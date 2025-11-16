<?php

/*
 *  @since      1.2.1
 *  @author     LiteSpeed Technologies <info@litespeedtech.com>
 *  @copyright  Copyright (c) 2017-2018 LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 *  @license    https://opensource.org/licenses/GPL-3.0
 */

defined('JPATH_BASE') or die;

use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Form\Field\NoteField;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Uri\Uri;

FormHelper::loadFieldClass('note');

/**
 * Display Purge Url field
 *
 * @since  1.2.1
 */
class JFormFieldNotePurge extends NoteField
{
	protected $type = 'NotePurge';
    
	protected function getLabel()
	{
		$description = (string) $this->element['description'];
        $description = Text::_($description);
        $settings = ComponentHelper::getParams('com_lscache');
        $secureWords = $settings->get('cleanCache');
        $root = Uri::root();
        $description = str_replace('{webroot}', $root, $description);
        $description = str_replace('{secureword}', $secureWords, $description);
        
        $this->element['description'] = $description;
        return parent::getLabel();
    }
    
}
