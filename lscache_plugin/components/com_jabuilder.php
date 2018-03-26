<?php

/* 
 *  @since      1.0.0
 *  @author     LiteSpeed Technologies <info@litespeedtech.com>
 *  @copyright  Copyright (c) 2017-2018 LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 *  @license    https://opensource.org/licenses/GPL-3.0
 */

class LSCacheComponentJabuilder extends LSCacheComponentBase{
    
    
    public function onRegisterEvents()
    {
        $this->dispatcher->register("onJubSaveItem",$this);
        $this->dispatcher->register("onJubLoadItem", $this);

        $app = JFactory::getApplication();
        $option = $app->input->get('jub', null);
        if($option!=null){
            return false;
        }
        else{
            return true;
        }

    }
    
    
    
    public function onJubSaveItem($item){
        $this->plugin->purgeContent("com_jabuilder.item", $item);
    }

    public function onJubLoadItem($item){
        $this->plugin->onContentPrepare("com_jabuilder.item", $item, $item);
    }
    
    public function onPurgeContent($context, $row)
    {
        if($context == "com_jabuilder.page"){
            return "com_jabuilder, com_jabuilder:" . $row->id;
        }
        if($context == "com_jabuilder.item"){
            return "com_jabuilder, com_jabuilder:" . $row->id;
        }
        else{
            return "com_jabuilder";
        }
    }
    
}