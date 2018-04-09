<?php

/* 
 *  @since      1.1.0
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
    }

    public function plgVmOnRemoveFromCart($cart, $prodid){
        $this->plugin->lscInstance->purgePrivate("com_virtuemart.cart");
    }

    public function plgVmOnUpdateCart($cart, $force, $html){
        $this->plugin->lscInstance->purgePrivate("com_virtuemart.cart");
    }

    public function plgVmAfterStoreProduct($data, $product_data){
        $tag = "com_virtuemart, com_virtuemart.product:" . $product_data->virtuemart_product_id;
        $this->plugin->lscInstance->purgePublic($tag);
        $this->plugin->log();
    }

    public function plgVmOnDeleteProduct($id, $ok){
        if(!$ok){
            return;
        }
        $tag = "com_virtuemart, com_virtuemart.product:" . $id;
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
        $this->plugin->lscInstance->purgePublic($tag);
        $this->plugin->log();
    }

    
    public function onPurgeContent($context, $row)
    {
        if($context == "com_virtuemart.product"){
            return "com_virtuemart, com_virtuemart.product:" . $row->virtuemart_product_id;
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
            return $option;;
        }
        else{
            return $option;
        }
    }   
    
    
}