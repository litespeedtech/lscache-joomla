<?php
defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;

?>
<div class="container-popup">
    <p><?php echo Text::_('COM_LSCACHE_PURGE_URL_TIP'); ?></p>
    <textarea name="purgeURLs" id="jform_purgeURLs" rows="10" style="width:95%"  aria-invalid="false"></textarea>
</div>
