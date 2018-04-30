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
            $this->plugin->pageCachable = false;
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
        $this->plugin->purgeObject->option = "com_jabuilder";
        $this->plugin->purgeObject->idField = "id";

        if($context == "com_jabuilder.page"){
            $this->plugin->purgeObject->tags =  "com_jabuilder, com_jabuilder.item:" . $row->id;
            $this->plugin->purgeObject->ids[] = $row->id;
        }
        if($context == "com_jabuilder.item"){
            $this->plugin->purgeObject->tags =  "com_jabuilder, com_jabuilder.item:" . $row->id;
            $this->plugin->purgeObject->ids[] = $row->id;
        }
        else{
            $this->plugin->purgeObject->tags =  "com_jabuilder";
        }
    }
    
}