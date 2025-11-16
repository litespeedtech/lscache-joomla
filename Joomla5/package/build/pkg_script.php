<?php

/* 
 *  @since      1.2.0
 *  @author     LiteSpeed Technologies <info@litespeedtech.com>
 *  @copyright  Copyright (c) 2017-2018 LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 *  @license    https://opensource.org/licenses/GPL-3.0
 */
// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Application\AdministratorApplication;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScriptInterface;
use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Folder;
use Joomla\Filesystem\Path;
use Joomla\Filesystem\Exception\FilesystemException;


class Pkg_LSCacheInstallerScript implements InstallerScriptInterface {

    private AdministratorApplication $app;
    private DatabaseInterface $db;
    private bool $uninstalling = false;

    public function __construct(AdministratorApplication $app, DatabaseInterface $db)
    {
        $this->app = $app;
        $this->db  = $db;
    }

    public function install(InstallerAdapter $parent): bool
    {
        $this->lscacheEnable();

        $this->app->enqueueMessage('Successful installed.');

        return true;
    }

    public function update(InstallerAdapter $parent): bool
    {
        $this->lscacheEnable();

        $this->app->enqueueMessage('Successful updated.');

        return true;
    }

    public function uninstall(InstallerAdapter $parent): bool
    {
        $db = $this->db;
        $this->clearHtaccess();

        $this->uninstalling = true;

        $query = $db->createQuery();
        $query->delete($db->quoteName('#__extensions'))
                ->where($db->quoteName('element') . ' = ' . $db->quote('lscache'))
                ->where($db->quoteName('type') . ' = ' . $db->quote('package'));
        try {
            $db->setQuery($query)->execute();
        } catch (RuntimeException $ex) {
            $app->enqueueMessage($ex->getMessage(), 'error');
        }        

        $this->app->enqueueMessage('Successful uninstalled.');

        return true;
    }

    public function preflight(string $type, InstallerAdapter $parent): bool
    {
        $app = $this->app;
        $minimum_version = '5.0.0';

        $package_path = JPATH_ROOT . '/administrator/manifests/packages' ;       
        if (!Folder::exists( $package_path )){
            Folder::create($package_path);
        }

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

        return true;
    }

    public function postflight(string $type, InstallerAdapter $parent): bool
    {
        $db = $this->db;
        $app = $this->app;

        if($this->uninstalling){
            $query = $db->createQuery();
            $query->select($db->quoteName('extension_id'));
            $query->from($db->quoteName('#__extensions'));
            $query->where($db->quoteName('type') . ' = ' . $db->Quote('module'));
            $query->where($db->quoteName('element') . ' = ' . $db->Quote('mod_lscache_purge'));
            $db->setQuery($query, 0, 1);
            $id = $db->loadResult();
            if ($id)
            {
                Installer::getInstance()->uninstall('module',$id);    
            }
            return true;   
        }

        $package = $parent->getParent()->getPath('source');
        $template_path = JPATH_ROOT . '/templates/esitemplate' ;       
        if (Folder::exists( $template_path )){
            $app->enqueueMessage('esi template already exists, esi template installing ignored', 'warning');
            return true;
        }

        $template_package = $package.'/esiTemplate';
        if (!Folder::exists( $template_package )){
            $app->enqueueMessage('esi template package not exists, esi template installing ignored', 'warning');
            return true;
        }

        $query = $db->createQuery();
        $query->delete($db->quoteName('#__extensions'))
                ->where($db->quoteName('type') . ' = '  . $db->quote('template'))
                ->where($db->quoteName('element') . ' = ' . $db->quote('esitemplate'));
        try {
            $db->setQuery($query)->execute();
        } catch (Exception $ex) {

        }
        
        $query = $db->createQuery();
        $query->delete($db->quoteName('#__template_styles'))
                ->where($db->quoteName('template') . ' = ' . $db->quote('esitemplate'));
        try {
            $db->setQuery($query)->execute();
        } catch (Exception $ex) {

        }

        $result = Installer::getInstance()->install($template_package);
        
        if (function_exists('opcache_reset')){
            opcache_reset();
        } else if (function_exists('phpopcache_reset')){
            phpopcache_reset();
        }
        return true;
    }

