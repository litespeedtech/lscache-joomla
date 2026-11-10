<?php

/*
 *  @since      1.5.3
 *  @author     LiteSpeed Technologies <info@litespeedtech.com>
 *  @copyright  Copyright (c) 2017-2018 LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 *  @license    https://opensource.org/licenses/GPL-3.0
 */

use Joomla\Event\Event;
use Joomla\CMS\Factory;

class LSCacheComponentJ2Commerce extends LSCacheComponentBase
{
    // Catalog views look the same for every guest. The other views (carts, checkout,
    // confirmation, myprofile, paymentupdate) are built from the visitor's session.
    protected $cachableViews = array('products', 'product', 'categories', 'categoryalias', 'tags', 'tagalias', 'producttags');

    public function onRegisterEvents()
    {
        $this->dispatcher->addListener('onJ2CommerceAfterSaveProduct', [$this, 'onAfterSaveProduct']);
        $this->dispatcher->addListener('onJ2CommerceBeforeDeleteProduct', [$this, 'onBeforeDeleteProduct']);
        $this->dispatcher->addListener('onJ2CommerceAfterStockAdjust', [$this, 'onAfterStockAdjust']);
        foreach (array('AfterAddCartItem', 'AfterUpdateCart', 'AfterUpdateCartQuantity', 'RemoveFromCart', 'AfterPlaceOrder') as $event) {
            $this->dispatcher->addListener('onJ2Commerce' . $event, [$this, 'purgeCart']);
        }

        if (!$this->isCachableRequest()) {
            $this->plugin->pageCachable = false;
        }
    }

    // Reads the routed request rather than pageElements, so the decision is the same
    // whatever the active menu item and the Itemid.
    protected function isCachableRequest()
    {
        $app = Factory::getApplication();
        $input = $app->getInput();

        // Served from the cache, a task would never run.
        if ($input->getCmd('task', '') !== '') {
            return false;
        }
        if (!in_array($input->getCmd('view', 'products'), $this->cachableViews, true)) {
            return false;
        }
        // Prices depend on the customer group, and the page shows the visitor's cart and wishlist.
        if (!$app->getIdentity()->guest || self::hasCart()) {
            return false;
        }
        // Prices are shown in the currency the visitor picked.
        return !$app->getSession()->get('j2commerce_currency_user_set', false);
    }

    // J2Commerce sets j2commerce_cart_id, and j2commerce_<type>_cart_id for the other cart types (wishlist).
    public static function hasCart()
    {
        return count(preg_grep('/^j2commerce_(\w+_)?cart_id$/', array_keys($_COOKIE))) > 0;
    }

    public function getTags($option, $pageElements)
    {
        $input = Factory::getApplication()->getInput();
        if ($input->getCmd('view', '') !== 'product') {
            return 'com_j2commerce';
        }

        $id = $input->getInt('id', 0);
        $tag = 'com_j2commerce.product:' . $id;
        $articleId = $this->getArticleId($id);
        if ($articleId) {
            // Saving the product's article from the article editor purges com_content:<id>.
            $tag .= ',com_content:' . $articleId;
        }
        return $tag;
    }

    public function onAfterSaveProduct(Event $event)
    {
        $product = $event->getArgument('0');
        if (is_object($product) && !empty($product->j2commerce_product_id)) {
            $this->purgeProduct((int) $product->j2commerce_product_id);
        }
    }

    public function onBeforeDeleteProduct(Event $event)
    {
        $this->purgeProduct((int) $event->getArgument('0'));
    }

    // Stock shown on product and list pages changes after an order or a stock edit.
    public function onAfterStockAdjust(Event $event)
    {
        $db = Factory::getDbo();
        $query = $db->createQuery()
                ->select($db->quoteName('product_id'))
                ->from($db->quoteName('#__j2commerce_variants'))
                ->where($db->quoteName('j2commerce_variant_id') . ' = ' . (int) $event->getArgument('0'));
        $db->setQuery($query);
        $this->purgeProduct((int) $db->loadResult());
    }

    public function purgeCart()
    {
        if ($this->plugin->lscInstance) {
            $this->plugin->lscInstance->purgePrivate('com_j2commerce.cart');
            $this->plugin->log();
        }
    }

    public function onPurgeContent($context, $row)
    {
        $this->plugin->purgeObject->tags[] = 'com_j2commerce';
    }

    protected function purgeProduct($id)
    {
        if ($id <= 0) {
            return;
        }
        $this->plugin->purgeObject->tags[] = 'com_j2commerce,com_j2commerce.product:' . $id;
        $this->plugin->purgeAction();
    }

    protected function getArticleId($productId)
    {
        $db = Factory::getDbo();
        $query = $db->createQuery()
                ->select($db->quoteName('product_source_id'))
                ->from($db->quoteName('#__j2commerce_products'))
                ->where($db->quoteName('j2commerce_product_id') . ' = ' . (int) $productId)
                ->where($db->quoteName('product_source') . ' = ' . $db->quote('com_content'));
        $db->setQuery($query);
        return (int) $db->loadResult();
    }
}
