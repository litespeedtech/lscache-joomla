<?php

/* 
 *  @since      1.0.0
 *  @author     LiteSpeed Technologies <info@litespeedtech.com>
 *  @copyright  Copyright (c) 2017-2018 LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 *  @license    https://opensource.org/licenses/GPL-3.0
 */

class LSCacheComponentKomento extends LSCacheComponentBase{
    
    
    public function onRegisterEvents()
    {
        $app = JFactory::getApplication();
        
        if(empty($namespace=$app->input->get('namespace','')) && empty($task=$app->input->get('task',''))){
            return;
        }
        
        if($app->isClient('administrator')  && in_array($namespace, array('admin.views.comments.stick','admin.views.comments.unstick','admin.views.comments.publish','admin.views.comments.unpublish'))){
            $ids=$app->input->get('ids', array());
            if(count($ids)<1){
                return;
            } else {
                $this->purgeComment($ids);
            }
            
        } else if($app->isClient('administrator')  && ($task=='remove')){
            $ids=$app->input->get('cid', array());
            if(count($ids)<1){
                return;
            } else {
                $this->purgeComment($ids);
            }

            $this->plugin->log($namespace);
        } else if((!$app->isAdmin()) && ($namespace=='site.views.komento.addcomment')){
            $component = $app->input->get('component');
            $cid = $app->input->get('cid');
            $this->plugin->onContentChangeState($component, array($cid), true);
        }
    }
    
    protected function purgeComment($ids){
            $db = JFactory::getDbo();
            $query = $db->getQuery(true)
                    ->select("distinct component, cid")
                    ->from('#__komento_comments')
                    ->where($db->quoteName('id') . ' in (' . implode(',', $ids) . ')');
            $db->setQuery($query);
            $comments = $db->loadObjectList();
            foreach($comments as $comment){
                $component = $comment->component;
                $this->plugin->onContentChangeState($component, array($comment->cid), true);
            }
    }    
}