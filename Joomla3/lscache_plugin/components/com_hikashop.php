<?php

/*
 *  @since      1.5.3
 *  @author     LiteSpeed Technologies <info@litespeedtech.com>
 *  @copyright  Copyright (c) 2017-2018 LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 *  @license    https://opensource.org/licenses/GPL-3.0
 */


class LSCacheComponentHikaShop extends LSCacheComponentBase
{
    // The other views of HikaShop display the cart of the visitor or need his session token
    protected $cachableViews = array('product', 'category');

    public function onRegisterEvents()
    {
        // A visitor who has a cart sees it in the cart module, which can be displayed on any page
        if (!empty($_COOKIE['hikashop_cart_id']) || !empty($_COOKIE['hikashop_wishlist_id'])) {
            $this->plugin->pageCachable = false;
            return;
        }

        if (!in_array($this->getView(), $this->cachableViews)) {
            $this->plugin->pageCachable = false;
        }
    }

    protected function getView()
    {
        $app = JFactory::getApplication();

        // HikaShop uses "ctrl", and falls back on "view" for the links built by Joomla
        $view = $app->input->getCmd('ctrl', '');
        if ($view === '') {
            $view = $app->input->getCmd('view', '');
        }
        if ($view === '' && isset($this->plugin->pageElements['view'])) {
            $view = $this->plugin->pageElements['view'];
        }

        return $view;
    }
}
