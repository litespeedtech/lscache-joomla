<?php
/**
 *  @since      1.0.0
 *  @author     LiteSpeed Technologies <info@litespeedtech.com>
 *  @copyright  Copyright (c) 2017-2018 LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 *  @license    https://opensource.org/licenses/GPL-3.0
 */

defined('_JEXEC') or die;

JHtml::_('bootstrap.tooltip');
JHtml::_('behavior.multiselect');
JHtml::_('formbehavior.chosen', 'select');

$clientId   = (int) $this->state->get('client_id', 0);
$user		= JFactory::getUser();
$listOrder	= $this->escape($this->state->get('list.ordering'));
$listDirn	= $this->escape($this->state->get('list.direction'));
$saveOrder	= ($listOrder == 'a.ordering');
if ($saveOrder)
{
	$saveOrderingUrl = 'index.php?option=com_lscache&task=modules.saveOrderAjax&tmpl=component';
	JHtml::_('sortablelist.sortable', 'moduleList', 'adminForm', strtolower($listDirn), $saveOrderingUrl);
}
$colSpan = $clientId === 1 ? 8 : 10;
?>
<form action="<?php echo JRoute::_('index.php?option=com_lscache'); ?>" method="post" name="adminForm" id="adminForm">
<?php if (!empty( $this->sidebar)) : ?>
	<div id="j-sidebar-container" class="span2">
		<?php echo $this->sidebar; ?>
	</div>
	<div id="j-main-container" class="span10">
<?php else : ?>
	<div id="j-main-container">
