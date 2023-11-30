<?php

// No direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.controller');

class LSCacheController extends JControllerLegacy
{

	public function display($cachable = false, $urlparams = false)
	{
		JFactory::getApplication()->input->set('view','default'); // force it to be the search view

		return parent::display($cachable, $urlparams);
	}

}