    protected function lscacheEnable()
    {
        $db = $this->db;
        $app = $this->app;

        $query = $db->createQuery();
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

        $query = $db->createQuery();
        $query->update($db->quoteName('#__extensions'))
                ->set('enabled=1')
                ->where($db->quoteName('element') . ' = ' . $db->quote('com_lscache'))
                ->where($db->quoteName('type') . ' = ' . $db->quote('component'));
        try {
            $db->setQuery($query)->execute();
        } catch (RuntimeException $ex) {
            $app->enqueueMessage($ex->getMessage(), 'error');
        }

        $query = $db->createQuery();
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

        $query = $db->createQuery();
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

        $query = $db->createQuery();
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

        // disable gzip
        $data = Factory::getConfig();
        if($data["gzip"]!='0'){
            $data["gzip"] = '0';
            $config = new Registry($data);
            // Attempt to write the configuration file as a PHP class named JConfig.
            $configuration = $config->toString('PHP', array('class' => 'JConfig', 'closingtag' => false));
            $file = JPATH_CONFIGURATION . '/configuration.php';
            try {
                if (!Path::setPermissions($file, '0644') || !File::write($file, $configuration))
                {
                    $app->enqueueMessage('Fail to write configuration file to disable gzip', 'error');
                } else {
                    Path::setPermissions($file, '0444');
                }   
            } catch (\FilesystemException $e) {
                echo Text::sprintf('FILES_JOOMLA_ERROR_FILE_FOLDER', $file) . '<br>';
            }
        }

        $query = $db->createQuery();
        try {
            $query->update($db->quoteName('#__virtuemart_configs'))
                ->set("config = replace ( config, 'enable_content_plugin=" . '"0"' . "', 'enable_content_plugin=". '"1"' ."')")
                ->where($db->quoteName('virtuemart_config_id') . ' = 1' );
            $db->setQuery($query)->execute();
        } catch (Exception $ex) {
            error_log($ex->getMessage());
        }
            
        //Add module
        $query = $db->createQuery();
        $query->select($db->quoteName('id'))
            ->from('#__modules')
            ->where($db->quoteName('module') . ' = ' . $db->quote('mod_lscache_purge'))
            ->where($db->quoteName('client_id') . ' = 1');                        
        $db->setQuery($query, 0, 1);
        $id = $db->loadResult();

        if ( ! $id)
        {
            return;
        }

        $query = $db->createQuery();
        $query->update($db->quoteName('#__modules'))
            ->set($db->quoteName('published') . ' = 1')
            ->set($db->quoteName('ordering') . ' = 1' )
            ->set($db->quoteName('access') . ' = 3' )
            ->set($db->quoteName('position') . ' = ' . $db->quote('status'))
            ->where($db->quoteName('id') . ' = ' . (int)$id);
        try {
            $db->setQuery($query)->execute();
        } catch (Exception $ex) {
            $app->enqueueMessage($ex->getMessage(), 'error');
        }

        $query = $db->createQuery();
        $query->select($db->quoteName('moduleid'))
            ->from('#__modules_menu')
            ->where($db->quoteName('moduleid') . ' = ' . (int) $id);
        $db->setQuery($query, 0, 1)->execute();
        $exists = $db->loadResult();
        if ($exists)
        {
            return;
        }

        $query = $db->createQuery();
        $query->insert('#__modules_menu')
            ->columns([$db->quoteName('moduleid'), $db->quoteName('menuid')])
            ->values((int) $id . ', 0');
        try {
            $db->setQuery($query)->execute();
        } catch (Exception $ex) {
            $app->enqueueMessage($ex->getMessage(), 'error');
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

}


return new class () implements ServiceProviderInterface {
  public function register(Container $container)
  {
    $container->set(
      InstallerScriptInterface::class,
      new Pkg_LSCacheInstallerScript (
      $container->get(AdministratorApplication::class),
      $container->get(DatabaseInterface::class)
      ));
  }
};