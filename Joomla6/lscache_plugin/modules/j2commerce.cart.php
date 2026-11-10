<?php

/* 
 *  @since      1.5.3
 *  @author     LiteSpeed Technologies <info@litespeedtech.com>
 *  @copyright  Copyright (c) 2017-2018 LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 *  @license    https://opensource.org/licenses/GPL-3.0
 */

use Joomla\CMS\Factory;

// The J2Commerce cart and wishlist modules show the visitor's own items, on any page.
class LSCacheModuleJ2CommerceCart extends LSCacheModuleBase
{
    public function getModuleTags(){
        // Never store this visitor's items inside a public page. Without ESI, a page cached
        // for a guest with an empty cart is what a visitor with a cart then gets.
        if (!Factory::getApplication()->getIdentity()->guest
            || count(preg_grep('/^j2commerce_(\w+_)?cart_id$/', array_keys($_COOKIE)))) {
            $this->plugin->pageCachable = false;
            return "";
        }

        // With ESI, the module is rendered privately for each visitor, purged on cart changes.
        if($this->plugin->getModuleCacheType($this->module)!=1){
            $this->module->original_cache_type = $this->module->cache_type;
            $this->module->cache_type = 1;
            $this->module->lscache_type = -1;
            $this->module->lscache_ttl = 120;
            $this->module->lscache_tag = 'com_j2commerce.cart';
            $this->module->vary_language = 0;
        }
        return "";
    }
}
