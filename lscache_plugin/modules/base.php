<?php

/*
 *  @since      1.1
 *  @author     LiteSpeed Technologies <info@litespeedtech.com>
 *  @copyright  Copyright (c) 2017-2018 LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 *  @license    https://opensource.org/licenses/GPL-3.0
 */

abstract class LSCacheModuleBase
{
    protected $module;
    
    public function __construct(&$module){
        $this->module = $module;
    }    
    
    public function getModuleTags(){
        return "";
    }
    
    public function afterESIRender(&$content){
        return;
    }
}
