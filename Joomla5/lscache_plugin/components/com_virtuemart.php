<?php

/*
 *  @since      1.2.0
 *  @author     LiteSpeed Technologies <info@litespeedtech.com>
 *  @copyright  Copyright (c) 2017-2018 LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 *  @license    https://opensource.org/licenses/GPL-3.0
 */

use Joomla\Event\Event;

class LSCacheComponentVirtueMart extends LSCacheComponentBase
{

    public function onRegisterEvents()
    {
        $this->dispatcher->addListener("plgVmOnAddToCart", [$this,'plgVmOnAddToCart']);
        $this->dispatcher->addListener("plgVmOnRemoveFromCart", [$this,'plgVmOnRemoveFromCart']);
        $this->dispatcher->addListener("plgVmOnUpdateCart", [$this,'plgVmOnUpdateCart']);
        $this->dispatcher->addListener("plgVmAfterStoreProduct", [$this,'plgVmAfterStoreProduct']);
        $this->dispatcher->addListener("plgVmOnDeleteProduct", [$this,'plgVmOnDeleteProduct']);
        $this->dispatcher->addListener("plgVmAfterVendorStore", [$this,'plgVmAfterVendorStore']);
        $this->dispatcher->addListener("plgVmConfirmedOrder", [$this,'plgVmConfirmedOrder']);
        $this->dispatcher->addListener("onContentPrepare", [$this,'onContentPrepare']);

        $db = JFactory::getDbo();
        $query = $db->getQuery(true)
                ->select('vendor_currency')
                ->from('#__virtuemart_vendors')
                ->where($db->quoteName('virtuemart_vendor_id') . '=1');
        $db->setQuery($query);
        $vendor_currency = (int) $db->loadResult();

        $app = JFactory::getApplication();
        $currency = $app->getUserStateFromRequest("virtuemart_currency_id", 'virtuemart_currency_id', $vendor_currency);

        if ($currency != $vendor_currency) {
            $this->plugin->vary["vm_currency"] = $currency;
        }

        if (isset($this->plugin->pageElements["view"])) {
            $view = $this->plugin->pageElements["view"];
        } else {
            $view = "";
        }

        $user = JFactory::getUser();
        if ($user->get('guest')) {
            if (($view == "cart") || ($view == "user")) {
                $this->plugin->pageCachable = false;
            }
        } else if ($view != "category") {
            $this->plugin->pageCachable = false;
        }
    }

    public function plgVmOnAddToCart($cart)
    {
        if (!$this->plugin->lscInstance) {
            return;
        }
        $this->plugin->lscInstance->purgePrivate("com_virtuemart.cart");
        $this->plugin->log();
    }

    public function plgVmOnRemoveFromCart($cart, $prodid=null)
    {
        if (!$this->plugin->lscInstance) {
            return;
        }
        $this->plugin->lscInstance->purgePrivate("com_virtuemart.cart");
        $this->plugin->log();
    }

    public function plgVmOnUpdateCart($cart, $force=null, $html=null)
    {
        if (!$this->plugin->lscInstance) {
            return;
        }
        $this->plugin->lscInstance->purgePrivate("com_virtuemart.cart");
        $this->plugin->log();
    }

    public function plgVmAfterStoreProduct($data, $product_data=null)
    {
        if($data instanceof Event) {
            return $this->plgVmOnDeleteProduct($data->getArgument('0'), $data->getArgument('1'));
        }

        $category_tag = $this->getProductCategoryTags($product_data->virtuemart_product_id);
        $tag = "com_virtuemart, com_virtuemart.product:" . $product_data->virtuemart_product_id . $category_tag;
        $this->plugin->purgeObject->tags[] = $tag;
        if($this->plugin->purgeObject->autoRecache==0){
            $this->plugin->purgeAction();
            return;
        }
        $this->plugin->purgeObject->urls = $this->getProductCategoryUrls($product_data->virtuemart_product_id);
        $this->plugin->purgeObject->urls[] = 'index.php?option=com_virtuemart&view=productdetails&virtuemart_product_id=' . $product_data->virtuemart_product_id.'&virtuemart_category_id=0';
        $this->plugin->purgeAction();
        
    }

