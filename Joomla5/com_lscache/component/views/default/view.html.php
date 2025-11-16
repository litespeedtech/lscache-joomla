<?php
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView;

// No direct access
defined('_JEXEC') or die;

class lscacheViewDefault extends HtmlView
{
	protected $params;

	public function display($tpl = null)
	{
		$app	= Factory::getApplication();
		$params = $app->getParams();
		$menus	= $app->getMenu();
		$menu	= $menus->getActive();

		if ($menu)
		{
			$params->set('page_heading', $params->get('page_title', $menu->title));
		}
		else
		{
			$params->set('page_title',	Text::_('LSCache Component'));
		}

		$title = $params->get('page_title');
		if ($app->getCfg('sitename_pagetitles', 0)) {
			$title = Text::sprintf('JPAGETITLE', $app->getCfg('sitename'), $title);
		}
		$this->document->setTitle($title);

		if ($params->get('menu-meta_description'))
		{
			$this->document->setDescription($params->get('menu-meta_description'));
		}

		if ($params->get('menu-meta_keywords')) 
		{
			$this->document->setMetadata('keywords', $params->get('menu-meta_keywords'));
		}

		if ($params->get('robots')) 
		{
			$this->document->setMetadata('robots', $params->get('robots'));
		}

		$this->params = $params;

		parent::display($tpl);
	}
}
