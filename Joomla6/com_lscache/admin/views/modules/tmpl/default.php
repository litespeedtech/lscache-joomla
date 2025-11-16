<?php
/**
 *  @since      1.3.0
 *  @author     LiteSpeed Technologies <info@litespeedtech.com>
 *  @copyright  Copyright (c) 2017-2018 LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 *  @license    https://opensource.org/licenses/GPL-3.0
 */

defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.multiselect');
//HTMLHelper::_('formbehavior.chosen', 'select');

$clientId   = (int) $this->state->get('client_id', 0);
$user		= Factory::getUser();
$listOrder	= $this->escape($this->state->get('list.ordering'));
$listDirn	= $this->escape($this->state->get('list.direction'));
$esiRender	= $this->escape($this->state->get('lscache_type'));

$saveOrder	= ($listOrder == 'a.ordering');
if ($saveOrder)
{
	$saveOrderingUrl = 'index.php?option=com_lscache&task=modules.saveOrderAjax&tmpl=component';
	HTMLHelper::_('sortablelist.sortable', 'moduleList', 'adminForm', strtolower($listDirn), $saveOrderingUrl);
}
$colSpan = $clientId === 1 ? 8 : 10;
?>
<style type="text/css">
.icon-lsc-jml-icon-b {
	background-image: url(data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz48IURPQ1RZUEUgc3ZnIFBVQkxJQyAiLS8vVzNDLy9EVEQgU1ZHIDEuMS8vRU4iICJodHRwOi8vd3d3LnczLm9yZy9HcmFwaGljcy9TVkcvMS4xL0RURC9zdmcxMS5kdGQiPjxzdmcgdmVyc2lvbj0iMS4xIiBpZD0iTGF5ZXJfMSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vcmcvMTk5OS94bGluayIgeD0iMHB4IiB5PSIwcHgiIHdpZHRoPSIyNHB4IiBoZWlnaHQ9IjI0cHgiIHZpZXdCb3g9IjAgMCA0OCA0OCIgZW5hYmxlLWJhY2tncm91bmQ9Im5ldyAwIDAgNDggNDgiIHhtbDpzcGFjZT0icHJlc2VydmUiPjxnPjxwYXRoIGZpbGw9IiNGRkZGRkYiIGQ9Ik00NS4yOTgsNC4yODljLTAuMTAxLTAuNzc0LTAuNzk2LTEuNDgzLTEuNTg0LTEuNTg2TDMxLjYxMywwLjI1NWMtMC4wODgtMC4wMTItMC4xNzktMC4wMTgtMC4yNzItMC4wMThjLTAuNzQ5LDAtMS42NDQsMC4zNTEtMi4xMjcsMC44MzVMMTEuMjg3LDE4Ljk5OUwxNS4yODksMjNMMzEuOTYzLDYuMzI3YzAuMDk1LTAuMDk1LDAuMjIzLTAuMTQ2LDAuMzU0LTAuMTQ2YzAuMDMzLDAsMC4wNjYsMC4wMDMsMC4xLDAuMDFsNy40ODksMS41MTVjMC4xOTYsMC4wNCwwLjM1MSwwLjE5NCwwLjM5MSwwLjM5MWwxLjUxNiw3LjQ5YzAuMDMzLDAuMTY0LTAuMDE5LDAuMzM0LTAuMTM3LDAuNDUzbC0yLjk2MSwyLjk2bDQsNC4wMDJsNC4yMTUtNC4yMTRjMC41NTItMC41NTMsMC45MTUtMS42NDYsMC44MTYtMi40MDNMNDUuMjk4LDQuMjg5eiBNNDYuMjIyLDE4LjA4MWwtMy41MDgsMy41MDZsLTIuNTg2LTIuNTg4bDIuNDQzLTIuNDQzYzAuMjM4LTAuMjM5LDAuMzQtMC41NzcsMC4yNzMtMC45MDVMNDEuMjEsNy41NzNjLTAuMDgtMC4zOTUtMC4zODctMC43MDItMC43ODEtMC43ODJsLTguMDc3LTEuNjM1Yy0wLjAzMy0wLjAwNi0wLjE2NC0wLjAxOS0wLjE5Ny0wLjAxOWMtMC4yNywwLTAuNTIxLDAuMTA0LTAuNzEsMC4yOTNMMTUuMjg5LDIxLjU4NmwtMi41ODgtMi41ODdMMjkuOTIxLDEuNzhjMC4yOTQtMC4yOTMsMC45NDctMC41NDEsMS40MjgtMC41NDFjMC4wNSwwLDAuMDk5LDAuMDAyLDAuMTIsMC4wMDRMNDMuNTUsMy42ODhjMC4zNjIsMC4wNDksMC43MTcsMC40MTcsMC43NjIsMC43NjRsMi40NDcsMTIuMDk1QzQ2LjgxMywxNi45NzUsNDYuNTUxLDE3Ljc1LDQ2LjIyMiwxOC4wODF6Ii8+PHBhdGggZmlsbD0iI0ZGRkZGRiIgZD0iTTMyLjcxMSwyNS4wMDFMMTYuMDM3LDQxLjY3NWMtMC4wOTUsMC4wOTUtMC4yMjMsMC4xNDYtMC4zNTQsMC4xNDZjLTAuMDMzLDAtMC4wNjYtMC4wMDMtMC4xLTAuMDFsLTcuNDg4LTEuNTE2Yy0wLjE5Ni0wLjA0LTAuMzUxLTAuMTk0LTAuMzkxLTAuMzkxbC0xLjUxNi03LjQ4OWMtMC4wMzMtMC4xNjUsMC4wMTktMC4zMzUsMC4xMzctMC40NTNsMi45NjEtMi45NjJMNS4yODUsMjVsLTQuMjEzLDQuMjE0Yy0wLjU1MiwwLjU1Mi0wLjkxNiwxLjY0NS0wLjgxOCwyLjQwMmwyLjQ0NywxMi4wOTdjMC4xMDMsMC43NzQsMC43OTgsMS40ODMsMS41ODUsMS41ODVsMTIuMDk1LDIuNDQ3YzAuMDg3LDAuMDExLDAuMTc3LDAuMDE3LDAuMjcxLDAuMDE3YzAuNzUyLDAsMS42NDgtMC4zNTEsMi4xMzMtMC44MzRsMTcuOTI4LTE3LjkyN0wzMi43MTEsMjUuMDAxeiBNMTguMDc5LDQ2LjIyMWMtMC4yOTQsMC4yOTMtMC45NDgsMC41NDEtMS40MjgsMC41NDFjLTAuMDUsMC0wLjEtMC4wMDMtMC4xMjItMC4wMDVMNC40NSw0NC4zMTJjLTAuMzYyLTAuMDQ4LTAuNzE3LTAuNDE3LTAuNzYzLTAuNzY1TDEuMjQsMzEuNDUyYy0wLjA1NS0wLjQyNiwwLjIxLTEuMjAyLDAuNTM5LTEuNTMxbDMuNTA2LTMuNTA3bDIuNTg4LDIuNTg3TDUuNDMsMzEuNDQ1Yy0wLjIzNywwLjIzNy0wLjM0LDAuNTc2LTAuMjczLDAuOTA1bDEuNjM1LDguMDc4YzAuMDgxLDAuMzk1LDAuMzg3LDAuNywwLjc4MSwwLjc4MWw4LjA3NiwxLjYzNWMwLjA2NCwwLjAxMywwLjEzMiwwLjAyMSwwLjIsMC4wMjFjMC4yNjYsMCwwLjUxNy0wLjEwNCwwLjcwNi0wLjI5NGwxNi4xNTYtMTYuMTU2bDIuNTg4LDIuNTg2TDE4LjA3OSw0Ni4yMjF6Ii8+PGc+PHBhdGggZmlsbD0iI0ZGRkZGRiIgZD0iTTQ2LjkyOSwyOS4yMTRMMjkuMDAyLDExLjI4OEwyNSwxNS4yOWwxLjM4NCwxLjM4NGwwLjk3OC0wLjc1YzAuMjg5LTAuMjEzLDAuNTYzLTAuMzExLDAuODU1LTAuMzExYzAuNDYzLDAsMC44OTQsMC4yNjMsMS4xMDYsMC42NzNjMC4yMzUsMC40NDIsMC4xNzQsMC45Ny0wLjE2OCwxLjQxNmwtMC43NjksMC45NzVsMTMuNDc4LDEzLjQ3N2wtMS42MzUsOC4wNzdsLTguMDc3LDEuNjM1bC0zLjE1LTMuMTVsLTQuMDAyLDRsNC4yMTQsNC4yMTVjMC41NSwwLjU0OSwxLjYzMSwwLjkxOCwyLjQwMywwLjgxNmwxMi4wOTYtMi40NDdjMC43NzEtMC4xLDEuNDg0LTAuODE0LDEuNTg1LTEuNTg1bDIuNDQ4LTEyLjA5NkM0Ny44NDYsMzAuODQ2LDQ3LjQ3OCwyOS43NjUsNDYuOTI5LDI5LjIxNHoiLz48cGF0aCBmaWxsPSIjRkZGRkZGIiBkPSJNMTkuMzE3LDMyLjI3MmMtMC4yMjQtMC4wOS0wLjQ4OS0wLjI2Ny0wLjY0Ny0wLjU3NmMtMC4xMTUtMC4yMy0wLjMxNC0wLjgxMywwLjIxMS0xLjQ0M2wwLjczLTAuOTNMNi4xMzcsMTUuODQ5TDcuNzcsNy43NzJsOC4wNzgtMS42MzVsMy4xNSwzLjE1bDQtNC4wMDFsLTQuMjEzLTQuMjEzYy0wLjU0OS0wLjU1LTEuNjMxLTAuOTE4LTIuNDAyLTAuODE3TDQuMjg3LDIuNzAzQzMuNTE2LDIuODA0LDIuODAzLDMuNTE3LDIuNzAxLDQuMjg5TDAuMjU0LDE2LjM4NGMtMC4xLDAuNzcxLDAuMjY4LDEuODUyLDAuODE3LDIuNDAybDAuMDAxLDBsMTcuOTI2LDE3LjkyNWw0LTRsLTEuMzkxLTEuMzkzbC0wLjk3MiwwLjc0NkMyMC4wMTYsMzIuNTYyLDE5LjMxNywzMi4yNzIsMTkuMzE3LDMyLjI3MnoiLz48L2c+PHBhdGggZmlsbD0iI0ZGRkZGRiIgZD0iTTIwLjA4OCwzMS4zNTFsNi40MzYtNC45MzhjMC4zMDUtMC4yMzEsMC4yMTItMC43MzMsMC4xMjUtMC44NzlsLTIuMjE5LTMuMjA2Yy0wLjAxLTAuMDItMC4wMTQtMC4wNzIsMC0wLjA4OGw0LjAxMi01LjA4N2MwLjA2LTAuMDc4LDAuMTg4LTAuMjYyLDAuMDg1LTAuNDVjLTAuMTMtMC4yNjMtMC40MjQtMC4yMDktMC42MTctMC4wNjdsLTYuNDMzLDQuOTRjLTAuMjcxLDAuMjA0LTAuMzMyLDAuNjEtMC4xMzgsMC44ODFsMi4yMjksMy4yMTRjMC4wMTIsMC4wMTUsMC4wMTIsMC4wNjQsMCwwLjA4MWwtMy45OSw1LjA2OGMtMC4xNjEsMC4xODktMC4yMjIsMC41ODIsMC4xMTksMC42MzFDMTkuNjk3LDMxLjQ1MiwxOS44NzMsMzEuNDk3LDIwLjA4OCwzMS4zNTF6Ii8+PC9nPjwvc3ZnPg==);
    width: 24px ;
    height: 24px ;
    padding: 0 0 0 0;
    margin-right: 0 px;    
}
</style>
<form action="<?php echo Route::_('index.php?option=com_lscache'); ?>" method="post" name="adminForm" id="adminForm">
<?php if (!empty( $this->sidebar)) : ?>
	<div id="j-sidebar-container" class="span2">
		<?php echo $this->sidebar; ?>
	</div>
	<div id="j-main-container" class="span10">
