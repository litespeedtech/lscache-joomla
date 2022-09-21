<?php

/*
 *  @since      1.2.0
 *  @author     LiteSpeed Technologies <info@litespeedtech.com>
 *  @copyright  Copyright (c) 2017-2018 LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 *  @license    https://opensource.org/licenses/GPL-3.0
 */
use Joomla\Component\Content\Site\Helper\RouteHelper;

class LSCacheComponentContent extends LSCacheComponentBase
{


    public function getComMap()
    {
        $comUrls =  array();
        $db = JFactory::getDbo();

//  get category urls
        $query = $db->getQuery(true)
                ->select($db->quoteName(array('id','language')))
                ->from('#__categories');
        $query->where($db->quoteName('published') . ' =1');
        $query->where($db->quoteName('extension') . ' ="com_content"');

        try {
            $db->setQuery($query);
            $categories = $db->loadObjectList();
            foreach($categories as $category){
                $link =  RouteHelper::getCategoryRoute($category->id, $category->language);
                $comUrls[] = $link;
            }
        } catch (RuntimeException $ex) {
            return array();
        }

//  get article urls;
        $query = $db->getQuery(true)
                ->select($db->quoteName(array('id','language','catid','alias')))
                ->from('#__content');
        $query->where($db->quoteName('state') . ' =1');

        try {
            $db->setQuery($query);
            $articles = $db->loadObjectList();
            foreach($articles as $article){
                if(empty($article->id)){
                    continue;
                }
                $slug = $article->alias ? ($article->id . ':' . $article->alias) : $article->id;

                $link = RouteHelper::getArticleRoute($slug, $article->catid, $article->language);
                
                $comUrls[] = $link;
            }
        } catch (RuntimeException $ex) {
            return array();
        }
        return $comUrls;
    }
    
    
   
}
