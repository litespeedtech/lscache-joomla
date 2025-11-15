<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_modules
 *
 * @copyright   Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;

$text = Text::_('COM_LSCACHE_BTN_PURGE_ALL');
$title = Text::_('COM_LSCACHE_BTN_PURGE_ALL_TIP');
?>
<button onclick="Joomla.submitbutton('modules.purgeall')" class="btn btn-small btn-success" title="<?php echo $title; ?>">
	<span class="icon-purge icon-white" aria-hidden="true"></span>
	<?php echo $text; ?>
</button>
