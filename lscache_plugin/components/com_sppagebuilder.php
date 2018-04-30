<?php

/* 
 *  @since      1.1.1
 *  @author     LiteSpeed Technologies <info@litespeedtech.com>
 *  @copyright  Copyright (c) 2017-2018 LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 *  @license    https://opensource.org/licenses/GPL-3.0
 */

class LSCacheComponentSPPageBuilder extends LSCacheComponentBase{
    
    
    public function onRegisterEvents()
    {
        $app = JFactory::getApplication();
        if(!$app->isAdmin()){
            if($this->plugin->pageElements['view']!='page'){
                $this->plugin->pageCachable = false;
            }
            else{
                $link = JUri::getInstance()->getQuery();
                if(!empty($link)){
                    $this->plugin->pageCachable = false;
                }
            }
            
        }
    }
    
    public function onPurgeContent($context, $row)
    {
        $this->plugin->purgeObject->option = "com_sppagebuilder";
        $this->plugin->purgeObject->idField = "id";
        
        if(!empty($row->id)){
            $this->plugin->purgeObject->tags =  "com_sppagebuilder, com_sppagebuilder:" . $row->id;
            $this->plugin->purgeObject->ids[] = $row->id;
        }
    }
    
    public function getTags($option, $pageElements){

        if(isset($pageElements["id"])){
            return $option . ':' . $pageElements["id"];
        }
        else{
            return $option;
        }

    }
    
    
}