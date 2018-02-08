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
class LSCacheControllerModules extends JControllerAdmin
{
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
	public function getModel($name = 'Module', $prefix = 'LSCacheModel', $config = array('ignore_request' => true))
	{
		return parent::getModel($name, $prefix, $config);
	}
    
    public function esi(){

		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		$pks = $this->input->post->get('cid', array(), 'array');
		$pks = ArrayHelper::toInteger($pks);

		try
		{
			if (empty($pks))
			{
				throw new Exception(JText::_('COM_LSCACHE_ERROR_NO_MODULES_SELECTED'));
			}

			$model = $this->getModel();
			$model->renderESI($pks);
			$this->setMessage(count($pks) .JText::_('COM_LSCACHE_MODULES_CACHE_ESI'));
		}
		catch (Exception $e)
		{
			JError::raiseWarning(500, $e->getMessage());
		}

		$this->setRedirect('index.php?option=com_lscache');        

    }
    

    public function normal(){
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		$pks = $this->input->post->get('cid', array(), 'array');
		$pks = ArrayHelper::toInteger($pks);

		try
		{
			if (empty($pks))
			{
				throw new Exception(JText::_('COM_LSCACHE_ERROR_NO_MODULES_SELECTED'));
			}

			$model = $this->getModel();
			$model->renderNormal($pks);
			$this->setMessage(count($pks) .JText::_('COM_LSCACHE_MODULES_CACHE_NORMAL'));
		}
		catch (Exception $e)
		{
			JError::raiseWarning(500, $e->getMessage());
		}

		$this->setRedirect('index.php?option=com_lscache');        
    }
    
    
    
    public function purgeall(){
        JLoader::registerPrefix('LiteSpeedCacheCore', JPATH_ROOT . '/plugins/system/lscache/lscachebase.php', true);
        JLoader::registerPrefix('LiteSpeedCacheBase', JPATH_ROOT . '/plugins/system/lscache/lscachebase.php', true);
        $lscInstance = new LiteSpeedCacheCore();
        $app = JFactory::getApplication();
        $app->enqueueMessage(JText::_('COM_LSCACHE_PURGED_ALL'), "");
		$this->setRedirect('index.php?option=com_lscache');        
        $app->setHeader($lscInstance::CACHE_PURGE,"public,",$lscInstance->getSiteOnlyTag());
    }
    
    
}
