<?php

/**
 *  @since      1.0.0
 *  @author     LiteSpeed Technologies <info@litespeedtech.com>
 *  @copyright  Copyright (c) 2017-2018 LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 *  @license    https://opensource.org/licenses/GPL-3.0
 */
defined('_JEXEC') or die;

use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

/**
 * Modules list controller class.
 *
 * @since  0.1
 */
class LSCacheControllerModules extends AdminController {

    /**
     * Method to get a model object, loading it if required.
     *
     * @param   string  $name    The model name. Optional.
     * @param   string  $prefix  The class prefix. Optional.
     * @param   array   $config  Configuration array for model. Optional.
     *
     * @return  object  The model.
     *
     * @since   1.6
     */
    public function getModel($name = 'Module', $prefix = 'LSCacheModel', $config = array('ignore_request' => true)) {
        return parent::getModel($name, $prefix, $config);
    }

    public function esi() {

        if (!Session::checkToken()) {
            Factory::getApplication()->enqueueMessage(Text::_('JINVALID_TOKEN'), 'error');
            Factory::getApplication()->close(403);
        }

        $pks = $this->input->post->get('cid', array(), 'array');
        $pks = ArrayHelper::toInteger($pks);

        try {
            if (empty($pks)) {
                throw new Exception(Text::_('COM_LSCACHE_ERROR_NO_MODULES_SELECTED'));
            }

            $model = $this->getModel();
            $model->renderESI($pks);
            $this->setMessage(count($pks) . Text::_('COM_LSCACHE_MODULES_RENDER_ESI'));
        } catch (Exception $e) {
            $application = Factory::getApplication();
            $application->enqueueMessage($e->getMessage(), 'error');
        }

        $this->setRedirect('index.php?option=com_lscache');
    }

    public function normal() {
        if (!Session::checkToken()) {
            Factory::getApplication()->enqueueMessage(Text::_('JINVALID_TOKEN'), 'error');
            Factory::getApplication()->close(403);
        }

        $pks = $this->input->post->get('cid', array(), 'array');
        $pks = ArrayHelper::toInteger($pks);

        try {
            if (empty($pks)) {
                throw new Exception(Text::_('COM_LSCACHE_ERROR_NO_MODULES_SELECTED'));
            }

            $model = $this->getModel();
            $model->renderNormal($pks);
            $this->setMessage(count($pks) . Text::_('COM_LSCACHE_MODULES_RENDER_NORMAL'));
        } catch (Exception $e) {
            $application = Factory::getApplication();
            $application->enqueueMessage($e->getMessage(), 'error');
        }

        $this->setRedirect('index.php?option=com_lscache');
    }

    public function purgeall() {
        require_once JPATH_SITE . '/plugins/system/lscache/lscachebase.php';
        require_once JPATH_SITE . '/plugins/system/lscache/lscachecore.php';
        $lscInstance = new LiteSpeedCacheCore();
        $app = Factory::getApplication();
        $app->enqueueMessage(Text::_('COM_LSCACHE_PURGED_ALL'), "");
        $lscInstance->purgeAllPublic();
        $this->setRedirect('index.php?option=com_lscache');
    }

    public function purgelscache() {
        if (!isset($_SERVER['HTTP_REFERER'])) {
            return;
        }
        require_once JPATH_SITE . '/plugins/system/lscache/lscachebase.php';
        require_once JPATH_SITE . '/plugins/system/lscache/lscachecore.php';
        $lscInstance = new LiteSpeedCacheCore();
        $app = Factory::getApplication();
        $app->enqueueMessage(Text::_('COM_LSCACHE_PURGED_ALL'), "");
        $lscInstance->purgeAllPublic();
        $this->setRedirect($_SERVER['HTTP_REFERER']);
    }

    public function rebuild() {
        $app = Factory::getApplication();
        $app->triggerEvent("onLSCacheRebuildAll");
    }

    public function purgeModule() {

        $app = Factory::getApplication();
        if (!Session::checkToken()) {
            $app->enqueueMessage(Text::_('JINVALID_TOKEN'), 'error');
            $app->close(403);
        }

        $pks = $this->input->post->get('cid', array(), 'array');
        $pks = ArrayHelper::toInteger($pks);

        try {
            if (empty($pks)) {
                throw new Exception(Text::_('COM_LSCACHE_ERROR_NO_MODULES_SELECTED'));
            }
            $app->triggerEvent("onContentChangeState", array('com_modules.module', $pks, true));
        } catch (Exception $e) {
            $application = Factory::getApplication();
            $application->enqueueMessage($e->getMessage(), 'error');
        }

        $this->setRedirect('index.php?option=com_lscache');
    }

    public function purgeURL() {

        if (!Session::checkToken()) {
            Factory::getApplication()->enqueueMessage(Text::_('JINVALID_TOKEN'), 'error');
            Factory::getApplication()->close(403);
        }
        if (!isset($_POST['purgeURLs'])) {
            $app = Factory::getApplication();
            $app->enqueueMessage(Text::_('COM_LSCACHE_ERROR_NO_URLS_INPUT'));
            $this->setRedirect('index.php?option=com_lscache');
            return;
        }

        $url = $_POST['purgeURLs'];
        if (empty($url)) {
            $app = Factory::getApplication();
            $app->enqueueMessage(Text::_('COM_LSCACHE_ERROR_NO_URLS_INPUT'));
            $this->setRedirect('index.php?option=com_lscache');
            return;
        }
        $urls = explode("\n", str_replace(array("\r\n", "\r"), "\n", $url));
        $this->purgeURLs($urls);
        $this->setRedirect('index.php?option=com_lscache');
    }

    private function purgeURLs($slugs) {
        $success = 0;
        $acceptCode = array(200, 201);

        $domain = Uri::getInstance()->toString(['host']);
        $host = $_SERVER['SERVER_ADDR'];
        $server = Uri::getInstance()->toString(['host', 'port']);
        $header = ['Host: ' . $server];
        $msg = [];

        // Build list of valid URLs and their original paths for reporting
        $validPaths = [];
        foreach ($slugs as $path) {
            if (strpos($path, $domain) === false) {
                $msg[] = $path . ' - ' . Text::_('COM_LSCACHE_URL_WRONG_DOMAIN');
                continue;
            }
            $validPaths[] = $path;
        }

        if (!empty($validPaths)) {
            // Send all PURGE requests in parallel using curl_multi
            $mh = curl_multi_init();
            $handles = [];

            foreach ($validPaths as $path) {
                // Replace domain with host IP to support Cloudflare or reverse proxies
                $host_path = str_replace($domain, $host, $path);
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
                curl_setopt($ch, CURLOPT_URL, $host_path);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_TIMEOUT, 3);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PURGE');
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_multi_add_handle($mh, $ch);
                $handles[$path] = $ch;
            }

            // Execute all handles simultaneously
            do {
                $status = curl_multi_exec($mh, $running);
                if ($running) {
                    curl_multi_select($mh);
                }
            } while ($running && $status === CURLM_OK);

            // Collect results
            foreach ($handles as $path => $ch) {
                $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                if (in_array($httpcode, $acceptCode)) {
                    $success++;
                } else {
                    $msg[] = $path . ' - ' . Text::_('COM_LSCACHE_URL_PURGE_FAIL') . $httpcode . curl_error($ch);
                }
                curl_multi_remove_handle($mh, $ch);
            }

            curl_multi_close($mh);
        }

        $msg[] = str_replace('%d', $success, Text::_('COM_LSCACHE_URL_PURGED'));
        $app = Factory::getApplication();
        $app->enqueueMessage(implode("<br>", $msg));

        return true;
    }

}
