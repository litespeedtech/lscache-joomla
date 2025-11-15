<?php
/**
 *  @since      1.0.0
 *  @author     LiteSpeed Technologies <info@litespeedtech.com>
 *  @copyright  Copyright (c) 2017-2018 LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 *  @license    https://opensource.org/licenses/GPL-3.0
 */
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;


defined('_JEXEC') or die;

if (!Factory::getUser()->authorise('core.manage', 'com_lscache'))
{
	throw new JAccessExceptionNotallowed(JText::_('JERROR_ALERTNOAUTHOR'), 403);
}

$controller = BaseController::getInstance('LSCache');
$input = Factory::getApplication()->input;
$controller->execute($input->getCmd('task'));
$controller->redirect();
