<?php

/* 
 *  @since      1.2.0
 *  @author     LiteSpeed Technologies <info@litespeedtech.com>
 *  @copyright  Copyright (c) 2017-2018 LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 *  @license    https://opensource.org/licenses/GPL-3.0
 */

class LSCacheModuleJoomlaLogin extends LSCacheModuleBase
{
    public function getModuleTags(){

        $cacheType = $this->plugin->getModuleCacheType($this->module);
        if(($cacheType!=plgSystemLSCache::MODULE_ESI) && ($this->plugin->settings->get('loginESI',1)==1)){
            $this->module->cache_type = plgSystemLSCache::MODULE_ESI;
            $this->module->lscache_type = -1;
            $this->module->lscache_ttl = 14;
            $this->module->lscache_tag = "joomla.login";
        }
        
        return "";
    }

 }
 
