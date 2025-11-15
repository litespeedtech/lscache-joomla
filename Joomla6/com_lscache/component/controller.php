<?php
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;

// No direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.controller');

class LSCacheController extends BaseController
{

	public function display($cachable = false, $urlparams = false)
	{
		Factory::getApplication()->input->set('view','default'); // force it to be the search view

		return parent::display($cachable, $urlparams);
	}

}