    public function plgVmOnDeleteProduct($data, $ok=true)
    {
        if($data instanceof Event) {
            return $this->plgVmOnDeleteProduct($data->getArgument('0'),$data->getArgument('1'));
        }

        if (!$ok) {
            return;
        }
        
        $id = $data['virtuemart_product_id'];
        
        $category_tag = $this->getProductCategoryTags($id);
        $tag = "com_virtuemart, com_virtuemart.product:" . $id . $category_tag;
        $this->plugin->purgeObject->tags[] = $tag;
        if($this->plugin->purgeObject->autoRecache==0){
            $this->plugin->purgeAction();
            return;
        }
        $this->plugin->purgeObject->urls = $this->getProductCategoryUrls($id);
        $this->plugin->purgeObject->urls[] = 'index.php?option=com_virtuemart&view=productdetails&virtuemart_product_id=' . $id.'&virtuemart_category_id=0';
        $this->plugin->purgeAction();
    }

    public function plgVmAfterVendorStore($data)
    {
        $tag = "com_virtuemart, com_virtuemart.vendor:" . $data->virtuemart_vendor_id;
        $this->plugin->purgeObject->tags[] = $tag;
        $this->plugin->purgeAction();
    }

    public function plgVmConfirmedOrder($cart, $orderDetails = null)
    {
        if($cart instanceof Event) {
            $cart = $cart->getArgument('0');
        }

        $tag = "com_virtuemart";
        $productids = array();
        $productUrls = array();
        
        foreach ($cart->products as $product) {
            $productids[] = $product->virtuemart_product_id;
            $productUrls[] = 'index.php?option=com_virtuemart&view=productdetails&virtuemart_product_id=' . $product->virtuemart_product_id .'&virtuemart_category_id=0';
        }
        
        if(empty($productids)){
            return;
        }
        
        $category_tag = $this->getProductCategoryTags($productids);
        $tag .= $category_tag;
        $this->plugin->purgeObject->tags[] = $tag;
        if($this->plugin->purgeObject->autoRecache==0){
            $this->plugin->purgeAction();
            return;
        }
        $urls = $this->getProductCategoryUrls($id);
        $this->plugin->purgeObject->urls = array_merge($urls, $productUrls);
        $this->plugin->purgeAction();
    }

    public function onPurgeContent($context, $row)
    {
        if ($context == "com_virtuemart.product") {
            $this->plgVmOnDeleteProduct($row->virtuemart_product_id,true);
            
        } else if ($context == "com_virtuemart.category") {
            $tag = "com_virtuemart, com_virtuemart.category:" . $row->virtuemart_category_id;
            $this->plugin->purgeObject->tags[] = $tag;
            $this->plugin->purgeObject->urls[] = 'index.php?option=com_virtuemart&view=category&virtuemart_category_id='.$row->virtuemart_category_id;
        } else if ($context == "com_virtuemart.vendor") {
            $this->plgVmAfterVendorStore($row);
        } else {
            $this->plugin->purgeObject->tags[] = "com_virtuemart";
        }
    }

    public function getTags($option, $pageElements)
    {
        if (isset($pageElements["context"])) {
            $context = $pageElements["context"];
        } else {
            $context = $option;
        }

        if ($context == "com_virtuemart.productdetails") {
            return 'com_virtuemart.product:' . $pageElements['content']->virtuemart_product_id;
        } else if ($context == "com_virtuemart.category") {
            if (isset($pageElements["content"]) & !empty($pageElements["content"]->virtuemart_category_id)) {
                return 'com_virtuemart.category:' . $pageElements["content"]->virtuemart_category_id;
            }
            return $option;
        } else {
            return $option;
        }
    }

