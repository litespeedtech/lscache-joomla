<?php

/* 
 *  @since      1.0.0
 *  @author     LiteSpeed Technologies <info@litespeedtech.com>
 *  @copyright  Copyright (c) 2017-2018 LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 *  @license    https://opensource.org/licenses/GPL-3.0
 */

use Joomla\Registry\Registry;

class LSCacheModuleJoomlaMenu extends LSCacheModuleBase
{
    public function getModuleTags(){

        $moduleParams = new Registry;
        $moduleParams->loadString($this->module->params);
        $menuid = $moduleParams->get('base', FALSE);
        $menutype = $moduleParams->get('menutype', FALSE);
        if($menuid){
            return 'com_menus:' . $menuid;
        }
        else if($menutype){
            if($this->plugin->getModuleCacheType($this->module)==2){
                return 'com_menus';
            }
            return 'com_menus:' . $menutype;
        }
        else{
            return 'com_menus';
        }
        
    }
}