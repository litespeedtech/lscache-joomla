<?php

/*
 *  @since      1.1
 *  @author     LiteSpeed Technologies <info@litespeedtech.com>
 *  @copyright  Copyright (c) 2017-2018 LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 *  @license    https://opensource.org/licenses/GPL-3.0
 */

class LSCacheModuleRSForm extends LSCacheModuleBase
{
    public function getModuleTags(){
        if ($this->plugin->getModuleCacheType($this->module) == plgSystemLSCache::MODULE_ESI) {
            $this->module->lscache_type = 0;
            $this->module->module_type = 0;
            $this->module->lscache_pageurl = 1;
        }

        return "";
    }

    public function afterESIRender(&$content){
        $pageUrl = $this->plugin->getESIPageUrlParam();
        if ($pageUrl === '') {
            return;
        }

        $content = preg_replace_callback(
            '/<form\b[^>]*\baction="[^"]*"/i',
            function ($matches) use ($pageUrl) {
                return preg_replace('/action="[^"]*"/i', 'action="' . htmlspecialchars($pageUrl, ENT_COMPAT, 'UTF-8') . '"', $matches[0], 1);
            },
            $content
        );
    }
}
