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
        if($menuid){
            return 'com_menus:' . $menuid;
        }
        else{
            return '';
        }
        
    }
}