    private function getProductCategories($productid)
    {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true)
                ->select($db->quoteName(array('virtuemart_category_id', 'virtuemart_product_id')))
                ->from('#__virtuemart_product_categories');
        if (is_array($productid)) {
            $products = implode(',', $productid);
            $query->where($db->quoteName('virtuemart_product_id') . ' in (' . $products . ')');
        } else {
            $query->where($db->quoteName('virtuemart_product_id') . '=' . (int) $productid);
        }
        $db->setQuery($query);
        $result = $db->loadObjectList();
        return $result;
    }
    
    private function getProductCategoryTags($productid)
    {
        $categories = $this->getProductCategories($productid);
        $tags = "";
        if (count($categories)) {
            foreach ($categories as $category) {
                $tags .= ",com_virtuemart.category:" . $category->virtuemart_category_id;
            }
        }
        return $tags;
    }

    private function getProductCategoryUrls($productid)
    {
        $categories = $this->getProductCategories($productid);
        $urls = array();
        $catids = array();
        if (count($categories)) {
            foreach ($categories as $category) {
                $urls[] =  'index.php?option=com_virtuemart&view=productdetails&virtuemart_product_id=' . $category->virtuemart_product_id.'&virtuemart_category_id='.$category->virtuemart_category_id;
                if(!in_array($category->virtuemart_category_id, $catids)){
                    $urls[] = 'index.php?option=com_virtuemart&view=category&virtuemart_category_id='.$category->virtuemart_category_id;
                    $catids[] = $category->virtuemart_category_id;
                }
            }
        }
        return $urls;
    }
    
    public function getComMap()
    {
        $comUrls =  array();
        $comUrls[] = 'index.php?option=com_virtuemart';

        $db = JFactory::getDbo();
        $query = $db->getQuery(true)
                ->select('virtuemart_category_id')
                ->from('#__virtuemart_categories');
        try {
            $db->setQuery($query);
            $cateids = $db->loadColumn();
            foreach($cateids as $cateid){
                $comUrls[] = 'index.php?option=com_virtuemart&view=category&virtuemart_category_id='.$cateid;
            }
        } catch (RuntimeException $ex) {
            return array();
        }

        $query = $db->getQuery(true)
                ->select($db->quoteName(array('virtuemart_category_id', 'virtuemart_product_id')))
                ->from('#__virtuemart_product_categories');
        try {
            $db->setQuery($query);
            $products = $db->loadObjectList();
            foreach($products as $product){
                if(empty($product->virtuemart_category_id)){
                    $product->virtuemart_category_id = 0;
                }
                $comUrls[] = 'index.php?option=com_virtuemart&view=productdetails&virtuemart_product_id=' . $product->virtuemart_product_id.'&virtuemart_category_id='.$product->virtuemart_category_id;
            }
        } catch (RuntimeException $ex) {
            return array();
        }

        $query = $db->getQuery(true)
                ->select($db->quoteName('virtuemart_product_id'))
                ->from('#__virtuemart_products');
        try {
            $db->setQuery($query);
            $products = $db->loadObjectList();
            foreach($products as $product){
                if(empty($product->virtuemart_category_id)){
                    $product->virtuemart_category_id = 0;
                }
                $comUrls[] = 'index.php?option=com_virtuemart&view=productdetails&virtuemart_product_id=' . $product->virtuemart_product_id.'&virtuemart_category_id=0';
            }
        } catch (RuntimeException $ex) {
            return array();
        }
        return $comUrls;
    }
    
    
    public function onContentPrepare($context, $row=null, $params=null, $page = 0) {
        if($context instanceof Event) {
            return $this->onContentPrepare($context->getArgument(0),$context->getArgument(1),$context->getArgument(2),$context->getArgument(3));
        }

        $productModel = VmModel::getModel('Product');
        $total = $productModel->getTotal();
        $limitstart = $productModel->_limitStart;
        $limit = $productModel->_limit;
        $app = JFactory::getApplication();
        $start = $app->input->get('start', $limitstart);
        $pagination = $productModel->_pagination;
        
        $pageCurrent = "pages.current";
        if(($start>0) && ($pagination->$pageCurrent==1)){
            $this->plugin->pageCachable = false;
            return;
        }
        
        if($limitstart>$total){
            $this->plugin->pageCachable = false;
            return;
        }
        
        if($pagination instanceof VmPagination){
            if($limitstart!=$pagination->limitstart){
                $this->plugin->pageCachable = false;
                return;
            }
            if($limit!=$pagination->limit){
                $this->plugin->pageCachable = false;
                return;
            }
        }
    }

   
}
