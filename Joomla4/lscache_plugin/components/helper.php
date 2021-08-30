<?php

/* 
 *  @since      1.1
 *  @author     LiteSpeed Technologies <info@litespeedtech.com>
 *  @copyright  Copyright (c) 2017-2018 LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 *  @license    https://opensource.org/licenses/GPL-3.0
 */

class LSCacheComponentsHelper
{
    const COM_NAME = 0;
    const COM_ELEMENT = 1;
    const COM_FILE = 2;
    const COM_CLASSNAME = 3;
    const COM_RUNADMIN = 4;
        
    protected $activeComponents;
    protected $plugin;
    protected $dispatcher;
    
    public function __construct (&$plugin, $filter = array()) {
        require_once (__DIR__ . '/list.php');
        
        $this->plugin = $plugin;
        $this->dispatcher =  JFactory::getApplication();
        $this->activeComponents = $lscacheComponents;
        
        if(count($filter)>0){
            foreach($filter as $component){
                unset($this->activeComponents[$component]);
            }
        }
    }
    
    public function supportComponent($com_name){
        if(isset($this->activeComponents[$com_name])){
            $app = JFactory::getApplication();
            if($app->isClient('administrator')){
                $component = $this->activeComponents[$com_name];
                if(!$component[self::COM_RUNADMIN]){
                    return false;
                }
            }
            return true;
        }
        else{
            return false;
        }
    }
    
    public function getInstance($com_name){
        if(!$this->supportComponent($com_name)){
            return null;
        }
        
        $component = $this->activeComponents[$com_name];
        return $this->getInstanceInternal($component);
    }
    
    protected function getInstanceInternal($component){
        
        $className = $component[self::COM_CLASSNAME];
        if (!class_exists($className)){
            $filename = __DIR__ . '/' . $component[self::COM_FILE];
            if (file_exists($filename)) {
                require_once $filename;
            } else {
                return NULL;
            }
        }
        
        if (!class_exists($className)){
            return NULL;
        }
        
        $instance = new $className($this->dispatcher, array());
        $instance->init($this->dispatcher,$this->plugin);
        return $instance;
        
    }
    
    public function registerEvents($com_name){
        if($com_name!=null){
            $com_instance = $this->getInstance($com_name);
            if($com_instance==null){
                return;
            }
            $com_instance->onRegisterEvents();
        }
    }
    
    public function onPurgeContent($com_name, $context, $row){
        $com_instance = $this->getInstance($com_name);
        if($com_instance==NULL){
            return false;
        }
        $com_instance->onPurgeContent($context, $row);
    }
    
    public function getTags($com_name, $pageElements){
        $com_instance = $this->getInstance($com_name);
        if($com_instance==NULL){
            return "";
        }
        return $com_instance->getTags($com_name, $pageElements);
    }
    
    public function getComMap($com_name){
        $com_instance = $this->getInstance($com_name);
        if($com_instance==NULL){
            return array();
        }
        return $com_instance->getComMap();
    }
}