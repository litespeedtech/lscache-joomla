<?php
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;

defined( '_JEXEC' ) or die( 'Restricted access' );

$app = Factory::getApplication();
$admin = $app->isClient('administrator');
if($admin==1)
	{
?>
<div>
	This Component was made to make it possible to create a menu item that has only modules and no component.<br />
	You can use it by adding a new menu item, select "Blank Component" from the "Menu Item Type" list, and save.<br />
	then, go to the module manager, and assign the modules you want to use with this menu item, and you're done!<br />
</div>
<?php
	}
else
	{
	// Create the controller
	$controller = BaseController::getInstance('LSCache');

	// Perform the Request task
	$controller->execute(Factory::getApplication()->input->getCmd('task'));

	// Redirect if set by the controller
	$controller->redirect();
	}

 ?>