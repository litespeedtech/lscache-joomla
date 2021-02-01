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

    public function postflight( $type, $parent ) {
        
        $package = $parent->getParent()->getPath('source');
        $this->installEsiTemplate($package);
        
        if (function_exists('opcache_reset')){
            opcache_reset();
        } else if (function_exists('phpopcache_reset')){
            phpopcache_reset();
        }       
    }
    
    public function install()
    {
        $this->lscacheEnable();
    }
    
    public function uninstall()
    {
        $this->clearHtaccess();
    }

    public function update()
    {
        $this->lscacheEnable();
    }
    
    protected function lscacheEnable()
    {
        $db = JFactory::getDbo();
        $app = JFactory::getApplication();

        $query = $db->getQuery(true);
        $query->update($db->quoteName('#__extensions'))
                ->set('enabled=1')
                ->where($db->quoteName('element') . ' = ' . $db->quote('lscache'))
    			->where($db->quoteName('folder') . ' = ' . $db->quote('system'))
        		->where($db->quoteName('type') . ' = ' . $db->quote('plugin'));
        try {
            $db->setQuery($query)->execute();
        } catch (RuntimeException $ex) {
            $app->enqueueMessage($ex->getMessage(), 'error');
        }

        $query = $db->getQuery(true);
        $query->update($db->quoteName('#__extensions'))
                ->set('enabled=1')
                ->where($db->quoteName('element') . ' = ' . $db->quote('com_lscache'))
        		->where($db->quoteName('type') . ' = ' . $db->quote('component'));
        try {
            $db->setQuery($query)->execute();
        } catch (RuntimeException $ex) {
            $app->enqueueMessage($ex->getMessage(), 'error');
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
            $app->enqueueMessage($ex->getMessage(), 'error');
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
            $app->enqueueMessage($ex->getMessage(), 'error');
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
            $app->enqueueMessage($ex->getMessage(), 'error');
        }

        //Add module
        $query = $db->getQuery(true);
		$query->select($db->quoteName('id'))
			->from('#__modules')
			->where($db->quoteName('module') . ' = ' . $db->quote('mod_lscache'))
			->where($db->quoteName('client_id') . ' = 1');                        
		$db->setQuery($query, 0, 1);
		$id = $db->loadResult();

		if ( ! $id)
		{
			return;
		}

        $query = $db->getQuery(true);
        $query->select($db->quoteName('moduleid'))
			->from('#__modules_menu')
			->where($db->quoteName('moduleid') . ' = ' . (int) $id);
		$db->setQuery($query, 0, 1)->execute();
		$exists = $db->loadResult();
		if ($exists)
		{
			return;
		}

        $query = $db->getQuery(true);
		$query->select($db->quoteName('ordering'))
			->from('#__modules')
			->where($db->quoteName('position') . ' = ' . $db->quote('status'))
			->where($db->quoteName('client_id') . ' = 1')
			->order('ordering DESC');
		$db->setQuery($query, 0, 1)->execute();
		$ordering = $db->loadResult();
		$ordering++;
        
        $query = $db->getQuery(true);
        $query->update($db->quoteName('#__modules'))
			->set($db->quoteName('published') . ' = 1')
			->set($db->quoteName('ordering') . ' = ' . (int)$ordering)
			->set($db->quoteName('position') . ' = ' . $db->quote('status'))
			->where($db->quoteName('id') . ' = ' . (int)$id);
        try {
            $db->setQuery($query)->execute();
        } catch (Exception $ex) {
            $app->enqueueMessage($ex->getMessage(), 'error');
        }

        $query = $db->getQuery(true);
		$query->insert('#__modules_menu')
			->columns([$db->quoteName('moduleid'), $db->quoteName('menuid')])
			->values((int) $id . ', 0');
        try {
            $db->setQuery($query)->execute();
        } catch (Exception $ex) {
            $app->enqueueMessage($ex->getMessage(), 'error');
        }


        $query = $db->getQuery(true);
        try {
            $query->update($db->quoteName('#__virtuemart_configs'))
                ->set("config = replace ( config, 'enable_content_plugin=" . '"0"' . "', 'enable_content_plugin=". '"1"' ."')")
        		->where($db->quoteName('virtuemart_config_id') . ' = 1' );
            $db->setQuery($query)->execute();
        } catch (Exception $ex) {
            error_log($ex->getMessage());
        }
        
        
    }
    
	private function clearHtaccess()
	{
        $htaccess = JPATH_ROOT  . '/.htaccess';

        if (file_exists($htaccess))
        {
            $contents = file_get_contents($htaccess);
            $pattern = '@\n?### LITESPEED_CACHE_START - Do not remove this line.*?### LITESPEED_CACHE_END@s';

            $clean_contents = preg_replace($pattern, '', $contents, -1, $count);

            if($count > 0)
            {
                file_put_contents($htaccess, $clean_contents);
            }
        }
	}

	protected function installEsiTemplate($package)
	{
        $app = JFactory::getApplication();

        $template_path = JPATH_ROOT . '/templates/esitemplate' ;       
        if (JFolder::exists( $template_path )){
            $app->enqueueMessage('esi template already exists, esi template installing ignored', 'warning');
            return;
        }

        $template_package = $package.'/esiTemplate';
        if (!JFolder::exists( $template_package )){
            $app->enqueueMessage('esi template package not exists, esi template installing ignored', 'warning');
            return;
        }

        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->delete($db->quoteName('#__extensions'))
        		->where($db->quoteName('type') . ' = '  . $db->quote('template'))
        		->where($db->quoteName('element') . ' = ' . $db->quote('esitemplate'));
        try {
            $db->setQuery($query)->execute();
        } catch (Exception $ex) {

        }
        
        $query = $db->getQuery(true);
        $query->delete($db->quoteName('#__template_styles'))
        		->where($db->quoteName('template') . ' = ' . $db->quote('esitemplate'));
        try {
            $db->setQuery($query)->execute();
        } catch (Exception $ex) {

        }

        $installer = new JInstaller;
        $result = $installer->install($template_package);
        
    }
    
}
