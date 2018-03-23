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
    
    public function init($dispatcher,$plugin){
        $this->dispatcher = $dispatcher;
        $this->plugin = $plugin;
    }    
    
    //return: current page cachable
    public function onRegisterEvents(){
        return true;
    }
    
    
    //return purgeTags or false
    abstract public function onPurgeContent($context, $row);
}
