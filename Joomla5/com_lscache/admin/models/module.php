<?php

/**
 *  @since      1.0.0
 *  @author     LiteSpeed Technologies <info@litespeedtech.com>
 *  @copyright  Copyright (c) 2017-2018 LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 *  @license    https://opensource.org/licenses/GPL-3.0
 */
// No direct access to this file
defined('_JEXEC') or die;

/**
 * LSCache Module Model
 *
 * @since  0.0.1
 */
class LSCacheModelModule extends JModelAdmin {

    public function __construct($config = array()) {
        $config = array_merge(
                array(
                    'event_after_delete' => 'onExtensionAfterDelete',
                    'event_after_save' => 'onExtensionAfterSave',
                    'event_before_delete' => 'onExtensionBeforeDelete',
                    'event_before_save' => 'onExtensionBeforeSave',
                    'events_map' => array(
                        'save' => 'extension',
                        'delete' => 'extension'
                    )
                ), $config
        );
        parent::__construct($config);
    }

    public function getTable($type = 'Module', $prefix = 'LSCacheTable', $config = array()) {
        return JTable::getInstance($type, $prefix, $config);
    }

    public function renderESI(array $pks) {
        $app = JFactory::getApplication();
        $user = JFactory::getUser();

        if (!$user->authorise('core.admin', 'com_lscache')) {
            $app->enqueueMessage(JText::_('COM_LSCACHE_TASK_NOT_ALLOWED'), 'error');
            return;
        }

        foreach ($pks as $pk) {
            $db = $this->getDbo();
            $query = $db->getQuery(true)
                    ->insert($db->quoteName('#__modules_lscache'))
                    ->columns($db->quoteName(array('moduleid', 'lscache_type', 'lscache_ttl')))
                    ->values($pk . ', 1, 500');

            $db->setQuery($query);
            $db->execute();

            $table = $this->getTable();
            $table->moduleid = $pk;
            $app->triggerEvent("onExtensionAfterSave", array("com_lscache.module", $table, false));
        }

        $app = JFactory::getApplication();
        $app->setUserState("com_lscache.modules.lscache_type", '1');

        return true;
    }

    public function renderNormal($pks) {
        $app = JFactory::getApplication();
        $user = JFactory::getUser();

        if (!$user->authorise('core.admin', 'com_lscache')) {
            $app->enqueueMessage(JText::_('COM_LSCACHE_TASK_NOT_ALLOWED'), 'error');
            return;
        }

        foreach ($pks as $pk) {
            $db = $this->getDbo();
            $query = $db->getQuery(true)
                    ->delete('#__modules_lscache')
                    ->where('moduleid=' . (int) $pk);

            $db->setQuery($query);
            $db->execute();

            $table = $this->getTable();
            $table->moduleid = $pk;
            $app->triggerEvent("onExtensionAfterSave", array("com_lscache.module", $table, false));
        }

        $app->setUserState("com_lscache.modules.lscache_type", '0');
        
        return true;
    }

    public function getForm($data = array(), $loadData = true) {
        // Get the form.
        $form = $this->loadForm(
                'com_lscache.module',
                'module',
                array(
                    'control' => 'jform',
                    'load_data' => $loadData
                )
        );

        if (empty($form)) {
            return false;
        }

        return $form;
    }

    /**
     * Method to get the data that should be injected in the form.
     *
     * @return  mixed  The data for the form.
     *
     * @since   1.6
     */
    protected function loadFormData() {
        // Check the session for previously entered form data.
        $data = JFactory::getApplication()->getUserState(
                'com_lscache.default.module.data',
                array()
        );

        if (empty($data)) {
            $data = $this->getItem();
        }

        $db = $this->getDbo();
        $query = $db->getQuery(true)
                ->select('title')
                ->from('#__modules')
                ->where($db->quoteName('id') . '=' . (int) $data->moduleid);
        $db->setQuery($query);

        $title = $db->loadResult();
        $data->title = $title;
        return $data;
    }

    public function getItem($pk = null) {
        $pk = (!empty($pk)) ? (int) $pk : (int) $this->getState('module.id');

        $table = $this->getTable();

        if (!$table->load($pk)) {
            throw new Exception($table->getError());
        }

        return $table;
    }

}
