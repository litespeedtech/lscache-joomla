<?php
/**
 *  @since      1.0.0
 *  @author     LiteSpeed Technologies <info@litespeedtech.com>
 *  @copyright  Copyright (c) 2017-2018 LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 *  @license    https://opensource.org/licenses/GPL-3.0
 */

defined('_JEXEC') or die;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\HTML\Helpers\Sidebar;
use Joomla\CMS\HTML\HTMLHelper;
/**
 * View class for a list of modules.
 *
 * @since  1.6
 */
class LSCacheViewModules extends HtmlView
{
	protected $items;

	protected $pagination;

	protected $state;

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise an Error object.
	 *
	 * @since   1.6
	 */
	public function display($tpl = null)
	{
		$this->items         = $this->get('Items');
		$this->pagination    = $this->get('Pagination');
		$this->state         = $this->get('State');
		$this->total         = $this->get('Total');
		$this->filterForm    = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');
		$this->clientId      = $this->state->get('client_id');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors), 500);
		}

		// We do not need the Language filter when modules are not filtered
		if ($this->clientId == 1 && !JModuleHelper::isAdminMultilang())
		{
			unset($this->activeFilters['language']);
			$this->filterForm->removeField('language', 'filter');
		}

        if(isset( $_SERVER['LSWS_EDITION'] ) && strpos( $_SERVER['LSWS_EDITION'], 'Openlitespeed' ) === 0){
            $app = JFactory::getApplication();  
            $app->enqueueMessage(Text::_('COM_LSCACHE_MODULES_UPGRADE'));
        }
        
		$this->addToolbar();
		HTMLHelper::_('jquery.framework');


		// Include the component HTML helpers.
		HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');

		return parent::display($tpl);
	}

    
	/**
	 * Add the page title and toolbar.
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	protected function addToolbar()
	{
		$state = $this->get('State');
        $cacheType = $state->get('lscache_type');
		$canDo = ContentHelper::getActions('com_lscache');
		$user  = Factory::getUser();

	    $bar    = Toolbar::getInstance('toolbar');

		ToolbarHelper::title(Text::_('COM_LSCACHE_TOOLBAR_TITLE'), 'lsc-jml-icon-b');

		if ($canDo->get('core.admin'))
		{
            if($cacheType=="1"){
                ToolbarHelper::custom('modules.normal', 'unfeatured', 'loop', 'COM_LSCACHE_RENDER_NORMAL', true);
            }
            else{
                ToolbarHelper::custom('modules.esi', 'featured', 'loop', 'COM_LSCACHE_RENDER_ESI', true);
            }
            
            $layout = new FileLayout('toolbar.purgeall');
			$bar->appendButton('Custom', $layout->render(array()));

            ToolbarHelper::custom('modules.rebuild', 'flash', 'refresh','COM_LSCACHE_BTN_REBUILD',false);

            $layout = new FileLayout('toolbar.purgeurl');
			$dhtml = $layout->render(array());
			$bar->appendButton('Custom', $dhtml);

            ToolbarHelper::custom('modules.purgeModule', 'folder-minus', 'folder-remove','COM_LSCACHE_BTN_PURGE_MODULE',false);
            
			ToolbarHelper::preferences('com_lscache');
		}

		if (Sidebar::getEntries())
		{
			$this->sidebar = Sidebar::render();
		}
	}

	/**
	 * Returns an array of fields the table can be sorted by
	 *
	 * @return  array  Array containing the field name to sort by as the key and display text as value
	 *
	 * @since   3.0
	 */
	protected function getSortFields()
	{
		$this->state = $this->get('State');

		if ($this->state->get('client_id') == 0)
		{
			if ($this->getLayout() == 'default')
			{
				return array(
					'ordering'       => Text::_('JGRID_HEADING_ORDERING'),
					'a.published'    => Text::_('JSTATUS'),
					'a.title'        => Text::_('JGLOBAL_TITLE'),
					'position'       => Text::_('COM_MODULES_HEADING_POSITION'),
					'name'           => Text::_('COM_MODULES_HEADING_MODULE'),
					'pages'          => Text::_('COM_MODULES_HEADING_PAGES'),
					'a.access'       => Text::_('JGRID_HEADING_ACCESS'),
					'language_title' => Text::_('JGRID_HEADING_LANGUAGE'),
					'a.id'           => Text::_('JGRID_HEADING_ID')
				);
			}

			return array(
				'a.title'        => Text::_('JGLOBAL_TITLE'),
				'position'       => Text::_('COM_MODULES_HEADING_POSITION'),
				'name'           => Text::_('COM_MODULES_HEADING_MODULE'),
				'pages'          => Text::_('COM_MODULES_HEADING_PAGES'),
				'a.access'       => Text::_('JGRID_HEADING_ACCESS'),
				'language_title' => Text::_('JGRID_HEADING_LANGUAGE'),
				'a.id'           => Text::_('JGRID_HEADING_ID')
			);
		}
		else
		{
			if ($this->getLayout() == 'default')
			{
				return array(
					'ordering'       => Text::_('JGRID_HEADING_ORDERING'),
					'a.published'    => Text::_('JSTATUS'),
					'a.title'        => Text::_('JGLOBAL_TITLE'),
					'position'       => Text::_('COM_MODULES_HEADING_POSITION'),
					'name'           => Text::_('COM_MODULES_HEADING_MODULE'),
					'a.access'       => Text::_('JGRID_HEADING_ACCESS'),
					'a.language'     => Text::_('JGRID_HEADING_LANGUAGE'),
					'a.id'           => Text::_('JGRID_HEADING_ID')
				);
			}

			return array(
					'a.title'        => Text::_('JGLOBAL_TITLE'),
					'position'       => Text::_('COM_MODULES_HEADING_POSITION'),
					'name'           => Text::_('COM_MODULES_HEADING_MODULE'),
					'a.access'       => Text::_('JGRID_HEADING_ACCESS'),
					'a.language'     => Text::_('JGRID_HEADING_LANGUAGE'),
					'a.id'           => Text::_('JGRID_HEADING_ID')
			);
		}
	}
}
