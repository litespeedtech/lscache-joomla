<?php

/*
 *  @since      1.1
 *  @author     LiteSpeed Technologies <info@litespeedtech.com>
 *  @copyright  Copyright (c) 2017-2018 LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 *  @license    https://opensource.org/licenses/GPL-3.0
 */

abstract class LSCacheComponentBase  extends JPlugin
{
    protected $plugin;
    protected $dispatcher;
    
    public function init($dispatcher,&$plugin){
        $this->dispatcher = $dispatcher;
        $this->plugin = $plugin;
    }    
    
    public function onRegisterEvents(){
    }
    
    
    //return purgeTags or false
    abstract public function onPurgeContent($context, $row);
    
    //return cache tags;
    public function getTags($option, $pageElements){
        if(isset($pageElements["context"])){
            $context = $pageElements["context"];
        }
        else{
            $context = $option;
        }
        
        if(isset($pageElements["id"])){
            return $context . ':' . $pageElements["id"];
        }
        else{
            return $context;
        }
    }
    
    public function getComMap(){
        return array();
    }
}
