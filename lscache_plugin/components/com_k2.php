<?php

/* 
 *  @since      1.0.0
 *  @author     LiteSpeed Technologies <info@litespeedtech.com>
 *  @copyright  Copyright (c) 2017-2018 LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 *  @license    https://opensource.org/licenses/GPL-3.0
 */

class LSCacheComponentK2 extends LSCacheComponentBase{

    public function onRegisterEvents()
    {
        $this->dispatcher->register("onFinderAfterSave",$this);
        $this->dispatcher->register("onFinderAfterDelete", $this);
        $this->dispatcher->register("onFinderChangeState", $this);
    }

    public function onFinderAfterSave($context, $row, $isNew)
    {
        if($context!="com_k2.item"){
            $this->plugin->onContentAfterSave($context,$row,$isNew);
        }
    }

    public function onFinderAfterDelete($context, $row)
    {
        if($context!="com_k2.item"){
            $this->plugin->onContentAfterDelete($context,$row,$isNew);
        }
    }

    public function onFinderChangeState($context, $pks, $value){
        if($context!="com_k2.item"){
            $this->plugin->onContentChangeState($context, $pks, $value);
        }
    }
    
    public function onPurgeContent($context, $row){
        
        $this->plugin->purgeObject->option = "com_k2";
        $this->plugin->purgeObject->idField = "id";
        if($context == "com_k2.item"){
            $purgeTags =  'com_k2,com_k2.item:' . $row->id . ",com_k2.category:" . $row->catid . ",com_k2.user:" . $row->created_by;
            $tags = JRequest::getVar('tags', NULL, 'POST', 'array');
			if (count($tags))
			{
				$tags = array_unique($tags);
				foreach ($tags as $tag)
				{
                    $purgeTags .= ",com_k2.tag:" . $tag;
                }
            }
            $this->plugin->purgeObject->tags = $purgeTags;
            $this->plugin->purgeObject->ids = array($row->id, $row->catid);
            
        }
        else if($context=="com_k2.tag"){
            $this->plugin->purgeObject->tags = "com_k2," . $context . ":" . $row->name;
        }
        else if(!empty($row->id)){
            $this->plugin->purgeObject->tags = "com_k2," . $context . ":" . $row->id;
            $this->plugin->purgeObject->ids[] = $row->id;
        }
        else{
            $this->plugin->purgeObject->tags = 'com_k2';
        }
        
   }
   
    public function getTags($option, $pageElements){

        if(isset($pageElements["layout"])){
           $context = $option . "." . $pageElements["layout"];
        }
        else if(isset($pageElements["context"])){
           $context = $pageElements["context"];
        }
        else{
           $context = $option;
        }
        
        if($context=="com_k2.tag"){
            return $context . ':' . $pageElements['tag'];
        }
        else if(isset($pageElements["id"])){
            return $context . ':' . $pageElements["id"];
        }
        else{
            return $option;
        }

    }
}

