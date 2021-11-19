<?php
defined('_JEXEC') or die;
$user = $app->getIdentity();
if($user && $user->id!=0){
    require JModuleHelper::getLayoutPath('mod_lscache', $params->get('layout', 'default'));
}