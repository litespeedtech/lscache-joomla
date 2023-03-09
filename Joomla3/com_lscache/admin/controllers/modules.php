<?php

/**
 *  @since      1.0.0
 *  @author     LiteSpeed Technologies <info@litespeedtech.com>
 *  @copyright  Copyright (c) 2017-2018 LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 *  @license    https://opensource.org/licenses/GPL-3.0
 */
defined('_JEXEC') or die;

use Joomla\Utilities\ArrayHelper;

/**
 * Modules list controller class.
 *
 * @since  0.1
 */
class LSCacheControllerModules extends JControllerAdmin {

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

        JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

        $pks = $this->input->post->get('cid', array(), 'array');
        $pks = ArrayHelper::toInteger($pks);

        try {
            if (empty($pks)) {
                throw new Exception(JText::_('COM_LSCACHE_ERROR_NO_MODULES_SELECTED'));
            }

            $model = $this->getModel();
            $model->renderESI($pks);
            $this->setMessage(count($pks) . JText::_('COM_LSCACHE_MODULES_RENDER_ESI'));
        } catch (Exception $e) {
            JError::raiseWarning(500, $e->getMessage());
        }

        $this->setRedirect('index.php?option=com_lscache');
    }

    public function normal() {
        JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

        $pks = $this->input->post->get('cid', array(), 'array');
        $pks = ArrayHelper::toInteger($pks);

        try {
            if (empty($pks)) {
                throw new Exception(JText::_('COM_LSCACHE_ERROR_NO_MODULES_SELECTED'));
            }

            $model = $this->getModel();
            $model->renderNormal($pks);
            $this->setMessage(count($pks) . JText::_('COM_LSCACHE_MODULES_RENDER_NORMAL'));
        } catch (Exception $e) {
            JError::raiseWarning(500, $e->getMessage());
        }

        $this->setRedirect('index.php?option=com_lscache');
    }

    public function purgeall() {
        JLoader::register('LiteSpeedCacheBase', JPATH_SITE . '/plugins/system/lscache/lscachebase.php', true);
        JLoader::register('LiteSpeedCacheCore', JPATH_SITE . '/plugins/system/lscache/lscachecore.php', true);
        $lscInstance = new LiteSpeedCacheCore();
        $app = JFactory::getApplication();
        $app->enqueueMessage(JText::_('COM_LSCACHE_PURGED_ALL'), "");
        $lscInstance->purgeAllPublic();
        $this->setRedirect('index.php?option=com_lscache');
    }

    public function purgelscache() {
        if (!isset($_SERVER['HTTP_REFERER'])) {
            return;
        }
        JLoader::register('LiteSpeedCacheBase', JPATH_SITE . '/plugins/system/lscache/lscachebase.php', true);
        JLoader::register('LiteSpeedCacheCore', JPATH_SITE . '/plugins/system/lscache/lscachecore.php', true);
        $lscInstance = new LiteSpeedCacheCore();
        $app = JFactory::getApplication();
        $app->enqueueMessage(JText::_('COM_LSCACHE_PURGED_ALL'), "");
        $lscInstance->purgeAllPublic();
        $this->setRedirect($_SERVER['HTTP_REFERER']);
    }

    public function rebuild() {
        $dispatcher = JEventDispatcher::getInstance();
        $dispatcher->trigger("onLSCacheRebuildAll");
        $settings = JComponentHelper::getParams('com_lscache');
        $secureWords = $settings->get('cleanCache');
        $this->setRedirect('index.php?option=com_lscache&recache='+$secureWords);
    }

    public function purgeModule() {

        JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

        $pks = $this->input->post->get('cid', array(), 'array');
        $pks = ArrayHelper::toInteger($pks);

        try {
            if (empty($pks)) {
                throw new Exception(JText::_('COM_LSCACHE_ERROR_NO_MODULES_SELECTED'));
            }

            $dispatcher = JEventDispatcher::getInstance();
            $dispatcher->trigger("onContentChangeState", array('com_modules.module', $pks, true));
        } catch (Exception $e) {
            JError::raiseWarning(500, $e->getMessage());
        }

        $this->setRedirect('index.php?option=com_lscache');
    }

    public function purgeURL() {

        JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));
        if (!isset($_POST['purgeURLs'])) {
            $app = JFactory::getApplication();
            $app->enqueueMessage(JText::_('COM_LSCACHE_ERROR_NO_URLS_INPUT'));
            $this->setRedirect('index.php?option=com_lscache');
            return;
        }

        $url = $_POST['purgeURLs'];
        if (empty($url)) {
            $app = JFactory::getApplication();
            $app->enqueueMessage(JText::_('COM_LSCACHE_ERROR_NO_URLS_INPUT'));
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

        $domain = JURI::getinstance()->toString(['host']);
        $host = $_SERVER['SERVER_ADDR'];
        $server = JURI::getinstance()->toString(['host', 'port']);
        $header = ['Host: ' . $server];
        $msg = [];

        foreach ($slugs as $key => $path) {

            // Check that URL is in this domain
            if (strpos($path, $domain) === FALSE) {
                $msg[] = $path . ' - ' . JText::_('COM_LSCACHE_URL_WRONG_DOMAIN');
                continue;
            }

            $ch = curl_init();

            // Replace domain with host, and set Header Host, to support Cloudflare or reverse proxies
            $host_path = str_replace($domain, $host, $path);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

            curl_setopt($ch, CURLOPT_URL, $host_path);
            curl_setopt($ch, CURLOPT_VERBOSE, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 3);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PURGE");
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $buffer = curl_exec($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if (in_array($httpcode, $acceptCode)) {
                $success++;
            } else {
                $msg[] = $path . ' - ' . JText::_('COM_LSCACHE_URL_PURGE_FAIL') . $httpcode . curl_error($ch);
            }

            curl_close($ch);
        }

        $msg[] = str_replace('%d', $success, JText::_('COM_LSCACHE_URL_PURGED'));
        $app = JFactory::getApplication();
        $app->enqueueMessage(implode("<br>", $msg));

        return true;
    }

}
