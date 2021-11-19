<?php
defined('_JEXEC') or die;

$text = JText::_('MOD_LSCACHE');

?>
<a  href="index.php?option=com_lscache&task=modules.purgelscache"
    class="header-item-content" title="<?php echo $text; ?>">
	<div class="header-item-icon">
		<span class="icon-trash" aria-hidden="true"></span>
	</div>
    
        <div class="header-item-text">
                <?php echo $text; ?>
        </div>
</a>