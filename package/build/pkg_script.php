<?php

/* 
 *  @since      1.2.0
 *  @author     LiteSpeed Technologies <info@litespeedtech.com>
 *  @copyright  Copyright (c) 2017-2018 LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 *  @license    https://opensource.org/licenses/GPL-3.0
 */

class Pkg_LSCacheInstallerScript
{

    public function preflight($type, $parent)
    {
        $app = JFactory::getApplication();
        
        $minimum_version = '3.0.0';

        if (version_compare(JVERSION, $minimum_version, '<'))
        {
                $app->enqueueMessage('This plugin requires Joomla version v'. $minimum_version . ' or greater to work. Your installed version is ' . JVERSION, 'error');

                return FALSE;
        }

        // Server type
        if ( ! defined( 'LITESPEED_SERVER_TYPE' ) ) {
            if ( isset( $_SERVER['HTTP_X_LSCACHE'] ) && $_SERVER['HTTP_X_LSCACHE'] ) {
                define( 'LITESPEED_SERVER_TYPE', 'LITESPEED_SERVER_ADC' ) ;
            }
            elseif ( isset( $_SERVER['LSWS_EDITION'] ) && strpos( $_SERVER['LSWS_EDITION'], 'Openlitespeed' ) === 0 ) {
                define( 'LITESPEED_SERVER_TYPE', 'LITESPEED_SERVER_OLS' ) ;
            }
            elseif ( isset( $_SERVER['SERVER_SOFTWARE'] ) && $_SERVER['SERVER_SOFTWARE'] == 'LiteSpeed' ) {
                define( 'LITESPEED_SERVER_TYPE', 'LITESPEED_SERVER_ENT' ) ;
            }
            else {
                define( 'LITESPEED_SERVER_TYPE', 'NONE' ) ;
            }
        }

        // Checks if caching is allowed via server variable
        if ( ! empty ( $_SERVER['X-LSCACHE'] ) ||  LITESPEED_SERVER_TYPE === 'LITESPEED_SERVER_ADC' || defined( 'LITESPEED_CLI' ) ) {
            ! defined( 'LITESPEED_ALLOWED' ) &&  define( 'LITESPEED_ALLOWED', true ) ;
        }
        else{
            $app->enqueueMessage('This plugin requires running on a LiteSpeed Web Server, or it will not work properly');

            return true;
        }

        if ( LITESPEED_SERVER_TYPE == 'LITESPEED_SERVER_OLS') {

            $app->enqueueMessage('This version of LiteSpeed Web Server does not support ESI feature, ESI feature will not work');

            return true;
        }
    }

    public function install()
    {
        $this->lscacheEnable();
    }
    

    public function update()
    {
        $this->lscacheEnable();
    }
    
    
    protected function lscacheEnable()
    {
        $db = JFactory::getDbo();

        $query = $db->getQuery(true);
        $query->update($db->quoteName('#__extensions'))
                ->set('enabled=1')
                ->where($db->quoteName('element') . ' = ' . $db->quote('lscache'))
    			->where($db->quoteName('folder') . ' = ' . $db->quote('system'))
        		->where($db->quoteName('type') . ' = ' . $db->quote('plugin'));
        try {
            $db->setQuery($query)->execute();
        } catch (RuntimeException $ex) {
            $this->app->enqueueMessage($ex->getMessage(), 'error');
        }

        $query = $db->getQuery(true);
        $query->update($db->quoteName('#__extensions'))
                ->set('enabled=1')
                ->where($db->quoteName('element') . ' = ' . $db->quote('com_lscache'))
        		->where($db->quoteName('type') . ' = ' . $db->quote('component'));
        try {
            $db->setQuery($query)->execute();
        } catch (RuntimeException $ex) {
            $this->app->enqueueMessage($ex->getMessage(), 'error');
        }

        $query = $db->getQuery(true);
        $query->update($db->quoteName('#__extensions'))
                ->set('enabled=0')
                ->where($db->quoteName('element') . ' = ' . $db->quote('cache'))
    			->where($db->quoteName('folder') . ' = ' . $db->quote('system'))
        		->where($db->quoteName('type') . ' = ' . $db->quote('plugin'));
        try {
            $db->setQuery($query)->execute();
        } catch (RuntimeException $ex) {
            $this->app->enqueueMessage($ex->getMessage(), 'error');
        }

        $query = $db->getQuery(true);
        $query->update($db->quoteName('#__extensions'))
                ->set('enabled=0')
                ->where($db->quoteName('element') . ' = ' . $db->quote('jotcache'))
    			->where($db->quoteName('folder') . ' = ' . $db->quote('system'))
        		->where($db->quoteName('type') . ' = ' . $db->quote('plugin'));
        try {
            $db->setQuery($query)->execute();
        } catch (RuntimeException $ex) {
            $this->app->enqueueMessage($ex->getMessage(), 'error');
        }
        
        $query = $db->getQuery(true);
        $query->update($db->quoteName('#__extensions'))
                ->set('enabled=0')
                ->where($db->quoteName('element') . ' = ' . $db->quote('jotmarker'))
    			->where($db->quoteName('folder') . ' = ' . $db->quote('system'))
        		->where($db->quoteName('type') . ' = ' . $db->quote('plugin'));
        try {
            $db->setQuery($query)->execute();
        } catch (RuntimeException $ex) {
            $this->app->enqueueMessage($ex->getMessage(), 'error');
        }
        
        $query = $db->getQuery(true);
        $query->update($db->quoteName('#__virtuemart_configs'))
                ->set("config = replace ( config, 'enable_content_plugin=" . '"0"' . "', 'enable_content_plugin=". '"1"' ."')")
        		->where($db->quoteName('virtuemart_config_id') . ' = 1' );
        try {
            $db->setQuery($query)->execute();
        } catch (RuntimeException $ex) {
            return;
        }
    }
    
}
