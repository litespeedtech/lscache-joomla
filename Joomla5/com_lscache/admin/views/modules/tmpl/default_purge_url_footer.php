<?php
defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
?>
<button type="button" class="btn"  data-dismiss="modal">
	<?php echo Text::_('JCANCEL'); ?>
</button>
<button type="submit" class="btn btn-success" onclick="Joomla.submitbutton('modules.purgeURL');">
	<?php echo Text::_('JGLOBAL_BATCH_PROCESS'); ?>
</button>