<?php else : ?>
	<div id="j-main-container">
<?php endif;?>
		<?php echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this, 'options' => array('selectorFieldName' => 'lscache_type'))); ?>

		<?php if ($this->total > 0) : ?>
			<table class="table table-striped" id="moduleList">
				<thead>
					<tr>
						<th width="1%" class="nowrap center hidden-phone">
							<?php echo HTMLHelper::_('searchtools.sort', '', 'a.ordering', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING', 'icon-menu-2'); ?>
						</th>
						<th width="1%" class="nowrap center">
							<?php echo HTMLHelper::_('grid.checkall'); ?>
						</th>
						<th width="1%" class="nowrap center" style="min-width:55px">
							<?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'a.published', $listDirn, $listOrder); ?>
						</th>
						<th class="title">
							<?php echo HTMLHelper::_('searchtools.sort', 'JGLOBAL_TITLE', 'a.title', $listDirn, $listOrder); ?>
						</th>
                        <?php if($esiRender == "1") : ?>
						<th width="10%" class="nowrap hidden-phone hidden-tablet">
							<?php echo HTMLHelper::_('searchtools.sort', 'COM_LSCACHE_HEADING_CACHE_TYPE', 'm.lscache_type', $listDirn, $listOrder); ?>
						</th>
						<th width="10%" class="nowrap hidden-phone hidden-tablet">
							<?php echo HTMLHelper::_('searchtools.sort', 'COM_LSCACHE_HEADING_CACHE_TTL', 'm.lscache_ttl', $listDirn, $listOrder); ?>
						</th>
                        <?php endif;?>
						<th width="15%" class="nowrap hidden-phone">
							<?php echo HTMLHelper::_('searchtools.sort', 'COM_MODULES_HEADING_POSITION', 'a.position', $listDirn, $listOrder); ?>
						</th>
						<th width="10%" class="nowrap hidden-phone hidden-tablet">
							<?php echo HTMLHelper::_('searchtools.sort', 'COM_MODULES_HEADING_MODULE', 'name', $listDirn, $listOrder); ?>
						</th>
						<?php if ($clientId === 0) : ?>
						<th width="10%" class="nowrap hidden-phone hidden-tablet">
							<?php echo HTMLHelper::_('searchtools.sort', 'COM_MODULES_HEADING_PAGES', 'pages', $listDirn, $listOrder); ?>
						</th>
						<?php endif; ?>
						<th width="10%" class="nowrap hidden-phone">
							<?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ACCESS', 'ag.title', $listDirn, $listOrder); ?>
						</th>
						<?php if ($clientId === 0) : ?>
						<th width="10%" class="nowrap hidden-phone">
							<?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_LANGUAGE', 'l.title', $listDirn, $listOrder); ?>
						</th>
						<?php elseif ($clientId === 1 && JModuleHelper::isAdminMultilang()) : ?>
						<th width="10%" class="nowrap hidden-phone">
							<?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_LANGUAGE', 'a.language', $listDirn, $listOrder); ?>
						</th>
						<?php endif; ?>
						<th width="1%" class="nowrap center hidden-phone">
							<?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
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
                    $canEditESI = ( $user->authorise('core.manage') ) && ( $esiRender == "1" );
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
								$iconClass = ' inactive tip-top hasTooltip" title="' . HTMLHelper::_('tooltipText', 'JORDERINGDISABLED');
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
								<?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
							<?php endif; ?>
						</td>
						<td class="center">
							<div class="btn-group">
							<?php // Check if extension is enabled ?>
							<?php if ($item->enabled > 0) : ?>
								<?php echo HTMLHelper::_('jgrid.published', $item->published, $i, 'modules.', $canChange, 'cb', $item->publish_up, $item->publish_down); ?>
							<?php else : ?>
								<?php // Extension is not enabled, show a message that indicates this. ?>
								<button class="btn btn-micro hasTooltip" title="<?php echo Text::_('COM_MODULES_MSG_MANAGE_EXTENSION_DISABLED'); ?>">
									<span class="icon-ban-circle" aria-hidden="true"></span>
								</button>
							<?php endif; ?>
							</div>
						</td>
						<td class="has-context">
							<div class="pull-left">
								<?php if ($item->checked_out) : ?>
									<?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'modules.', $canCheckin); ?>
								<?php endif; ?>
								<?php if ($canEditESI) : ?>
									<a class="hasTooltip" href="<?php echo Route::_('index.php?option=com_lscache&view=module&moduleid=' . (int) $item->id); ?>" title="<?php echo Text::_('COM_LSCACHE_MODULES_ESI_TIPS'); ?>">
									<?php echo $this->escape($item->title); ?></a>
								<?php else : ?>
									<?php echo $this->escape($item->title); ?>
								<?php endif; ?>

								<?php if (!empty($item->note)) : ?>
									<div class="small">
										<?php echo Text::sprintf('JGLOBAL_LIST_NOTE', $this->escape($item->note)); ?>
									</div>
								<?php endif; ?>
							</div>
						</td>
                        <?php if($esiRender == "1") : ?>
						<td class="small hidden-phone hidden-tablet">
							<?php echo $item->lscache_type==1 ? 'Public' : ($item->lscache_type==-1 ? 'Private' : 'None') ; ?>
						</td>
						<td class="small hidden-phone hidden-tablet">
							<?php echo $item->lscache_ttl; ?>
						</td>
                        <?php endif; ?>
						<td class="small hidden-phone">
							<?php if ($item->position) : ?>
								<span class="label label-info">
									<?php echo $item->position; ?>
								</span>
							<?php else : ?>
								<span class="label">
									<?php echo Text::_('JNONE'); ?>
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
							<?php echo LayoutHelper::render('joomla.content.language', $item); ?>
						</td>
						<?php elseif ($clientId === 1 && JModuleHelper::isAdminMultilang()) : ?>
							<td class="small hidden-phone">
								<?php if ($item->language == ''):?>
									<?php echo Text::_('JUNDEFINED'); ?>
								<?php elseif ($item->language == '*'):?>
									<?php echo Text::alt('JALL', 'language'); ?>
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
<style type="text/css">
    #collapseModal {width:45%;}
</style>
        <?php echo HTMLHelper::_(
            'bootstrap.renderModal',
            'collapseModal',
            array(
                'title'  => Text::_('COM_LSCACHE_BTN_PURGE_URL'),
                'footer' => $this->loadTemplate('purge_url_footer'),
            ),
            $this->loadTemplate('purge_url_body')
        ); ?>

		<input type="hidden" name="task" value="" />
		<input type="hidden" name="boxchecked" value="0" />
		<?php echo HTMLHelper::_('form.token'); ?>
	</div>
</form>
