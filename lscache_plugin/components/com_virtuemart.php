<?php

/* 
 *  @since      1.1.1
 *  @author     LiteSpeed Technologies <info@litespeedtech.com>
 *  @copyright  Copyright (c) 2017-2018 LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 *  @license    https://opensource.org/licenses/GPL-3.0
 */

class LSCacheComponentVirtueMart extends LSCacheComponentBase{
    
    
    public function onRegisterEvents()
    {
        $this->dispatcher->register("plgVmOnAddToCart",$this);
        $this->dispatcher->register("plgVmOnRemoveFromCart", $this);
        $this->dispatcher->register("plgVmOnUpdateCart", $this);
        $this->dispatcher->register("plgVmAfterStoreProduct", $this);
        $this->dispatcher->register("plgVmOnDeleteProduct", $this);
        $this->dispatcher->register("plgVmAfterVendorStore", $this);
        $this->dispatcher->register("plgVmConfirmedOrder", $this);

        $db = JFactory::getDbo();
        $query = $db->getQuery(true)
            ->select('vendor_currency')
            ->from('#__virtuemart_vendors')
            ->where($db->quoteName('virtuemart_vendor_id') . '=1');
        $db->setQuery($query);
        $vendor_currency =  (int) $db->loadResult();
        
        $app = JFactory::getApplication();
        $currency = $app->getUserStateFromRequest( "virtuemart_currency_id", 'virtuemart_currency_id',$vendor_currency);

        if($currency!=$vendor_currency){
            $this->plugin->vary["vm_currency"] = $currency;
        }
        
        if(isset($this->plugin->pageElements["view"])){
            $view=$this->plugin->pageElements["view"];
        }
        else{
            $view = "";
        }

		$user = JFactory::getUser();
        if($user->get('guest')){
            if($view=="cart"){
                $this->plugin->pageCachable=false;
            }
        }
        else if($view!="category"){
            $this->plugin->pageCachable=false;
        }
        
    }
    
    public function plgVmOnAddToCart($cart){
        $this->plugin->lscInstance->purgePrivate("com_virtuemart.cart");
        $this->plugin->log();
    }

    public function plgVmOnRemoveFromCart($cart, $prodid){
        $this->plugin->lscInstance->purgePrivate("com_virtuemart.cart");
        $this->plugin->log();
    }

    public function plgVmOnUpdateCart($cart, $force, $html){
        $this->plugin->lscInstance->purgePrivate("com_virtuemart.cart");
        $this->plugin->log();
    }

    public function plgVmAfterStoreProduct($data, $product_data){
        $category_tag = $this->getProductCategories($product_data->virtuemart_product_id);
        $tag = "com_virtuemart, com_virtuemart.product:" . $product_data->virtuemart_product_id . $category_tag;
        $this->plugin->lscInstance->purgePublic($tag);
        $this->plugin->log();
    }

    public function plgVmOnDeleteProduct($id, $ok){
        if(!$ok){
            return;
        }
        $category_tag = $this->getProductCategories($id);
        $tag = "com_virtuemart, com_virtuemart.product:" . $id . $category_tag;
        $this->plugin->lscInstance->purgePublic($tag);
        $this->plugin->log();
    }

    public function plgVmAfterVendorStore($data){
        $tag =  "com_virtuemart, com_virtuemart.vendor:" . $data->virtuemart_vendor_id;
        $this->plugin->lscInstance->purgePublic($tag);
        $this->plugin->log();
    }

    public function plgVmConfirmedOrder($cart, $orderDetails){
        $tag =  "com_virtuemart";
        $productid = array();
        foreach($cart->products as $product){
            $productid[]=$product->virtuemart_product_id;
        }
        $category_tag = $this->getProductCategories($productid);
        $tag .= $category_tag;        
        $this->plugin->lscInstance->purgePublic($tag);
        $this->plugin->log();
    }

    public function onPurgeContent($context, $row)
    {
        if($context == "com_virtuemart.product"){
            $category_tag = $this->getProductCategories($row->virtuemart_product_id);
            return "com_virtuemart, com_virtuemart.product:" . $row->virtuemart_product_id . $category_tag;
        }
        else if($context == "com_virtuemart.category"){
            return "com_virtuemart, com_virtuemart.category:" . $row->virtuemart_category_id;
        }
        else if($context == "com_virtuemart.vendor"){
            return "com_virtuemart, com_virtuemart.vendor:" . $row->virtuemart_vendor_id;
        }
        else{
            return "com_virtuemart";
        }
    }
    
    public function getTags($option, $pageElements){

        if(isset($pageElements["context"])){
           $context = $pageElements["context"];
        }
        else{
           $context = $option;
        }
        
        if($context=="com_virtuemart.productdetails"){
            return  'com_virtuemart.product:' . $pageElements['content']->virtuemart_product_id;
        }
        else if($context=="com_virtuemart.category"){
            if(isset($pageElements["content"]) & !empty($pageElements["content"]->virtuemart_category_id)){
                return 'com_virtuemart.category:' . $pageElements["content"]->virtuemart_category_id;
            }
            return $option;;
        }
        else{
            return $option;
        }
    }
    

    private function getProductCategories($productid){
        $db = JFactory::getDbo();
        $query = $db->getQuery(true)
            ->select('virtuemart_category_id')
            ->from('#__virtuemart_product_categories');
        if(is_array($productid)){
            $products = implode(',', $productid);
            $query->where($db->quoteName('virtuemart_product_id') . ' in (' . $products . ')') ;
        }
        else{
            $query->where($db->quoteName('virtuemart_product_id') . '=' . (int)$productid);
        }
        $db->setQuery($query);
        $result = $db->loadObjectList();

        $tags = "";
        if(count($result)){
            foreach($result as $category){
                $tags .= ",com_virtuemart.category:" . $category->virtuemart_category_id;
            }
        }
        
        return $tags;
    }

    
}