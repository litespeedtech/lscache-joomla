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

$text = Text::_('COM_LSCACHE_BTN_PURGE_URL');
$title = Text::_('COM_LSCACHE_BTN_PURGE_URL_TIP');
?>

<button data-toggle="modal" onclick="jQuery( '#collapseModal' ).modal('show')" class="btn btn-small btn-success" title="<?php echo $title; ?>">
	<span class="icon-file-minus" aria-hidden="true"></span>
	<?php echo $text; ?>
</button>