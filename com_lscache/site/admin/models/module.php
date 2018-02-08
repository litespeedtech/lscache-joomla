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
class LSCacheModelModule extends JModelAdmin
{
	public function getTable($type = 'Module', $prefix = 'JTable', $config = array())
	{
		return JTable::getInstance($type, $prefix, $config);
	}
    
    
    public function renderESI(array $pks){
		$dispatcher = JEventDispatcher::getInstance();
		$user       = JFactory::getUser();
		$table      = $this->getTable();
        
        if(!$user->authorise('core.admin', 'com_lscache'))
        {
            JError::raiseWarning(403, JText::_('COM_LSCACHE_TASK_NOT_ALLOWED'));
            return;
        }

		foreach ($pks as $pk)
		{
			if (!$table->load($pk))
			{
				throw new Exception($table->getError());
			}

            $db    = $this->getDbo();
            $query = $db->getQuery(true)
                ->update('#__modules')
                ->set($db->quoteName('lscache_type') . ' = 1')
                ->where('id=' . (int) $pk);

            $db->setQuery($query);
            $db->execute();

            // Trigger the after delete event.
            $dispatcher->trigger("onExtensionAfterSave", array("com_modules.module", $table, false));
        }
        parent::cleanCache($table->module, $table->client_id);
        $this->cleanCache();

        return true;
    }
    
    public function renderNormal($pks){
		$user       = JFactory::getUser();
		$table      = $this->getTable();
        
        if(!$user->authorise('core.admin', 'com_lscache'))
        {
            JError::raiseWarning(403, JText::_('COM_LSCACHE_TASK_NOT_ALLOWED'));
            return;
        }

		foreach ($pks as $pk)
		{
			if (!$table->load($pk))
			{
				throw new Exception($table->getError());
			}

            $db    = $this->getDbo();
            $query = $db->getQuery(true)
                ->update('#__modules')
                ->set($db->quoteName('lscache_type') . ' = 0')
                ->where('id=' . (int) $pk);

            $db->setQuery($query);
            $db->execute();
        }
        parent::cleanCache($table->module, $table->client_id);
        $this->cleanCache();

        return true;
    }

	public function getForm($data = array(), $loadData = true)
	{
        return false;
    }    
}
