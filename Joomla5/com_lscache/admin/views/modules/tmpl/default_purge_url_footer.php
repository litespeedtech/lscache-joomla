<?php
defined('_JEXEC') or die;
?>
<button type="button" class="btn"  data-dismiss="modal">
	<?php echo JText::_('JCANCEL'); ?>
</button>
<button type="submit" class="btn btn-success" onclick="Joomla.submitbutton('modules.purgeURL');">
	<?php echo JText::_('JGLOBAL_BATCH_PROCESS'); ?>
</button>
