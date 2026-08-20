<?php
namespace MyNamespace\Component\MyComponent\Administrator\Field;

defined('_JEXEC') || die;

use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

class JFormFieldRecacheComps extends ListField
{
    /**
     * The form field type.
     */
    protected $type = 'RecacheComps';

    /**
     * Method to get the field options.
     */
    protected function getOptions(): array
    {
        $options = [];
        $options[] = (object) [
                        'value' => "com_virtuemart",
                        'text' => "com_virtuemart: virtuemart component",
                    ];

        $options[] = (object) [
                        'value' => "com_content",
                        'text' => "com_content: article component",
                    ];                    
    }
}