<?php endif;?>
		<?php echo JLayoutHelper::render('joomla.searchtools.default', array('view' => $this, 'options' => array('selectorFieldName' => 'lscache_type'))); ?>
		<?php if ($this->total > 0) : ?>
			<table class="table table-striped" id="moduleList">
				<thead>
					<tr>
						<th width="1%" class="nowrap center hidden-phone">
							<?php echo JHtml::_('searchtools.sort', '', 'a.ordering', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING', 'icon-menu-2'); ?>
						</th>
						<th width="1%" class="nowrap center">
							<?php echo JHtml::_('grid.checkall'); ?>
						</th>
						<th width="1%" class="nowrap center" style="min-width:55px">
							<?php echo JHtml::_('searchtools.sort', 'JSTATUS', 'a.published', $listDirn, $listOrder); ?>
						</th>
						<th class="title">
							<?php echo JHtml::_('searchtools.sort', 'JGLOBAL_TITLE', 'a.title', $listDirn, $listOrder); ?>
						</th>
						<th width="15%" class="nowrap hidden-phone">
							<?php echo JHtml::_('searchtools.sort', 'COM_MODULES_HEADING_POSITION', 'a.position', $listDirn, $listOrder); ?>
						</th>
						<th width="10%" class="nowrap hidden-phone hidden-tablet">
							<?php echo JHtml::_('searchtools.sort', 'COM_MODULES_HEADING_MODULE', 'name', $listDirn, $listOrder); ?>
						</th>
						<?php if ($clientId === 0) : ?>
						<th width="10%" class="nowrap hidden-phone hidden-tablet">
							<?php echo JHtml::_('searchtools.sort', 'COM_MODULES_HEADING_PAGES', 'pages', $listDirn, $listOrder); ?>
						</th>
						<?php endif; ?>
						<th width="10%" class="nowrap hidden-phone">
							<?php echo JHtml::_('searchtools.sort', 'JGRID_HEADING_ACCESS', 'ag.title', $listDirn, $listOrder); ?>
						</th>
						<?php if ($clientId === 0) : ?>
						<th width="10%" class="nowrap hidden-phone">
							<?php echo JHtml::_('searchtools.sort', 'JGRID_HEADING_LANGUAGE', 'l.title', $listDirn, $listOrder); ?>
						</th>
						<?php elseif ($clientId === 1 && JModuleHelper::isAdminMultilang()) : ?>
						<th width="10%" class="nowrap hidden-phone">
							<?php echo JHtml::_('searchtools.sort', 'JGRID_HEADING_LANGUAGE', 'a.language', $listDirn, $listOrder); ?>
						</th>
						<?php endif; ?>
						<th width="1%" class="nowrap center hidden-phone">
							<?php echo JHtml::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
						</th>
					</tr>
				</thead>
				<tfoot>
					<tr>
						<td colspan="<?php echo $colSpan; ?>">
							<?php echo $this->pagination->getListFooter(); ?>
						</td>
					</tr>
				</tfoot>
				<tbody>
				<?php foreach ($this->items as $i => $item) :
					$ordering   = ($listOrder == 'a.ordering');
					$canCheckin = false;
					$canChange  = false;
                    $canViewESI = false; //$user->authorise('core.manage');
				?>
					<tr class="row<?php echo $i % 2; ?>" sortable-group-id="<?php echo $item->position ?: 'none'; ?>">
						<td class="order nowrap center hidden-phone">
							<?php
							$iconClass = '';
							if (!$canChange)
							{
								$iconClass = ' inactive';
							}
							elseif (!$saveOrder)
							{
								$iconClass = ' inactive tip-top hasTooltip" title="' . JHtml::_('tooltipText', 'JORDERINGDISABLED');
							}
							?>
							<span class="sortable-handler<?php echo $iconClass; ?>">
								<span class="icon-menu"></span>
							</span>
							<?php if ($canChange && $saveOrder) : ?>
								<input type="text" style="display:none" name="order[]" size="5" value="<?php echo $item->ordering; ?>" class="width-20 text-area-order" />
							<?php endif; ?>
						</td>
						<td class="center">
							<?php if ($item->enabled > 0) : ?>
								<?php echo JHtml::_('grid.id', $i, $item->id); ?>
							<?php endif; ?>
						</td>
						<td class="center">
							<div class="btn-group">
							<?php // Check if extension is enabled ?>
							<?php if ($item->enabled > 0) : ?>
								<?php echo JHtml::_('jgrid.published', $item->published, $i, 'modules.', $canChange, 'cb', $item->publish_up, $item->publish_down); ?>
							<?php else : ?>
								<?php // Extension is not enabled, show a message that indicates this. ?>
								<button class="btn btn-micro hasTooltip" title="<?php echo JText::_('COM_MODULES_MSG_MANAGE_EXTENSION_DISABLED'); ?>">
									<span class="icon-ban-circle" aria-hidden="true"></span>
								</button>
							<?php endif; ?>
							</div>
						</td>
						<td class="has-context">
							<div class="pull-left">
								<?php if ($item->checked_out) : ?>
									<?php echo JHtml::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'modules.', $canCheckin); ?>
								<?php endif; ?>
								<?php if ($canViewESI) : ?>
									<a class="hasTooltip" href="<?php echo JRoute::_('index.php?option=com_lscache&view=esi&moduleid=' . (int) $item->id); ?>" title="<?php echo JText::_('COM_LSCACHE_MODULES_ESI_TIPS'); ?>">
									<?php echo $this->escape($item->title); ?></a>
								<?php else : ?>
									<?php echo $this->escape($item->title); ?>
								<?php endif; ?>

								<?php if (!empty($item->note)) : ?>
									<div class="small">
										<?php echo JText::sprintf('JGLOBAL_LIST_NOTE', $this->escape($item->note)); ?>
									</div>
								<?php endif; ?>
							</div>
						</td>
						<td class="small hidden-phone">
							<?php if ($item->position) : ?>
								<span class="label label-info">
									<?php echo $item->position; ?>
								</span>
							<?php else : ?>
								<span class="label">
									<?php echo JText::_('JNONE'); ?>
								</span>
							<?php endif; ?>
						</td>
						<td class="small hidden-phone hidden-tablet">
							<?php echo $item->name; ?>
						</td>
						<?php if ($clientId === 0) : ?>
						<td class="small hidden-phone hidden-tablet">
							<?php echo $item->pages; ?>
						</td>
						<?php endif; ?>
						<td class="small hidden-phone">
							<?php echo $this->escape($item->access_level); ?>
						</td>
						<?php if ($clientId === 0) : ?>
						<td class="small hidden-phone">
							<?php echo JLayoutHelper::render('joomla.content.language', $item); ?>
						</td>
						<?php elseif ($clientId === 1 && JModuleHelper::isAdminMultilang()) : ?>
							<td class="small hidden-phone">
								<?php if ($item->language == ''):?>
									<?php echo JText::_('JUNDEFINED'); ?>
								<?php elseif ($item->language == '*'):?>
									<?php echo JText::alt('JALL', 'language'); ?>
								<?php else:?>
									<?php echo $this->escape($item->language); ?>
								<?php endif; ?>
							</td>
						<?php endif; ?>
						<td class="hidden-phone">
							<?php echo (int) $item->id; ?>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

		<input type="hidden" name="task" value="" />
		<input type="hidden" name="boxchecked" value="0" />
		<?php echo JHtml::_('form.token'); ?>
	</div>
</form>
