<?php

use Joomla\CMS\Helper\ModuleHelper;

defined('_JEXEC') or die;
$user = $app->getIdentity();
if($user && $user->id!=0){
    require ModuleHelper::getLayoutPath('mod_lscache_purge', $params->get('layout', 'default'));
}
