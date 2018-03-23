<?php

/* 
 *  @since      1.1
 *  @author     LiteSpeed Technologies <info@litespeedtech.com>
 *  @copyright  Copyright (c) 2017-2018 LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 *  @license    https://opensource.org/licenses/GPL-3.0
 */


class LSCacheModulesHelper
{
    const MODULE_NAME = 0;
    const MODULE_ELEMENT = 1;
    const MODULE_FILE = 2;
    const MODULE_CLASSNAME = 3;
        
    protected $activeModules;
    
	public function __construct () {
        require_once (__DIR__ . '/list.php');

        $this->activeModules = $lscacheModules;
    }
    
    public function getInstance($module){
        
        if(!isset($this->activeModules[$module->module])){
            return NULL;
        }

        $classModule = $this->activeModules[$module->module];
        
        $className = $classModule[self::MODULE_CLASSNAME];
        if (!class_exists($className)){
            $filename = __DIR__ . '/' . $classModule[self::MODULE_FILE];
            if (file_exists($filename)) {
                require_once $filename;
            } else {
                return NULL;
            }
        }
        
        if (!class_exists($className)){
            return NULL;
        }
        
        return new $className($module);
    }
    
    public function getModuleTags($module){
        $lscacheModule = $this->getInstance($module);
        if($lscacheModule==NULL){
            return "";
        }
        return $lscacheModule->getModuleTags();
    }
    
    public function afterESIRender($module, &$content){
        $lscacheModule = $this->getInstance($module);
        if($lscacheModule==NULL){
            return;
        }
        $lscacheModule->afterESIRender($content);
    }
    
}