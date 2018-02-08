<?php

/**
 * General function of communicating with LSWS Server for LSCache operations,
 * The Base class works at server level, its operation will affect the whole server.
 *
 * @since      1.0.0
 * @author     LiteSpeed Technologies <info@litespeedtech.com>
 * @copyright  Copyright (c) 2017-2018 LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 * @license    https://opensource.org/licenses/GPL-3.0
 */
class LiteSpeedCacheBase
{
    const PUBLIC_CACHE_CONTROL = 'X-LiteSpeed-Cache-Control:public,max-age=';
    const PRIVATE_CACHE_CONTROL = 'X-LiteSpeed-Cache-Control:private,max-age=';
    const CACHE_PURGE = 'X-LiteSpeed-Purge:';
    const CACHE_TAG = 'X-LiteSpeed-Tag:';
    const VARY_COOKIE = '_lscache_vary';
    const PRIVATE_COOKIE = 'lsc_private';

    protected $public_cache_timeout = '1000000';
    protected $private_cache_timeout = '5000';
    protected $logbuffer = "";
    
    /**
     *
     *  load class configuration from $config object
     *
     * @since   1.0.0
     */
    public function config($config)
    {
        $this->public_cache_timeout = $config['public_cache_timeout'];
        $this->private_cache_timeout = $config['private_cache_timeout'];
    }

    /**
     *
     * put tag into Array in the format for this site only.
     *
     * @since   1.0.0
     */
    protected function tagsForSite(Array &$tagArray, $rawTags, $prefix = "")
    {
        if (!isset($rawTags)) {
            return;
        }

        if ($rawTags == "") {
            return;
        }

        $tags = explode(",", $rawTags);
        
        foreach ($tags as $tag) {
            $tagStr = $prefix . trim($tag);
            if(!in_array($tagStr, $tagArray, false)){
                array_push($tagArray, $tagStr);
            }
        }
    }

    /**
     *
     * put tag in Array together to make an head command .
     *
     * @since   1.0.0
     */
    protected function tagCommand($start, Array $tagArray){
        $cmd = $start;
        
        foreach ($tagArray as $tag) {
            $cmd .= $tag . ",";
        }
        return substr($cmd,0,-1);
    }
    
    /**
     *
     *  purge public cache with specified tags for this site.
     *
     * @since   1.0.0
     */
    public function purgePublic($publicTags)
    {
        if ((!isset($publicTags)) || ($publicTags == "")) {
            return;
        }
        
        $siteTags = Array();
        $this->tagsForSite($siteTags, $publicTags);
        $LSheader = $this->tagCommand(self::CACHE_PURGE . 'public,' ,  $siteTags) ;
        $this->liteSpeedHeader($LSheader);
    }

    /**
     *
     *  purge private cache with specified tags for this site.
     *
     * @since   1.0.0
     */
    public function purgePrivate($privateTags)
    {
        if ((!isset($privateTags)) || ($privateTags == "")) {
            return;
        }

        $siteTags = Array();
        $this->tagsForSite($siteTags, $privateTags);
        $LSheader = $this->tagCommand( self::CACHE_PURGE . 'private,' ,  $siteTags);
        $this->liteSpeedHeader($LSheader);
    }

    /**
     *
     *  purge all public cache of this site
     *
     * @since   1.0.0
     */
    public function purgeAllPublic()
    {
        $LSheader = self::CACHE_PURGE . 'public,*';
        $this->liteSpeedHeader($LSheader);
    }

    /**
     *
     *  purge all private cache of this session
     *
     * @since   0.1
     */
    public function purgeAllPrivate()
    {
        $LSheader = self::CACHE_PURGE . 'private,*';
        $this->liteSpeedHeader($LSheader);
    }

    /**
     *
     * Cache this page for public use if not cached before
     *
     * @since   1.0.0
     * @param string $tags
     */
    public function cachePublic($publicTags)
    {
        if (!isset($publicTags) || ($publicTags == null)) {
            return;
        }

        $LSheader = self::PUBLIC_CACHE_CONTROL . $this->public_cache_timeout;
        $this->liteSpeedHeader($LSheader);

        $siteTags = Array();
        $this->tagsForSite($siteTags, $publicTags);

        $LSheader = $this->tagCommand( self::CACHE_TAG ,  $siteTags);
        $this->liteSpeedHeader($LSheader);
    }

    /**
     *
     * Cache this page for private session if not cached before
     *
     * @since   0.1
     */
    public function cachePrivate($publicTags, $privateTags = "")
    {
        if ( !isset($privateTags) || ($privateTags == "") ) {
            if ( !isset($publicTags) || ($publicTags == "")) {
                return;
            }
        }

        $LSheader = self::PRIVATE_CACHE_CONTROL . $this->private_cache_timeout;
        $this->liteSpeedHeader($LSheader);

        $siteTags = Array();
        $this->tagsForSite($siteTags, $publicTags, "public:");
        if($privateTags!=""){
            $this->tagsForSite($siteTags, $privateTags);
        }
        else{
            array_push($siteTags,  'pvt');
        }
        
        $LSheader = $this->tagCommand( self::CACHE_TAG ,  $siteTags);
        $this->liteSpeedHeader($LSheader);
    }

    /**
     *
     * Cache this page for private use if not cached before
     *
     * @since   1.0.0
     */
    protected function liteSpeedHeader($LSheader)
    {
        $this->logbuffer .= $LSheader . "\t";
        header($LSheader);
    }

    /**
     *
     *  set or delete private cookie.
     *
     * @since   1.0.0
     */
    public function checkPrivateCookie($path = '/')
    {
        if (!isset($_COOKIE[self::PRIVATE_COOKIE])) {
            setcookie(self::PRIVATE_COOKIE, md5((String)rand()), 0, $path);
        }
    }

    /**
     *
     *  set or delete cache vary cookie, if cookie need no change return true;
     *
     * @since   1.0.0
     */
    public function checkVary($value, $path='/')
    {
        if ($value == "") {
            if (isset($_COOKIE[self::VARY_COOKIE])) {
                setcookie(self::VARY_COOKIE, "", '0', $path);
                return false;
            }
            return true;
        }
        
        if(!isset($_COOKIE[self::VARY_COOKIE])){
            setcookie(self::VARY_COOKIE, $value, '0', $path);
            return false;
        }

        if($_COOKIE[self::VARY_COOKIE] != $value){
            setcookie(self::VARY_COOKIE, $value, '0', $path);
            return false;
        }
        
        return true;
    }

    /**
     *
     *  get LiteSpeedCache special head log
     *
     * @since   1.0.0
     */
    public function getLogBuffer()
    {
        $retVal = $this->logbuffer;
        $this->logbuffer = '';
        return $retVal;
    }
    
}
