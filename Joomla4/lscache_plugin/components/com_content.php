<?php

/*
 *  @since      1.2.0
 *  @author     LiteSpeed Technologies <info@litespeedtech.com>
 *  @copyright  Copyright (c) 2017-2018 LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 *  @license    https://opensource.org/licenses/GPL-3.0
 */

class LSCacheComponentContent extends LSCacheComponentBase
{


    public function getComMap()
    {
        $comUrls =  array();
        $db = JFactory::getDbo();
        $query = $db->getQuery(true)
                ->select($db->quoteName('id'))
                ->from('#__content');
        $query->where($db->quoteName('state') . ' =1');

        try {
            $db->setQuery($query);
            $articles = $db->loadObjectList();
            foreach($articles as $article){
                if(empty($article->id)){
                    $article->id = 0;
                }
                $comUrls[] = 'index.php?option=com_content&view=article&id=' . $article->id;
            }
        } catch (RuntimeException $ex) {
            return array();
        }
        return $comUrls;
    }
    
    
   
}
