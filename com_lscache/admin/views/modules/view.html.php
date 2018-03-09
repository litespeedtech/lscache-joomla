<?php
/**
 *  @since      1.0.0
 *  @author     LiteSpeed Technologies <info@litespeedtech.com>
 *  @copyright  Copyright (c) 2017-2018 LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 *  @license    https://opensource.org/licenses/GPL-3.0
 */

defined('_JEXEC') or die;

/**
 * View class for a list of modules.
 *
 * @since  1.6
 */
class LSCacheViewModules extends JViewLegacy
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

		$this->addToolbar();

		// Include the component HTML helpers.
		JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');

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
		$canDo = JHelperContent::getActions('com_lscache');
		$user  = JFactory::getUser();

		// Get the toolbar object instance
		$bar = JToolbar::getInstance('toolbar');

		JToolbarHelper::title(JText::_('COM_LSCACHE_TOOLBAR_TITLE'), 'flash');

		if ($canDo->get('core.admin'))
		{
            if($cacheType=="1"){
                JToolbarHelper::custom('modules.normal', 'unfeatured', 'loop', 'COM_LSCACHE_RENDER_NORMAL', true);
            }
            else{
                JToolbarHelper::custom('modules.esi', 'featured', 'loop', 'COM_LSCACHE_RENDER_ESI', true);
            }
            
			//JToolbarHelper::custom('modules.tag', 'tag', 'tag2', 'COM_LSCACHE_MODULE_TAG', true);
			//JToolbarHelper::custom('modules.purge', 'stack', 'stack', 'COM_LSCACHE_MODULE_PURGE', true);

    		$bar = JToolbar::getInstance('toolbar');
            $layout = new JLayoutFile('toolbar.purgeall');
			$bar->appendButton('Custom', $layout->render(array()));
            
			JToolbarHelper::preferences('com_lscache');
		}

		JToolbarHelper::help('JHELP_EXTENSIONS_MODULE_MANAGER');

		if (JHtmlSidebar::getEntries())
		{
			$this->sidebar = JHtmlSidebar::render();
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
					'ordering'       => JText::_('JGRID_HEADING_ORDERING'),
					'a.published'    => JText::_('JSTATUS'),
					'a.title'        => JText::_('JGLOBAL_TITLE'),
					'position'       => JText::_('COM_MODULES_HEADING_POSITION'),
					'name'           => JText::_('COM_MODULES_HEADING_MODULE'),
					'pages'          => JText::_('COM_MODULES_HEADING_PAGES'),
					'a.access'       => JText::_('JGRID_HEADING_ACCESS'),
					'language_title' => JText::_('JGRID_HEADING_LANGUAGE'),
					'a.id'           => JText::_('JGRID_HEADING_ID')
				);
			}

			return array(
				'a.title'        => JText::_('JGLOBAL_TITLE'),
				'position'       => JText::_('COM_MODULES_HEADING_POSITION'),
				'name'           => JText::_('COM_MODULES_HEADING_MODULE'),
				'pages'          => JText::_('COM_MODULES_HEADING_PAGES'),
				'a.access'       => JText::_('JGRID_HEADING_ACCESS'),
				'language_title' => JText::_('JGRID_HEADING_LANGUAGE'),
				'a.id'           => JText::_('JGRID_HEADING_ID')
			);
		}
		else
		{
			if ($this->getLayout() == 'default')
			{
				return array(
					'ordering'       => JText::_('JGRID_HEADING_ORDERING'),
					'a.published'    => JText::_('JSTATUS'),
					'a.title'        => JText::_('JGLOBAL_TITLE'),
					'position'       => JText::_('COM_MODULES_HEADING_POSITION'),
					'name'           => JText::_('COM_MODULES_HEADING_MODULE'),
					'a.access'       => JText::_('JGRID_HEADING_ACCESS'),
					'a.language'     => JText::_('JGRID_HEADING_LANGUAGE'),
					'a.id'           => JText::_('JGRID_HEADING_ID')
				);
			}

			return array(
					'a.title'        => JText::_('JGLOBAL_TITLE'),
					'position'       => JText::_('COM_MODULES_HEADING_POSITION'),
					'name'           => JText::_('COM_MODULES_HEADING_MODULE'),
					'a.access'       => JText::_('JGRID_HEADING_ACCESS'),
					'a.language'     => JText::_('JGRID_HEADING_LANGUAGE'),
					'a.id'           => JText::_('JGRID_HEADING_ID')
			);
		}
	}
}
