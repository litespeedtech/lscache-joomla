<?php
/**
 *
 * Description
 *
 * @package    VirtueMart
 * @subpackage
 * @author Max Milbers, Patrick Kohl, Valerie Isaksen
 * @link http://www.virtuemart.net
 * @copyright Copyright (c) 2004 - 2014 VirtueMart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * @version $Id: product.php 9200 2016-04-04 17:22:51Z Milbo $
 */

// Check to ensure this file is included in Joomla!
defined ('_JEXEC') or die('Restricted access');


if (!class_exists ('VmModel')) {
	require(VMPATH_ADMIN . DS . 'helpers' . DS . 'vmmodel.php');
}

// JTable::addIncludePath(VMPATH_ADMIN.DS.'tables');
/**
 * Model for VirtueMart Products
 *
 * @package VirtueMart
 * @author Max Milbers
 * @todo Replace getOrderUp and getOrderDown with JTable move function. This requires the vm_product_category_xref table to replace the ordering with the ordering column
 */
class VirtueMartModelProduct extends VmModel {

	/**
	 * products object
	 *
	 * @var integer
	 */
	var $products = NULL;
	var $decimals = array('product_length','product_width','product_height','product_weight','product_packaging');
	var $_onlyQuery 	= false;

	/**
	 * constructs a VmModel
	 * setMainTable defines the maintable of the model
	 *
	 * @author Max Milbers
	 */
	function __construct () {

		parent::__construct ('virtuemart_product_id');
		$this->setMainTable ('products');
		$this->starttime = microtime (TRUE);
		$this->maxScriptTime = VmConfig::getExecutionTime() * 0.95 - 1;
		$memoryLimit = ini_get('memory_limit');
		if($memoryLimit!=-1){
			$this->memory_limit = (int) substr($memoryLimit,0,-1) -4; // 4 MB reserve
		} else {
			$this->memory_limit = '1024M';
		}

		$app = JFactory::getApplication ();
		if ($app->isSite ()) {
			$this->_validOrderingFieldName = array();
			$browseOrderByFields = VmConfig::get ('browse_orderby_fields',array('pc.ordering,product_name','product_sku','category_name','mf_name'));
			$this->addvalidOrderingFieldName (array('pc.ordering,product_name'));
		}
		else {
			if (!class_exists ('shopFunctions')) {
				require(VMPATH_ADMIN . DS . 'helpers' . DS . 'shopfunctions.php');
			}
			$browseOrderByFields = ShopFunctions::getValidProductFilterArray ();
			$this->addvalidOrderingFieldName (array('pc.ordering,product_name','product_price','product_sales'));
			//$this->addvalidOrderingFieldName (array('product_price'));
			// 	vmdebug('$browseOrderByFields',$browseOrderByFields);
		}
		$this->addvalidOrderingFieldName ((array)$browseOrderByFields);

		$this->removevalidOrderingFieldName ('virtuemart_product_id');

		//array_unshift ($this->_validOrderingFieldName, 'pc.ordering,product_name');
		array_unshift ($this->_validOrderingFieldName, 'p.virtuemart_product_id');
		$this->_selectedOrdering = VmConfig::get ('browse_orderby_field', 'pc.ordering,product_name');

		$this->setToggleName('product_special');

		$this->initialiseRequests ();

		//This is just done now for the moment for developing, the idea is of course todo this only when needed.
		$this->populateState ();

	}

	var $keyword = "0";
	var $product_parent_id = FALSE;
	var $virtuemart_manufacturer_id = FALSE;
	var $virtuemart_category_id = 0;
	var $search_type = '';
	var $searchcustoms = FALSE;
	var $searchplugin = 0;
	var $filter_order = 'p.virtuemart_product_id';
	var $filter_order_Dir = 'DESC';
	var $valid_BE_search_fields = array('product_name', 'product_sku','`l`.`slug`', 'product_s_desc', '`l`.`metadesc`');
	private $_autoOrder = 0;
	private $orderByString = 0;
	private $listing = FALSE;

	/**
	 * This function resets the variables holding request depended data to the initial values
	 *
	 * @author Max Milbers
	 */
	function initialiseRequests () {

		$this->keyword = "";
		$this->valid_search_fields = $this->valid_BE_search_fields;
		$this->product_parent_id = FALSE;
		$this->virtuemart_manufacturer_id = FALSE;
		$this->search_type = '';
		$this->searchcustoms = FALSE;
		$this->searchplugin = 0;
		$this->filter_order = VmConfig::get ('browse_orderby_field');
		$this->filter_order_Dir = VmConfig::get('prd_brws_orderby_dir', 'ASC');

		$this->_uncategorizedChildren = null;

		$this->virtuemart_vendor_id = 0;
	}

	/**
	 * @deprecated
	 */
	function updateRequests () {
		$this->populateState();
	}

	/**
	 * This functions updates the variables of the model which are used in the sortSearchListQuery
	 *  with the variables from the Request
	 *
	 * @author Max Milbers
	 */
	protected function populateState () {

		$app = JFactory::getApplication ();
		$option = 'com_virtuemart';
		$view = 'product';

		$valid_search_fields = VmConfig::get ('browse_search_fields');
		if ($app->isSite () and !vRequest::getInt('manage',false)) {
			$filter_order = vRequest::getString ('orderby', "0");

			if($filter_order == "0"){
				$filter_order_raw = $this->getLastProductOrdering($this->_selectedOrdering);
				$filter_order = $this->checkFilterOrder ($filter_order_raw);
			} else {
				$filter_order = $this->checkFilterOrder ($filter_order);
				$this->setLastProductOrdering($filter_order);

			}
			$filter_order_Dir = strtoupper (vRequest::getCmd ('dir', VmConfig::get('prd_brws_orderby_dir', 'ASC')));

			$this->product_parent_id = vRequest::getInt ('product_parent_id', FALSE);
			$this->virtuemart_manufacturer_id = vRequest::getInt ('virtuemart_manufacturer_id', FALSE);

			$this->keyword = vRequest::getString('keyword','');	//vRequest::uword ('keyword', "", ' ,-,+,.,_,#,/');

			if ($this->keyword === '') {
				$this->keyword = vRequest::getString('filter_product','');//vRequest::uword ('filter_product', "", ' ,-,+,.,_,#,/');
				vRequest::setVar('filter_product',$this->keyword);
			} else {
				vRequest::setVar('keyword',$this->keyword);
			}

		}
		else {
			$filter_order = strtolower ($app->getUserStateFromRequest ('com_virtuemart.' . $view . '.filter_order', 'filter_order', $this->_selectedOrdering, 'cmd'));

			$filter_order = $this->checkFilterOrder ($filter_order);
			$filter_order_Dir = strtoupper ($app->getUserStateFromRequest ($option . '.' . $view . '.filter_order_Dir', 'filter_order_Dir', '', 'word'));

			$valid_search_fields = array_unique(array_merge($this->valid_BE_search_fields, $valid_search_fields));

			$view = vRequest::getCmd ('view');
			$stateTypes = array('virtuemart_category_id'=>'int','virtuemart_manufacturer_id'=>'int','product_parent_id'=>'int','filter_product'=>'string','search_type'=>'string','search_order'=>'string','search_date'=>'string','virtuemart_vendor_id' => 'int');

			foreach($stateTypes as $type => $filter){
				$k= 'com_virtuemart.' . $view . '.'.$type;
				if($filter=='int'){
					$new_state = vRequest::getInt($type, false);
				} else {
					$new_state = vRequest::getVar($type, false);
				}

				if($new_state===false){
					$this->{$type} = $app->getUserState($k, '');
				} else {
					$app->setUserState( $k,$new_state);
					$this->{$type} = $new_state;
				}
			}

			$this->keyword = $this->filter_product;
		}
		$filter_order_Dir = $this->checkFilterDir ($filter_order_Dir);

		$this->filter_order = $filter_order;
		$this->filter_order_Dir = $filter_order_Dir;
		$this->valid_search_fields = $valid_search_fields;

		$this->search_type = vRequest::getVar ('search_type', '');

		$this->searchcustoms = vRequest::getVar ('customfields', false, true);

		$this->searchplugin = vRequest::getInt ('custom_parent_id', 0);

		//$this->virtuemart_vendor_id = vmAccess::isSuperVendor();
		$this->virtuemart_vendor_id = vmAccess::getVendorId();
		$this->__state_set = true;
	}

	/**
	 * @author Max Milbers
	 */
	public function getLastProductOrdering($default = 0){
		$session = JFactory::getSession();
		return $session->get('vmlastproductordering', $default, 'vm');
	}

	/**
	 * @author Max Milbers
	 */
	public function setLastProductOrdering($ordering){
		$session = JFactory::getSession();
		return $session->set('vmlastproductordering', (string) $ordering, 'vm');
	}

	/**
	 * Sets the keyword variable for the search
	 *
	 * @param string $keyword
	 */
	function setKeyWord ($keyword) {

		$this->keyword = $keyword;
	}

	/**
	 * New function for sorting, searching, filtering and pagination for product ids.
	 *
	 * @author Max Milbers
	 */
	function sortSearchListQuery ($onlyPublished = TRUE, $virtuemart_category_id = FALSE, $group = FALSE, $nbrReturnProducts = FALSE, $langFields = array()) {

		$app = JFactory::getApplication ();
		$db = JFactory::getDbo();
		
		//User Q.Stanley said that removing group by is increasing the speed of product listing in a bigger shop (10k products) by factor 60
		//So what was the reason for that we have it? TODO experiemental, find conditions for the need of group by
		$groupBy = ' group by p.`virtuemart_product_id` ';

		//administrative variables to organize the joining of tables
		$joinLang = false;
		$joinCategory = FALSE;
		$joinCatLang = false;
		$joinMf = FALSE;
		$joinMfLang = false;
		$joinPrice = FALSE;
		$joinCustom = FALSE;
		$joinShopper = FALSE;
		$joinChildren = FALSE;
		//$joinLang = false;
		$orderBy = ' ';

		$where = array();

		//$isSite = $app->isSite ();
		$isSite = true;
		if($app->isAdmin() or (vRequest::get('manage',false) and vmAccess::manager('product')) ){
			$isSite = false;
		}

		$langFback = ( !VmConfig::get('prodOnlyWLang',false) and VmConfig::$defaultLang!=VmConfig::$vmlang and VmConfig::$langCount>1 );

		if (!empty($this->keyword) and $this->keyword !== '' and $group === FALSE) {

			$keyword = vRequest::filter(html_entity_decode($this->keyword, ENT_QUOTES, "UTF-8"),FILTER_SANITIZE_STRING,FILTER_FLAG_ENCODE_LOW);

			$keyword =  '"%' .str_replace(array(' ','-'),'%', $keyword). '%"';
			//$keyword = '"%' . $db->escape ($this->keyword, TRUE) . '%"';
			vmdebug('Current search field',$this->valid_search_fields);
			foreach ($this->valid_search_fields as $searchField) {
				if ($searchField == 'category_name' || $searchField == 'category_description') {
					$joinCatLang = true;
				}
				else if ($searchField == 'mf_name') {
					$joinMfLang = true;
				}
				else if ($searchField == 'product_price') {
					$joinPrice = TRUE;
				}
				else if ($searchField == 'product_name' or $searchField == 'product_s_desc' or $searchField == 'product_desc' or $searchField == 'slug' ){
					$langFields[] = $searchField;
					//if (strpos ($searchField, '`') !== FALSE){
						//$searchField = '`l`.'.$searchField;
					$keywords_plural = preg_replace('/\s+/', '%" AND '.$searchField.' LIKE "%', $keyword);
					if($langFback){
						$filter_search[] =  '`ld`.'.$searchField . ' LIKE ' . $keywords_plural;
						if(VmConfig::$defaultLang!=VmConfig::$jDefLang){
							$filter_search[] =  '`ljd`.'.$searchField . ' LIKE ' . $keywords_plural;
						}
					}
					$searchField = '`l`.'.$searchField;
					//}
				}

				if (strpos ($searchField, '`') !== FALSE){
					$keywords_plural = preg_replace('/\s+/', '%" AND '.$searchField.' LIKE "%', $keyword);
					$filter_search[] =  $searchField . ' LIKE ' . $keywords_plural;
				} else {
					$keywords_plural = preg_replace('/\s+/', '%" AND `'.$searchField.'` LIKE "%', $keyword);
					$filter_search[] = '`'.$searchField.'` LIKE '.$keywords_plural;

					//$filter_search[] = '`' . $searchField . '` LIKE ' . $keyword;
				}
			}
			if (!empty($filter_search)) {
				$where[] = '(' . implode (' OR ', $filter_search) . ')';
			}
			else {
				$where[] = '`l`.product_name LIKE ' . $keyword;
				$langFields[] = 'product_name';
				//If they have no check boxes selected it will default to product name at least.
			}
		}


		if (!empty($this->searchcustoms)) {
			$joinCustom = TRUE;
			foreach ($this->searchcustoms as $key => $searchcustom) {
				$custom_search[] = '(pf.`virtuemart_custom_id`="' . (int)$key . '" and pf.`customfield_value` like "%' . $db->escape ($searchcustom, TRUE) . '%")';
			}
			$where[] = " ( " . implode (' OR ', $custom_search) . " ) ";
		}

		if($isSite and !VmConfig::get('use_as_catalog',0)) {
			if (VmConfig::get('stockhandle','none')=='disableit_children') {
				$where[] = ' ( (p.`product_in_stock` - p.`product_ordered`) >"0" OR (children.`product_in_stock` - children.`product_ordered`) > "0") ';
				$joinChildren = TRUE;
			} else if (VmConfig::get('stockhandle','none')=='disableit') {
				$where[] = ' p.`product_in_stock` - p.`product_ordered` >"0" ';
			}
		}

		if ($virtuemart_category_id > 0) {
			$joinCategory = TRUE;
			if(true){
				$where[] = ' `pc`.`virtuemart_category_id` = ' . $virtuemart_category_id;
			} else {
				/*GJC add subcat products*/
				$catmodel = VmModel::getModel ('category');
				$childcats = $catmodel->getChildCategoryList(1, $virtuemart_category_id,null, null, true);
				$cats = $virtuemart_category_id;
				foreach($childcats as $childcat){
					$cats .= ','.$childcat->virtuemart_category_id;
				}
				$joinCategory = TRUE;
				$where[] = ' `pc`.`virtuemart_category_id` IN ('.$cats.') ';
			}
		} else if ($isSite) {
			if (!VmConfig::get('show_uncat_parent_products',TRUE)) {
				$joinCategory = TRUE;
				$where[] = ' ((p.`product_parent_id` = "0" AND `pc`.`virtuemart_category_id` > "0") OR p.`product_parent_id` > "0") ';
			}
			if (!VmConfig::get('show_uncat_child_products',TRUE)) {
				$joinCategory = TRUE;
				$where[] = ' ((p.`product_parent_id` > "0" AND `pc`.`virtuemart_category_id` > "0") OR p.`product_parent_id` = "0") ';
			}
		}

		if ($isSite and !VmConfig::get('show_unpub_cat_products',TRUE)) {
			$joinCategory = TRUE;
			$where[] = ' `c`.`published` = 1 ';
		}

		if ($this->product_parent_id) {
			$where[] = ' p.`product_parent_id` = ' . $this->product_parent_id;
		}

		if ($isSite) {
			$usermodel = VmModel::getModel ('user');
			$currentVMuser = $usermodel->getCurrentUser ();
			$virtuemart_shoppergroup_ids = (array)$currentVMuser->shopper_groups;

			if (is_array ($virtuemart_shoppergroup_ids)) {
				$sgrgroups = array();
				foreach ($virtuemart_shoppergroup_ids as $key => $virtuemart_shoppergroup_id) {
					$sgrgroups[] = '`ps`.`virtuemart_shoppergroup_id`= "' . (int)$virtuemart_shoppergroup_id . '" ';
				}
				$sgrgroups[] = '`ps`.`virtuemart_shoppergroup_id` IS NULL ';
				$where[] = " ( " . implode (' OR ', $sgrgroups) . " ) ";

				$joinShopper = TRUE;
			}
		}

		if ($this->virtuemart_manufacturer_id) {
			$joinMf = TRUE;
			if(is_array($this->virtuemart_manufacturer_id)){
				$mans = array();
				foreach ($this->virtuemart_manufacturer_id as $key => $v) {
					$mans[] = '`#__virtuemart_product_manufacturers`.`virtuemart_manufacturer_id`= "' . (int)$v . '" ';
				}
				$where[] = " ( " . implode (' OR ', $mans) . " ) ";
			} else {
				$where[] = ' `#__virtuemart_product_manufacturers`.`virtuemart_manufacturer_id` = ' . $this->virtuemart_manufacturer_id;
				//$virtuemart_manufacturer_id = $this->virtuemart_manufacturer_id;
			}

		}

		// Time filter
		if ($this->search_type != '') {
			$search_order = $db->escape (vRequest::getCmd ('search_order',$this->search_order) == 'bf' ? '<' : '>');
			switch ($this->search_type) {
				case 'parent':
					$where[] = 'p.`product_parent_id` = "0"';
					break;
				case 'product':
					$where[] = 'p.`modified_on` ' . $search_order . ' "' . $db->escape (vRequest::getVar ('search_date')) . '"';
					break;
				case 'price':
					$joinPrice = TRUE;
					$where[] = 'pp.`modified_on` ' . $search_order . ' "' . $db->escape (vRequest::getVar ('search_date')) . '"';
					break;
				case 'withoutprice':
					$joinPrice = TRUE;
					$where[] = 'pp.`product_price` IS NULL';
					break;
				case 'stockout':
					$where[] = ' p.`product_in_stock`- p.`product_ordered` < 1';
					break;
				case 'stocklow':
					$where[] = 'p.`product_in_stock`- p.`product_ordered` < p.`low_stock_notification`';
					break;
			}
		}

		//vmdebug('my filter ordering ',$this->filter_order);
		// special  orders case
		$ff_select_price = '';
		$filterOrderDir = $this->filter_order_Dir;
		switch ($this->filter_order) {
			case '`p`.product_special':
				if($isSite){
					$where[] = ' p.`product_special`="1" '; // TODO Change  to  a  individual button
					$orderBy = 'ORDER BY RAND()';
				} else {
					$orderBy = 'ORDER BY p.`product_special` '.$filterOrderDir.', `virtuemart_product_id` '.$filterOrderDir;
				}
				break;
			case 'category_name':
				$orderBy = ' ORDER BY `category_name` '.$filterOrderDir.', `virtuemart_product_id` '.$filterOrderDir;
				$joinCategory = TRUE;
				$joinCatLang = true;
				break;
			case 'category_description':
				$orderBy = ' ORDER BY `category_description` '.$filterOrderDir.', `virtuemart_product_id` '.$filterOrderDir;
				$joinCategory = TRUE;
				$joinCatLang = true;
				break;
			case 'mf_name':
			case '`l`.mf_name':
				$orderBy = ' ORDER BY `mf_name` '.$filterOrderDir.', `virtuemart_product_id` '.$filterOrderDir;
				$joinMf = TRUE;
				$joinMfLang = true;
				break;
			case 'ordering':
			case 'pc.ordering':
				$orderBy = ' ORDER BY `pc`.`ordering` '.$filterOrderDir.', `virtuemart_product_id` '.$filterOrderDir;
				$joinCategory = TRUE;
				break;
			case 'pc.ordering,product_name':
				$orderBy = ' ORDER BY `pc`.`ordering` '.$filterOrderDir.', `product_name` '.$filterOrderDir;
				$joinCategory = TRUE;
				$joinLang = true;
				break;
			case 'product_price':
				$orderBy = ' ORDER BY `product_price` '.$filterOrderDir.', `virtuemart_product_id` '.$filterOrderDir;
				$ff_select_price = ' , IF(pp.override, pp.product_override_price, pp.product_price) as product_price ';
				$joinPrice = TRUE;
				break;
			case 'created_on':
			case '`p`.created_on':
				$orderBy = ' ORDER BY p.`created_on` '.$filterOrderDir.', `virtuemart_product_id` '.$filterOrderDir;
				break;
			default;
				if (!empty($this->filter_order)) {
					$orderBy = ' ORDER BY '.$this->filter_order.' ' . $filterOrderDir ;
					if($this->filter_order!='virtuemart_product_id'){
						$orderBy .= ', `virtuemart_product_id` '.$filterOrderDir;
					}

				}
				break;
		}
		$filterOrderDir = '';

		//Group case from the modules
		if ($group) {

			$latest_products_days = VmConfig::get ('latest_products_days', 7);
			$latest_products_orderBy = VmConfig::get ('latest_products_orderBy','created_on');
			$groupBy = 'group by p.`virtuemart_product_id` ';
			switch ($group) {
				case 'featured':
					$where[] = 'p.`product_special`="1" ';
					$orderBy = 'ORDER BY RAND()';
					break;
				case 'latest':
					$orderBy = 'ORDER BY p.`' . $latest_products_orderBy . '` DESC, `virtuemart_product_id` DESC';;
					break;
				case 'random':
					$orderBy = ' ORDER BY RAND() '; //LIMIT 0, '.(int)$nbrReturnProducts ; //TODO set limit LIMIT 0, '.(int)$nbrReturnProducts;
					break;
				case 'topten':
					$orderBy = ' ORDER BY p.`product_sales` DESC, `virtuemart_product_id` DESC'; //LIMIT 0, '.(int)$nbrReturnProducts;  //TODO set limitLIMIT 0, '.(int)$nbrReturnProducts;
					$joinPrice = true;
					$where[] = 'pp.`product_price`>"0.0" ';
				break;
				case 'recent':
					$rSession = JFactory::getSession();
					$rIds = $rSession->get('vmlastvisitedproductids', array(), 'vm'); // get recent viewed from browser session
					return $rIds;
			}
			// 			$joinCategory 	= false ; //creates error
			// 			$joinMf 		= false ;	//creates error
			$joinPrice = TRUE;	//Why we set this all the time?
			$this->searchplugin = FALSE;
// 			$joinLang = false;
		}

		/*if ($onlyPublished and !empty($this->virtuemart_vendor_id) and vRequest::get('manage',false) and vmAccess::isSuperVendor()) {
			$where[] = ' p.`virtuemart_vendor_id` = "'.$this->virtuemart_vendor_id.'" ';
		} else {*/
			if(!empty($onlyPublished) and $isSite){
				$where[] = ' p.`published`="1" ';
			}
			if(!empty($this->virtuemart_vendor_id)){
				$where[] = ' p.`virtuemart_vendor_id` = "'.$this->virtuemart_vendor_id.'" ';
			}
		//}



		$joinedTables = array();

		//This option switches between showing products without the selected language or only products with language.
		if( $app->isSite() ){	//and !VmConfig::get('prodOnlyWLang',false)){
			//Maybe we have to join the language to order by product name, description, etc,...
			$productLangFields = array('product_s_desc','product_desc','product_name','metadesc','metakey','slug');
			foreach($productLangFields as $field){
				if(strpos($orderBy,$field,6)!==FALSE){
					$langFields[] = $field;
					$orderbyLangField = $field;
					$joinLang = true;
					break;
				}
			}

		} else {
			$joinLang = true;
		}

		$selectLang = '';
		if ($joinLang or count($langFields)>0 ) {

			if($langFback){

				$this->useLback = true;
				$this->useJLback = false;
				$method = 'LEFT';
				if($isSite){
					$method = 'INNER';
				}


				if(VmConfig::$defaultLang!=VmConfig::$jDefLang){
					$joinedTables[] = ' '.$method.' JOIN `#__virtuemart_products_' .VmConfig::$jDefLang . '` as ljd using (`virtuemart_product_id`)';
					$method = 'LEFT';
					$this->useJLback = true;
				}

				$joinedTables[] = ' '.$method.' JOIN `#__virtuemart_products_' .VmConfig::$defaultLang . '` as ld using (`virtuemart_product_id`)';
				$joinedTables[] = ' LEFT JOIN `#__virtuemart_products_' . VmConfig::$vmlang . '` as l using (`virtuemart_product_id`)';

				$langFields = array_unique($langFields);

				if(count($langFields)>0){
					foreach($langFields as $langField){
						$expr2 = 'ld.'.$langField;
						if($this->useJLback){
							$expr2 = 'IFNULL(ld.'.$langField.', ljd.'.$langField.')';
						}
						$selectLang .= ', IFNULL(l.'.$langField.','.$expr2.') as '.$langField.'';
					}
				}
			} else {
				$this->useLback = false;
				$joinedTables[] = ' INNER JOIN `#__virtuemart_products_' . VmConfig::$vmlang . '` as l using (`virtuemart_product_id`)';
			}

		}

		$select = ' p.`virtuemart_product_id`'.$ff_select_price.$selectLang.' FROM `#__virtuemart_products` as p ';

		if ($joinShopper == TRUE) {
			$joinedTables[] = ' LEFT JOIN `#__virtuemart_product_shoppergroups` as ps ON p.`virtuemart_product_id` = `ps`.`virtuemart_product_id` ';
			//$joinedTables[] = ' LEFT OUTER JOIN `#__virtuemart_shoppergroups` as s ON s.`virtuemart_shoppergroup_id` = `#__virtuemart_product_shoppergroups`.`virtuemart_shoppergroup_id` ';
		}

		if ($joinCategory == TRUE or $joinCatLang) {
			$joinedTables[] = ' LEFT JOIN `#__virtuemart_product_categories` as pc ON p.`virtuemart_product_id` = `pc`.`virtuemart_product_id` ';
			if ($isSite and !VmConfig::get('show_unpub_cat_products',TRUE)) {
				$joinedTables[] = ' LEFT JOIN `#__virtuemart_categories` as c ON c.`virtuemart_category_id` = `pc`.`virtuemart_category_id` ';
			}
			if($joinCatLang){
				$joinedTables[] = ' LEFT JOIN `#__virtuemart_categories_' . VmConfig::$vmlang . '` as cl ON cl.`virtuemart_category_id` = `pc`.`virtuemart_category_id`';
			}
		}

		if ($joinMf == TRUE or $joinMfLang) {
			$joinedTables[] = ' LEFT JOIN `#__virtuemart_product_manufacturers` ON p.`virtuemart_product_id` = `#__virtuemart_product_manufacturers`.`virtuemart_product_id` ';
			if($joinMfLang){
				$joinedTables[] = 'LEFT JOIN `#__virtuemart_manufacturers_' . VmConfig::$vmlang . '` as m ON m.`virtuemart_manufacturer_id` = `#__virtuemart_product_manufacturers`.`virtuemart_manufacturer_id` ';
			}
		}

		if ($joinPrice == TRUE) {
			$joinedTables[] = ' LEFT JOIN `#__virtuemart_product_prices` as pp ON p.`virtuemart_product_id` = pp.`virtuemart_product_id` ';
		}

		if ($this->searchcustoms) {
			$joinedTables[] = ' LEFT JOIN `#__virtuemart_product_customfields` as pf ON p.`virtuemart_product_id` = pf.`virtuemart_product_id` ';
		}

		if ($this->searchplugin !== 0) {
			if (!empty($PluginJoinTables)) {
				$plgName = $PluginJoinTables[0];
				$joinedTables[] = ' LEFT JOIN `#__virtuemart_product_custom_plg_' . $plgName . '` as ' . $plgName . ' ON ' . $plgName . '.`virtuemart_product_id` = p.`virtuemart_product_id` ';
			}
		}

		/*if ($joinShopper == TRUE) {
			$joinedTables[] = ' LEFT JOIN `#__virtuemart_product_shoppergroups` ON p.`virtuemart_product_id` = `#__virtuemart_product_shoppergroups`.`virtuemart_product_id`
			 LEFT  OUTER JOIN `#__virtuemart_shoppergroups` as s ON s.`virtuemart_shoppergroup_id` = `#__virtuemart_product_shoppergroups`.`virtuemart_shoppergroup_id`';
		}/*/

		if ($joinChildren) {
			$joinedTables[] = ' LEFT OUTER JOIN `#__virtuemart_products` children ON p.`virtuemart_product_id` = children.`product_parent_id` ';
		}

		if ($this->searchplugin !== 0) {
			JPluginHelper::importPlugin('vmcustom');
			$dispatcher = JDispatcher::getInstance();
			$dispatcher->trigger('plgVmBeforeProductSearch', array(&$select, &$joinedTables, &$where, &$groupBy, &$orderBy,&$joinLang));
		}

		if (count ($where) > 0) {
			$whereString = ' WHERE (' . implode (' AND ', $where) . ') ';
		}
		else {
			$whereString = '';
		}
		//vmdebug ( ' joined ? ',$select, $joinedTables, $whereString, $groupBy, $orderBy, $this->filter_order_Dir );		/* jexit();  */

		$this->orderByString = $orderBy;

		if($this->_onlyQuery){
			return (array($select,$joinedTables,$where,$orderBy,$joinLang));
		}
		$joinedTables = " \n".implode(" \n",$joinedTables);


		vmSetStartTime('sortSearchQuery');
		$product_ids = $this->exeSortSearchListQuery (2, $select, $joinedTables, $whereString, $groupBy, $orderBy, $filterOrderDir, $nbrReturnProducts);

		vmTime('sortSearchQuery products','sortSearchQuery');
		//vmdebug('exeSortSearchLIstquery orderby ',$product_ids);
		return $product_ids;

	}

	/**
	 * Override
	 *
	 * @see VmModel::setPaginationLimits()
	 */
	public function setPaginationLimits () {

		$app = JFactory::getApplication ();
		$view = vRequest::getCmd ('view','virtuemart');

		$cateid = vRequest::getInt ('virtuemart_category_id', -1);
		$manid = vRequest::getInt ('virtuemart_manufacturer_id', 0);

		$limitString = 'com_virtuemart.' . $view . 'c' . $cateid . '.limit';
		$limit = (int)$app->getUserStateFromRequest ($limitString, 'limit');

		$limitStartString  = 'com_virtuemart.' . $view . '.limitstart';
		if ($app->isSite () and ($cateid != -1 or $manid != 0) ) {

			//vmdebug('setPaginationLimits is site and $cateid,$manid ',$cateid,$manid);
			$lastCatId = ShopFunctionsf::getLastVisitedCategoryId ();
			$lastManId = ShopFunctionsf::getLastVisitedManuId ();

			if( !empty($cateid) and $cateid != -1) {
				$gCatId = $cateid;
			} else if( !empty($lastCatId) ) {
				$gCatId = $lastCatId;
			}

			if(!empty($gCatId)){
				$catModel= VmModel::getModel('category');
				$category = $catModel->getCategory($gCatId);
			} else {
				$category = new stdClass();
			}

			if ((!empty($lastCatId) and $lastCatId != $cateid) or (!empty($manid) and $lastManId != $manid)) {
				//We are in a new category or another manufacturer, so we start at page 1
				$limitStart = vRequest::getInt ('limitstart', 0,'GET');
			}
			else {
				//We were already in the category/manufacturer, so we take the value stored in the session
				$limitStartString  = 'com_virtuemart.' . $view . 'c' . $cateid .'m'.$manid. '.limitstart';
				$limitStart = $app->getUserStateFromRequest ($limitStartString, 'limitstart', vRequest::getInt ('limitstart', 0,'GET'), 'int');
			}

			if(empty($limit) and !empty($category->limit_list_initial)){
				$suglimit = $category->limit_list_initial;
			}
			else if(!empty($limit)){
				$suglimit = $limit;
			} else {
				$suglimit = VmConfig::get ('llimit_init_FE', 24);
			}
			if(empty($category->products_per_row)){
				$category->products_per_row = VmConfig::get ('products_per_row', 3);
			}
			$rest = $suglimit%$category->products_per_row;
			$limit = $suglimit - $rest;

			if(!empty($category->limit_list_step)){
				$prod_per_page = explode(",",$category->limit_list_step);
			} else {
				//fix by hjet
				$prod_per_page = explode(",",VmConfig::get('pagseq_'.$category->products_per_row));
			}

			if($limit <= $prod_per_page['0'] && array_key_exists('0',$prod_per_page)){
				$limit = $prod_per_page['0'];
			}

			//vmdebug('Calculated $limit  ',$limit,$suglimit);
		}
		else {
			$limitStart = $app->getUserStateFromRequest ('com_virtuemart.' . $view . '.limitstart', 'limitstart', vRequest::getInt ('limitstart', 0,'GET'), 'int');
		}

		if(empty($limit)){
			if($app->isSite()){
				$limit = VmConfig::get ('llimit_init_FE',24);
			} else {
				$limit = VmConfig::get ('llimit_init_BE',30);
			}
			if(empty($limit)){
				$limit = 30;
			}
		}

		$this->setState ('limit', $limit);
		$this->setState ($limitString, $limit);
		$this->_limit = $limit;

		//There is a strange error in the frontend giving back 9 instead of 10, or 24 instead of 25
		//This functions assures that the steps of limitstart fit with the limit
		$limitStart = ceil ((float)$limitStart / (float)$limit) * $limit;

		$this->setState ('limitstart', $limitStart);
		$this->setState ($limitStartString, $limitStart);

		$this->_limitStart = $limitStart;

		return array($this->_limitStart, $this->_limit);
	}

	public function checkIfCached($virtuemart_product_id = NULL, $front = TRUE, $withCalc = TRUE, $onlyPublished = TRUE, $quantity = 1,$virtuemart_shoppergroup_ids = 0){

		if($virtuemart_shoppergroup_ids !=0 and is_array($virtuemart_shoppergroup_ids)){
			$virtuemart_shoppergroup_idsString = implode('.',$virtuemart_shoppergroup_ids);
		} else {
			$virtuemart_shoppergroup_idsString = $virtuemart_shoppergroup_ids;
		}

		$front = $front?TRUE:0;
		$withCalc = $withCalc?TRUE:0;
		$onlyPublished = $onlyPublished?TRUE:0;
		$this->withRating = $this->withRating?TRUE:0;

		$productKey = $virtuemart_product_id.':'.$front.$onlyPublished.':'.$quantity.':'.$virtuemart_shoppergroup_idsString.':'.$withCalc.$this->withRating;

		if (array_key_exists ($productKey, self::$_products)) {
			//vmdebug('getProduct, take from cache : '.$productKey);
			return  array(true,$productKey);
		} else if(!$withCalc){
			$productKeyTmp = $virtuemart_product_id.':'.$front.$onlyPublished.':'.$quantity.':'.$virtuemart_shoppergroup_idsString.':'.TRUE.$this->withRating;
			if (array_key_exists ($productKeyTmp,  self::$_products)) {
				//vmdebug('getProduct, take from cache full product '.$productKeyTmp);
				return  array(true,$productKeyTmp);
			}
		} else {
			//vmdebug('getProduct, not cached '.$productKey);
			return array(false,$productKey);
		}
	}

	static $_products = array();
	/**
	 * This function creates a product with the attributes of the parent.
	 *
	 * @param int     $virtuemart_product_id
	 * @param boolean $front for frontend use
	 * @param boolean $withCalc calculate prices?
	 * @param boolean published
	 * @param int quantity
	 * @param boolean load customfields
	 */
	public function getProduct ($virtuemart_product_id = NULL, $front = TRUE, $withCalc = TRUE, $onlyPublished = TRUE, $quantity = 1,$virtuemart_shoppergroup_ids = 0) {

		//vmSetStartTime('getProduct');
		if (isset($virtuemart_product_id)) {
			$virtuemart_product_id = $this->setId ($virtuemart_product_id);
		}
		else {
			if (empty($this->_id)) {
				vmdebug('Can not return product with empty id');
				return FALSE;
			}
			else {
				$virtuemart_product_id = $this->_id;
			}
		}
		$checkedProductKey= $this->checkIfCached($virtuemart_product_id, $front, $withCalc, $onlyPublished, $quantity,$virtuemart_shoppergroup_ids);
		if($checkedProductKey[0]){
			if(self::$_products[$checkedProductKey[1]]===false){
				return false;
			} else {
				//vmTime('getProduct return cached clone','getProduct');
				return clone(self::$_products[$checkedProductKey[1]]);
			}
		}
		$productKey = $checkedProductKey[1];

		if ($this->memory_limit<$mem = round(memory_get_usage(FALSE)/(1024*1024),2)) {
			vmdebug ('Memory limit reached in model product getProduct('.$virtuemart_product_id.'), consumed: '.$mem.'M');
			vmError ('Memory limit reached in model product getProduct() ' . $virtuemart_product_id);
			return false;
		}
		$child = $this->getProductSingle ($virtuemart_product_id, $front,$quantity,true,$virtuemart_shoppergroup_ids);

		if (!$child->published && $onlyPublished) {
			self::$_products[$productKey] = false;
			vmTime('getProduct return false, not published','getProduct');
			return FALSE;
		}

		if(!isset($child->orderable)){
			$child->orderable = TRUE;
		}
		//store the original parent id
		$pId = $child->virtuemart_product_id;
		$ppId = $child->product_parent_id;
		$published = $child->published;
		if(!empty($pId)) $child->allIds[] = $pId;

		$i = 0;
		$runtime = microtime (TRUE) - $this->starttime;
		//Check for all attributes to inherited by parent products
		while (!empty($child->product_parent_id)) {
			$runtime = microtime (TRUE) - $this->starttime;
			if ($runtime >= $this->maxScriptTime) {
				vmdebug ('Max execution time reached in model product getProduct() ', $child);
				vmError ('Max execution time reached in model product getProduct() ' . $child->product_parent_id);
				break;
			}
			else {
				if ($i > 10) {
					vmdebug ('Time: ' . $runtime . ' Too many child products in getProduct() ', $child);
					vmError ('Time: ' . $runtime . ' Too many child products in getProduct() ' . $child->product_parent_id);
					break;
				}
			}
			//$child->allIds[] = $child->product_parent_id;
			if(!empty($child->product_parent_id)) $child->allIds[] = $child->product_parent_id;
			$parentProduct = $this->getProductSingle ($child->product_parent_id, $front,$quantity);
			if ($child->product_parent_id === $parentProduct->product_parent_id) {
				vmError('Error, parent product with virtuemart_product_id = '.$parentProduct->virtuemart_product_id.' has same parent id like the child with virtuemart_product_id '.$child->virtuemart_product_id);
				vmTrace('Error, parent product with virtuemart_product_id = '.$parentProduct->virtuemart_product_id.' has same parent id like the child with virtuemart_product_id '.$child->virtuemart_product_id);
				break;
			}
			$attribs = get_object_vars ($parentProduct);

			foreach ($attribs as $k=> $v) {
				if (strpos($k, "\0")===0) continue;
				if ('product_in_stock' != $k and 'product_ordered' != $k) {// Do not copy parent stock into child
					if (strpos ($k, '_') !== 0 and empty($child->$k)) {
						$child->$k = $v;
					//	vmdebug($child->product_parent_id.' $child->$k',$child->$k);
					}
				}
			}
			$i++;
			if ($child->product_parent_id != $parentProduct->product_parent_id) {
				$child->product_parent_id = $parentProduct->product_parent_id;
			}
			else {
				$child->product_parent_id = 0;
			}

		}

		//vmdebug('getProduct Time: '.$runtime);
		$child->published = $published;
		$child->virtuemart_product_id = $pId;
		$child->product_parent_id = $ppId;

		if ($withCalc) {

			$child->allPrices[$child->selectedPrice] = $this->getPrice ($child, 1);
			$child->prices = $child->allPrices[$child->selectedPrice];
		}

		if (empty($child->product_template)) {
			$child->product_template = VmConfig::get ('producttemplate');
		}

		if(!empty($child->canonCatId) ) {
			// Add the product link  for canonical
			$child->canonical = 'index.php?option=com_virtuemart&view=productdetails&virtuemart_product_id=' . $virtuemart_product_id . '&virtuemart_category_id=' . $child->canonCatId;
		} else {
			$child->canonical = 'index.php?option=com_virtuemart&view=productdetails&virtuemart_product_id=' . $virtuemart_product_id;
		}

		if(!empty($child->virtuemart_category_id)) {
			$child->link = 'index.php?option=com_virtuemart&view=productdetails&virtuemart_product_id=' . $virtuemart_product_id . '&virtuemart_category_id=' . $child->virtuemart_category_id;
		} else {
			$child->link = $child->canonical;
		}

		$child->quantity = $quantity;
		$child->addToCartButton = false;
		if(empty($child->categories)) $child->categories = array();

		if($this->withRating){
			if(!isset($child->rating)){
				$ratings = $this->getTable('ratings');
				$ratings->load($virtuemart_product_id,'virtuemart_product_id');
				if($ratings->published){
					$child->rating = $ratings->rating;
				}
			}
		}

		$stockhandle = VmConfig::get('stockhandle', 'none');
		$app = JFactory::getApplication ();
		if ($app->isSite () and $stockhandle == 'disableit' and ($child->product_in_stock - $child->product_ordered) <= 0) {
			vmdebug ('STOCK 0', VmConfig::get ('use_as_catalog', 0), VmConfig::get ('stockhandle', 'none'), $child->product_in_stock);
			self::$_products[$productKey] = false;
		} else {
			$product_available_date = substr($child->product_available_date,0,10);
			$current_date = date("Y-m-d");
			if (($child->product_in_stock - $child->product_ordered) < 1) {
				if ($product_available_date != '0000-00-00' and $current_date < $product_available_date) {
					$child->availability = vmText::_('COM_VIRTUEMART_PRODUCT_AVAILABLE_DATE') .': '. JHtml::_('date', $child->product_available_date, vmText::_('DATE_FORMAT_LC4'));
				} else if ($stockhandle == 'risetime' and VmConfig::get('rised_availability') and empty($child->product_availability)) {
					$child->availability =  (file_exists(VMPATH_ROOT . DS . VmConfig::get('assets_general_path') . 'images/availability/' . VmConfig::get('rised_availability'))) ? JHtml::image(JURI::root() . VmConfig::get('assets_general_path') . 'images/availability/' . VmConfig::get('rised_availability', '7d.gif'), VmConfig::get('rised_availability', '7d.gif'), array('class' => 'availability')) : vmText::_(VmConfig::get('rised_availability'));

				} else if (!empty($child->product_availability)) {
					$child->availability = (file_exists(VMPATH_ROOT . DS . VmConfig::get('assets_general_path') . 'images/availability/' . $child->product_availability)) ? JHtml::image(JURI::root() . VmConfig::get('assets_general_path') . 'images/availability/' . $child->product_availability, $child->product_availability, array('class' => 'availability')) : vmText::_($child->product_availability);
				}
			}
			else if ($product_available_date != '0000-00-00' and $current_date < $product_available_date) {
				$child->availability = vmText::_('COM_VIRTUEMART_PRODUCT_AVAILABLE_DATE') .': '. JHtml::_('date', $child->product_available_date, vmText::_('DATE_FORMAT_LC4'));
			}
			self::$_products[$productKey] = $child;
		}

		if(!self::$_products[$productKey]){
			return false;
		} else {
			//vmTime('getProduct loaded ','getProduct');
			return clone(self::$_products[$productKey]);
		}

	}

	public function loadProductPrices($productId,$virtuemart_shoppergroup_ids,$front){

		$db = JFactory::getDbo();
		if(!isset($this->_nullDate))$this->_nullDate = $db->getNullDate();
		if(!isset($this->_now)){
			$jnow = JFactory::getDate();
			$this->_now = $jnow->toSQL();
		}

		$q = 'SELECT * FROM `#__virtuemart_product_prices` WHERE `virtuemart_product_id` = "'.$productId.'" ';

		if($front){
			if($virtuemart_shoppergroup_ids and count($virtuemart_shoppergroup_ids)>0){
				$q .= ' AND (';
				$sqrpss = '';
				foreach($virtuemart_shoppergroup_ids as $sgrpId){
					$sqrpss .= ' `virtuemart_shoppergroup_id` ="'.$sgrpId.'" OR ';
				}

				$q .= $sqrpss.' `virtuemart_shoppergroup_id` IS NULL OR `virtuemart_shoppergroup_id`="0") ';
			}
			$q .= ' AND ( (`product_price_publish_up` IS NULL OR `product_price_publish_up` = "' . $db->escape($this->_nullDate) . '" OR `product_price_publish_up` <= "' .$db->escape($this->_now) . '" )
		        AND (`product_price_publish_down` IS NULL OR `product_price_publish_down` = "' .$db->escape($this->_nullDate) . '" OR product_price_publish_down >= "' . $db->escape($this->_now) . '" ) )';
		}

		$q .= ' ORDER BY `product_price` DESC';

		static $loadedProductPrices = array();
		$hash = $productId.','.implode('.',$virtuemart_shoppergroup_ids).','.(int)$front; //md5($q);

		if(!isset($loadedProductPrices[$hash])){
			$db->setQuery($q);
			$prices = $db->loadAssocList();
			$err = $db->getErrorMsg();
			if(!empty($err)){
				vmError('getProductSingle '.$err);
			} else {
				if(empty($prices)){
					$loadedProductPrices[$hash] = false;
				} else {
					$loadedProductPrices[$hash] = $prices ;
				}
			}
		}

		return $loadedProductPrices[$hash];
	}

	public function getRawProductPrices(&$product,$quantity,$virtuemart_shoppergroup_ids,$front,$withParent=0){

		$productId = $product->virtuemart_product_id===0? $this->_id:$product->virtuemart_product_id;
		$product->allPrices = $this->loadProductPrices($productId,$virtuemart_shoppergroup_ids,$front);
		$i = 0;
		$runtime = microtime (TRUE) - $this->starttime;
		$product_parent_id = $product->product_parent_id;
		//vmdebug('getRawProductPrices',$product->allPrices);
		//Check for all prices to inherited by parent products
		if(($front or $withParent) and !empty($product_parent_id)) {

			while ( $product_parent_id and (empty($product->allPrices) or count($product->allPrices)==0) ) {
				$runtime = microtime (TRUE) - $this->starttime;
				if ($runtime >= $this->maxScriptTime) {
					vmdebug ('Max execution time reached in model product getProductPrices() ', $product);
					vmError ('Max execution time reached in model product getProductPrices() ' . $product->product_parent_id);
					break;
				}
				else {
					if ($i > 10) {
						vmdebug ('Time: ' . $runtime . ' Too many child products in getProductPrices() ', $product);
						vmError ('Time: ' . $runtime . ' Too many child products in getProductPrices() ' . $product->product_parent_id);
						break;
					}
				}
				$product->allPrices = $this->loadProductPrices($product_parent_id,$virtuemart_shoppergroup_ids,$front);
				$i++;

				if(!isset($product->allPrices['salesPrice']) and $product_parent_id!=0){
					$product_parent_id = $this->getProductParentId($product_parent_id);
				}
			}
		}

		$pbC = VmConfig::get('pricesbyCurrency',false);
		if($front and $pbC){
			$app = JFactory::getApplication();
			if(!class_exists('calculationHelper')) require(VMPATH_ADMIN.DS.'helpers'.DS.'calculationh.php');
			$calculator = calculationHelper::getInstance();
			$cur = (int)$app->getUserStateFromRequest( 'virtuemart_currency_id', 'virtuemart_currency_id',$calculator->vendorCurrency );
		}

		$product->selectedPrice = null;
		if(!empty($product->allPrices) and is_array($product->allPrices)){
			$emptySpgrpPrice = 0;
			//vmdebug('Set selectedPrice to ',$product->allPrices);
			foreach($product->allPrices as $k=>$price){

				if(empty($price['price_quantity_start'])){
					$price['price_quantity_start'] = 0;
				}

				if(!empty($price['virtuemart_shoppergroup_id']) and !in_array($price['virtuemart_shoppergroup_id'],$virtuemart_shoppergroup_ids)){
					//vmdebug('Unset price, shoppergroup does not fit '.$k.' '.$price['virtuemart_shoppergroup_id'],$virtuemart_shoppergroup_ids);
					if($front) unset($product->allPrices[$k]);
					continue;
				}

				//This does not work correctly :-( , maybe someone could explain me
				//$quantityFits = (empty($price['price_quantity_end']) and $price['price_quantity_start'] <= $quantity) or ($price['price_quantity_start'] <= $quantity and $quantity <= $price['price_quantity_end']) ;
				$quantityFits = false;
				if(empty($price['price_quantity_end']) and $price['price_quantity_start'] <= $quantity){
					$quantityFits = true;
				} else if ($price['price_quantity_start'] <= $quantity and $quantity <= $price['price_quantity_end']) {
					$quantityFits = true;
				} else {
					$quantityFits = false;
				}

				if(empty($price['virtuemart_shoppergroup_id']) and empty($emptySpgrpPrice) and $quantityFits ){
					$emptySpgrpPrice = $k;
				} else if( $quantityFits ){
					$product->selectedPrice = $k;
				}

				if($front and $pbC){
					if($cur and $cur==$price['product_currency']){
						$product->selectedPrice = $k;
						break;
					}
				}
			}

			if(!isset($product->selectedPrice)){
				$product->selectedPrice = $emptySpgrpPrice;
			}

		}

		if(!isset($product->selectedPrice) or empty($product->allPrices)){
			$product->selectedPrice = 0;
			$product->allPrices[$product->selectedPrice] = $this->fillVoidPrice();
		}

	}

	var $withRating = false;
	static $_productsSingle = array();

	public function getProductSingle ($virtuemart_product_id = NULL, $front = TRUE, $quantity = 1, $withParent=false,$virtuemart_shoppergroup_ids=0) {

		if (!empty($virtuemart_product_id)) {
			$virtuemart_product_id = $this->setId ($virtuemart_product_id);
		}

		if($virtuemart_shoppergroup_ids===0){
			$usermodel = VmModel::getModel ('user');
			$currentVMuser = $usermodel->getCurrentUser ();
			if(!is_array($currentVMuser->shopper_groups)){
				$virtuemart_shoppergroup_ids = (array)$currentVMuser->shopper_groups;
			} else {
				$virtuemart_shoppergroup_ids = $currentVMuser->shopper_groups;
			}
		}

		$virtuemart_shoppergroup_idsString = 0;
		if(!empty($virtuemart_shoppergroup_ids) and is_array($virtuemart_shoppergroup_ids)){
			$virtuemart_shoppergroup_idsString = implode('.',$virtuemart_shoppergroup_ids);
		} else if(!empty($virtuemart_shoppergroup_ids)){
			$virtuemart_shoppergroup_idsString = $virtuemart_shoppergroup_ids;
		}

		$front = $front?TRUE:0;
		$productKey = $virtuemart_product_id.':'.$virtuemart_shoppergroup_idsString.':'.$quantity.':'.$front;

		if (array_key_exists ($productKey, self::$_productsSingle)) {
			return clone(self::$_productsSingle[$productKey]);
		}

		if (!empty($this->_id)) {

			$product = $this->getTable ('products');
			$product->load ($this->_id, 0, 0);

			$product->allIds = array();

			$xrefTable = $this->getTable ('product_medias');
			$product->virtuemart_media_id = $xrefTable->load ((int)$this->_id);

			// Load the shoppers the product is available to for Custom Shopper Visibility
			$product->shoppergroups = $this->getTable('product_shoppergroups')->load($this->_id);

			if (!empty($product->shoppergroups) and $front) {
				if (!class_exists ('VirtueMartModelUser')) {
					require(VMPATH_ADMIN . DS . 'models' . DS . 'user.php');
				}
				$commonShpgrps = array_intersect ($virtuemart_shoppergroup_ids, $product->shoppergroups);
				if (empty($commonShpgrps)) {
					return $this->fillVoidProduct ($front);
				}
			}

			$this->getRawProductPrices($product,$quantity,$virtuemart_shoppergroup_ids,$front,$withParent);

			$xrefTable = $this->getTable ('product_manufacturers');
			$product->virtuemart_manufacturer_id = $xrefTable->load ((int)$this->_id);

			if (!empty($product->virtuemart_manufacturer_id[0])) {
				//This is a fallback
				$mfTable = $this->getTable ('manufacturers');
				$mfTable->load ((int)$product->virtuemart_manufacturer_id[0]);
				$product = (object)array_merge ((array)$mfTable, (array)$product);
			}
			else {
				$product->virtuemart_manufacturer_id = array();
				$product->mf_name = '';
				$product->mf_desc = '';
				$product->mf_url = '';
			}

			// Load the categories the product is in
			$product->categoryItem = $this->getProductCategories ($this->_id); //We need also the unpublished categories, else the calculation rules do not work

			$product->canonCatId = false;
			$public_cats = array();
			if(!empty($product->categoryItem)){
				$tmp = array();
				foreach($product->categoryItem as $category){
					if($category['published']){
						if(!$product->canonCatId) $product->canonCatId = $category['virtuemart_category_id'];
						$public_cats[] = $category['virtuemart_category_id'];
					}
					$tmp[] = $category['virtuemart_category_id'];
				}
				$product->categories = $tmp;
			}



			if (!empty($product->categories) and is_array ($product->categories)){
				if ($front) {
					if (!class_exists ('shopFunctionsF')) {
						require(VMPATH_SITE . DS . 'helpers' . DS . 'shopfunctionsf.php');
					}

					//We must first check if we come from another category, due the canoncial link we would have always the same catgory id for a product
					//But then we would have wrong neighbored products / category and product layouts
					if(!isset($this->categoryId)){
						static $menu = null;
						if(!isset($menu)){
							$app = JFactory::getApplication();
							$menus	= $app->getMenu();
							$menu = $menus->getActive();
						}

						$this->categoryId = vRequest::getInt('virtuemart_category_id', -1);
						if($this->categoryId === -1 and !empty($menu->query['virtuemart_category_id'])){
							$this->categoryId = $menu->query['virtuemart_category_id'];
							//vRequest::setVar('virtuemart_category_id',$this->categoryId);
						} else if ( $this->categoryId === -1){
							$this->categoryId = ShopFunctionsF::getLastVisitedCategoryId();
						}
												//$last_category_id = shopFunctionsF::getLastVisitedCategoryId ();
						if ($this->categoryId!==0 and in_array ($this->categoryId, $product->categories)) {
							$product->virtuemart_category_id = $this->categoryId;
						}
						if ($this->categoryId!==0 and $this->categoryId!=$product->canonCatId){
							if(in_array($this->categoryId,$public_cats)){
								$product->virtuemart_category_id = $this->categoryId;
							}
						}
					}

				}
				//vmdebug('$product->virtuemart_category_id',$product->virtuemart_category_id);
				if(empty($product->virtuemart_category_id)){
					$virtuemart_category_id = vRequest::getInt ('virtuemart_category_id', 0);
					if ($virtuemart_category_id!==0 and in_array ($virtuemart_category_id, $product->categories)) {
						$product->virtuemart_category_id = $virtuemart_category_id;
					} else if(!empty($product->canonCatId)) {
						$product->virtuemart_category_id = $product->canonCatId;
					//} else if (!$front and !empty($product->categories) and is_array ($product->categories) and array_key_exists (0, $product->categories)) {
						//why the restriction why we should use it for BE only?
					} else if (!empty($product->categories) and is_array ($product->categories) and array_key_exists (0, $product->categories)) {
						$product->virtuemart_category_id = $product->categories[0];
						//vmdebug('I take for product the main category ',$product->virtuemart_category_id,$product->categories);
					}
				}
			}

			if(empty($product->virtuemart_category_id)) $product->virtuemart_category_id = $product->canonCatId;

			if(!empty($product->virtuemart_category_id)){

				$found = false;
				foreach($product->categoryItem as $category){

					if($category['virtuemart_category_id'] == $product->virtuemart_category_id){
						$product->ordering = $category['ordering'];
						//This is the ordering id in the list to store the ordering notice by Max Milbers
						$product->id = $category['id'];
						$product->category_name = $category['category_name'];
						$found = true;
						break;
					}
				}
				if(!$found){
					$product->ordering = $this->_autoOrder++;
					$product->id = $this->_autoOrder;
					vmdebug('$product->virtuemart_category_id no ordering stored for product '.$this->_id);
				}

			} else {
				$product->category_name = '';
				$product->virtuemart_category_id = '';
				$product->ordering = '';
				$product->id = $this->_autoOrder++;
			}

			// Check the stock level
			if (empty($product->product_in_stock)) {
				$product->product_in_stock = 0;
			}

			self::$_productsSingle[$productKey] = $product;
		}
		else {
			self::$_productsSingle[$productKey] = $this->fillVoidProduct ($front);
		}

		return clone(self::$_productsSingle[$productKey]);
	}

	/**
	 * This fills the empty properties of a product
	 * todo add if(!empty statements
	 *
	 * @author Max Milbers
	 * @param unknown_type $product
	 * @param unknown_type $front
	 */
	private function fillVoidProduct ($front = TRUE) {

		/* Load an empty product */
		$product = $this->getTable ('products');
		$product->load ();

		/* Add optional fields */
		$product->virtuemart_manufacturer_id = NULL;
		$product->virtuemart_product_price_id = NULL;

		if (!class_exists ('VirtueMartModelVendor')) {
			require(VMPATH_ADMIN . DS . 'models' . DS . 'vendor.php');
		}

		$product->selectedPrice = 0;
		$product->allPrices[0] = $this->fillVoidPrice();
		$product->categories = array();
		if ($front) {
			$product->link = '';
			$product->virtuemart_category_id = 0;
			$product->virtuemart_shoppergroup_id = 0;
			$product->mf_name = '';
			$product->packaging = '';
			$product->related = '';
			$product->box = '';
			$product->addToCartButton = false;
		}
		$product->virtuemart_vendor_id = vmAccess::isSuperVendor();
		return $product;
	}

	public function fillVoidPrice(){

		$prices = array();
		$prices['product_price'] = '';
		$prices['virtuemart_product_price_id'] = 0;
		$prices['product_currency'] = null;
		$prices['price_quantity_start'] = null;
		$prices['price_quantity_end'] = null;
		$prices['product_price_publish_up'] = null;
		$prices['product_price_publish_down'] = null;
		$prices['product_tax_id'] = 0;
		$prices['product_discount_id'] = null;
		$prices['product_override_price'] = null;
		$prices['override'] = null;
		$prices['categories'] = array();
		$prices['shoppergroups'] = array();
		$prices['virtuemart_shoppergroup_id'] = null;

		return $prices;
	}

	/**
	 * Load  the product category
	 *
	 * @author Max Milbers
	 * @return array list of categories product is in
	 */
	public function getProductCategories ($virtuemart_product_id) {

		static $prodCats = array();

		if(empty($virtuemart_product_id)) return false;

		if(!isset($prodCats[$virtuemart_product_id])){
			$categories = array();
			$db = JFactory::getDbo();

			$q = 'SELECT * FROM `#__virtuemart_product_categories`  WHERE `virtuemart_product_id` = ' . (int)$virtuemart_product_id;
			$db->setQuery ($q);
			$categoryIds = $db->loadAssocList();

			$catTable = $this->getTable('categories');

			foreach($categoryIds as $categoryId){
				$tmp = (array)$catTable->load($categoryId['virtuemart_category_id']);
				$tmp['id'] = $categoryId['id'];
				$tmp['ordering'] = $categoryId['ordering'];
				$categories[] = $tmp;
			}
			$prodCats[$virtuemart_product_id] = $categories;
		}

		return $prodCats[$virtuemart_product_id];
	}


	/**
	 * Get the products in a given category
	 *
	 * @access public
	 * @param int $virtuemart_category_id the category ID where to get the products for
	 * @return array containing product objects
	 * @deprecated
	 */
	public function getProductsInCategory ($categoryId) {

		$ids = $this->sortSearchListQuery (TRUE, $categoryId);
		$this->products = $this->getProducts ($ids);
		return $this->products;
	}


	/**
	 * Loads different kind of product lists.
	 * you can load them with calculation or only published onces, very intersting is the loading of groups
	 * valid values are latest, topten, featured, recent.
	 *
	 * The function checks itself by the config if the user is allowed to see the price or published products
	 *
	 * @author Max Milbers
	 */
	public function getProductListing ($group = FALSE, $nbrReturnProducts = FALSE, $withCalc = TRUE, $onlyPublished = TRUE, $single = FALSE, $filterCategory = TRUE, $category_id = 0) {

		$app = JFactory::getApplication ();
		if ($app->isSite ()) {
			$front = TRUE;
			if (!vmAccess::manager()) {
				$onlyPublished = TRUE;
				$withCalc = (int)VmConfig::get ('show_prices', 1);
			}
		}
		else {
			$front = FALSE;
		}

		$this->setFilter ();
		if ($filterCategory === TRUE) {
			if ($category_id) {
				$this->virtuemart_category_id = $category_id;
			}
		}
		else {
			$this->virtuemart_category_id = FALSE;
		}
		$ids = $this->sortSearchListQuery ($onlyPublished, $this->virtuemart_category_id, $group, $nbrReturnProducts);

		//quickndirty hack for the BE list, we can do that, because in vm2.1 this is anyway fixed correctly
		$this->listing = TRUE;
		$products = $this->getProducts ($ids, $front, $withCalc, $onlyPublished, $single);
		$this->listing = FALSE;
		return $products;
	}

	/**
	 * overriden getFilter to persist filters
	 *
	 * @author OSP
	 */
	public function setFilter () {

		$app = JFactory::getApplication ();
		if (!$app->isSite ()) { //persisted filter only in admin
			$view = vRequest::getCmd ('view');
			$mainframe = JFactory::getApplication ();
			$this->virtuemart_category_id = $mainframe->getUserStateFromRequest ('com_virtuemart.' . $view . '.filter.virtuemart_category_id', 'virtuemart_category_id', 0, 'int');
			$this->setState ('virtuemart_category_id', $this->virtuemart_category_id);
			$this->virtuemart_manufacturer_id = $mainframe->getUserStateFromRequest ('com_virtuemart.' . $view . '.filter.virtuemart_manufacturer_id', 'virtuemart_manufacturer_id', 0, 'int');
			$this->setState ('virtuemart_manufacturer_id', $this->virtuemart_manufacturer_id);
		}
		else {
			$this->virtuemart_category_id = vRequest::getInt ('virtuemart_category_id', FALSE);
		}
	}

	/**
	 * Returns products for given array of ids
	 *
	 * @author Max Milbers
	 * @param int $productIds
	 * @param boolean $front
	 * @param boolean $withCalc
	 * @param boolean $onlyPublished
	 */
	public function getProducts ($productIds, $front = TRUE, $withCalc = TRUE, $onlyPublished = TRUE, $single = FALSE) {

		if (empty($productIds)) {
			return array();
		}

		$usermodel = VmModel::getModel ('user');
		$currentVMuser = $usermodel->getCurrentUser ();
		if(!is_array($currentVMuser->shopper_groups)){
			$virtuemart_shoppergroup_ids = (array)$currentVMuser->shopper_groups;
		} else {
			$virtuemart_shoppergroup_ids = $currentVMuser->shopper_groups;
		}

		$maxNumber = VmConfig::get ('absMaxProducts', 700);
		$products = array();
		$i = 0;
		if ($single) {

			foreach ($productIds as $id) {

				if ($product = $this->getProductSingle ((int)$id, $front,1,false,$virtuemart_shoppergroup_ids)) {
					$products[] = $product;
					$i++;
				}
				if ($i > $maxNumber) {
					vmdebug ('Better not to display more than ' . $maxNumber . ' products');
					return $products;
				}
			}
		}
		else {

			foreach ($productIds as $id) {
				if ($product = $this->getProduct ((int)$id, $front, $withCalc, $onlyPublished,1,$virtuemart_shoppergroup_ids)) {
					$products[] = $product;
					$i++;
				}
				if ($i > $maxNumber) {
					vmdebug ('Better not to display more than ' . $maxNumber . ' products');
					return $products;
				}
			}
		}

		return $products;
	}


	/**
	 * This function retrieves the "neighbor" products of a product specified by $virtuemart_product_id
	 * Neighbors are the previous and next product in the current list
	 *
	 * @author Max Milbers
	 * @param object $product The product to find the neighours of
	 * @return array
	 */
	public function getNeighborProducts ($product, $onlyPublished = TRUE, $max = 1) {

		$db = JFactory::getDBO ();
		$neighbors = array('previous' => '', 'next' => '');

		$oldDir = $this->filter_order_Dir;


		if($this->filter_order_Dir=='ASC'){
			$direction = 'DESC';
			$op = '<=';
		} else {
			$direction = 'ASC';
			$op = '>=';
		}
		$this->filter_order_Dir = $direction;

		//We try the method to get exact the next product, the other method would be to get the list of the browse view again and do a match
		//with the product id and giving back the neighbours
		$this->_onlyQuery = true;
		$queryArray =  $this->sortSearchListQuery($onlyPublished,(int)$product->virtuemart_category_id,false,1,array('product_name'));
//vmdebug('my query stuff ',$queryArray);
		if(isset($queryArray[1])){

			$pos= strpos($queryArray[3],'ORDER BY');
			$sp = array();

			$orderByName = '`l`.product_name, virtuemart_product_id';
			$whereorderByName = '`l`.product_name';
			$orderByValue = $product->product_name;
			if($pos){
				$orderByName = trim(substr ($queryArray[3],($pos+8)) );

				$orderByNameMain = $orderByName;
				if($cpos = strpos($orderByName,',')!==false){
					$t = explode(',',$orderByName);
					if(!empty($t[0])){
						$orderByNameMain = $t[0];
					}
					$orderByNameMain = str_replace(array('DESC','ASC'), '',$orderByNameMain);
				}

				$orderByNameMain = trim(str_replace('`','',$orderByNameMain));

				if($orderByNameMain=='product_price'){
					if(isset($product->prices['product_price'])){
						$product->product_price = $product->prices['product_price'];
					} else {
						$product->product_price = 0.0;
					}
				}

				if(strpos($orderByNameMain,'.')){
					$sp = explode('.',$orderByNameMain);
					$orderByNameMain = $sp[count($sp)-1];
				}

				$tableLangKeys = array('product_name','product_s_desc','product_desc');
				if(isset($product->$orderByNameMain)){
					$orderByValue = $product->$orderByNameMain;
					if(isset($sp[0])){
						$orderByNameMain = '`'.$sp[0].'`.'.$orderByNameMain;
					} else if(in_array($orderByNameMain,$tableLangKeys)){
						$orderByNameMain = '`l`.'.$orderByNameMain;
					}
				}
				$whereorderByName = $orderByNameMain;
			}

			$selectLang = ' `l`.`product_name`';

			$q = 'SELECT p.`virtuemart_product_id`,'.$selectLang.','.$whereorderByName.' FROM `#__virtuemart_products` as p';

			$joinT = '';
			if(is_array($queryArray[1])){
				$joinT = implode('',$queryArray[1]);
			}

			/*if(strpos($orderByName,'virtuemart_product_id')!==false){
				$q .= $joinT . ' WHERE (' . implode (' AND ', $queryArray[2]) . ') AND p.`virtuemart_product_id`'.$op.'"'.$product->virtuemart_product_id.'" ';
			} else {*/
				$q .= $joinT . ' WHERE (' . implode (' AND ', $queryArray[2]) . ') AND p.`virtuemart_product_id`!="'.$product->virtuemart_product_id.'" ';
			//}


			$alreadyFound = '';
			foreach ($neighbors as &$neighbor) {

				if(!empty($alreadyFound)) $alreadyFound = 'AND p.`virtuemart_product_id`!="'.$alreadyFound.'"';
				$qm = $alreadyFound.' AND '.$whereorderByName.' '.$op.' "'.$orderByValue.'"  ORDER BY '.$orderByName.' LIMIT 1';
				$db->setQuery ($q.$qm);
				//vmdebug('getneighbors '.$q.$qm);
				if ($result = $db->loadAssocList ()) {
					$neighbor = $result;
					$alreadyFound = $result[0]['virtuemart_product_id'];
				}

				if($this->filter_order_Dir=='ASC'){
					$direction = 'DESC';
					$op = '<=';

				} else {
					$direction = 'ASC';
					$op = '>=';
				}
				$orderByName = str_replace($this->filter_order_Dir,$direction,$orderByName);
			}
		}

		$this->filter_order_Dir = $oldDir;
		$this->_onlyQuery = false;
		return $neighbors;
	}


	/* reorder product in one category
	 * TODO this not work perfect ! (Note by Patrick Kohl)
	*/
	function saveorder ($cid = array(), $order, $filter = NULL) {

		vRequest::vmCheckToken();

		$db = JFactory::getDbo();
		$virtuemart_category_id = vRequest::getInt ('virtuemart_category_id', 0);

		$q = 'SELECT `id`,`ordering` FROM `#__virtuemart_product_categories`
			WHERE virtuemart_category_id=' . (int)$virtuemart_category_id . '
			ORDER BY `ordering` ASC';
		$db->setQuery ($q);
		$pkey_orders = $db->loadObjectList ();

		$tableOrdering = array();
		foreach ($pkey_orders as $orderTmp) {
			$tableOrdering[$orderTmp->id] = $orderTmp->ordering;
		}
		// set and save new ordering
		foreach ($order as $key => $ord) {
			$tableOrdering[$key] = $ord;
		}
		asort ($tableOrdering);
		$i = 1;
		$ordered = 0;
		foreach ($tableOrdering as $key => $ord) {

			$db->setQuery ('UPDATE `#__virtuemart_product_categories`
					SET `ordering` = ' . $i . '
					WHERE `id` = ' . (int)$key . ' ');
			if (!$db->query ()) {
				vmError ($db->getErrorMsg ());
				return FALSE;
			}
			$ordered++;
			$i++;
		}
		if ($ordered) {
			$msg = vmText::sprintf ('COM_VIRTUEMART_ITEMS_MOVED', $ordered);
		}
		else {
			$msg = vmText::_ ('COM_VIRTUEMART_ITEMS_NOT_MOVED');
		}
		JFactory::getApplication ()->redirect ('index.php?option=com_virtuemart&view=product&virtuemart_category_id=' . $virtuemart_category_id, $msg);

	}

	/**
	 * Moves the order of a record
	 *
	 * @param integer The increment to reorder by
	 */
	function move ($direction, $filter = NULL) {

		vRequest::vmCheckToken();

		// Check for request forgeries
		$table = $this->getTable ('product_categories');
		$table->move ($direction);

		JFactory::getApplication ()->redirect ('index.php?option=com_virtuemart&view=product&virtuemart_category_id=' . vRequest::getInt ('virtuemart_category_id', 0));
	}

    /**
     * Store a product
     *
     * @author Max Milbers
     * @param $product reference
     * @param bool $isChild Means not that the product is child or not. It means if the product should be threated as child
     * @return bool
     */
    public function store (&$product) {

		vRequest::vmCheckToken();

		if(!vmAccess::manager('product.edit')){
			vmError('You are not a vendor or administrator, storing of product cancelled');
			return FALSE;
		}

		if ($product) {
			$data = (array)$product;
		}
		$isChild = FALSE;
		if(!empty($data['isChild'])) $isChild = $data['isChild'];

		if (isset($data['intnotes'])) {
			$data['intnotes'] = trim ($data['intnotes']);
		}

		// Setup some place holders
		$product_data = $this->getTable ('products');

		if(!empty($data['virtuemart_product_id'])){
			$product_data -> load($data['virtuemart_product_id']);
		}
		if( (empty($data['virtuemart_product_id']) or empty($product_data->virtuemart_product_id)) and !vmAccess::manager('product.create')){
			vmWarn('Insufficient permission to create product');
			return false;
		}
		if(!vmAccess::manager('product.edit.state')){
			if( (empty($data['virtuemart_product_id']) or empty($product_data->virtuemart_product_id))){
				$data['published'] = 0;
			} else {
				$data['published'] = $product_data->published;
			}
		}

		//Set the decimals like product packaging
		foreach($this->decimals as $decimal){
			if (array_key_exists ($decimal, $data)) {
				if(!empty($data[$decimal])){
					$data[$decimal] = str_replace(',','.',$data[$decimal]);
					//vmdebug('Store product '.$data['virtuemart_product_id'].', set $decimal '.$decimal.' = '.$data[$decimal]);
				} else {
					$data[$decimal] = null;
					$product_data->$decimal = null;
					//vmdebug('Store product '.$data['virtuemart_product_id'].', set $decimal '.$decimal.' = null');
				}
			}
		}

		//We prevent with this line, that someone is storing a product as its own parent
		if(!empty($product_data->product_parent_id) and $product_data->product_parent_id == $data['virtuemart_product_id']){
			$product_data->product_parent_id = 0;
			unset($data['product_parent_id']);
		}

		$stored = $product_data->bindChecknStore ($data, false);

		if(!$stored ){
			vmError('You are not an administrator or the correct vendor, storing of product cancelled');
			return FALSE;
		}

		$this->_id = $data['virtuemart_product_id'] = (int)$product_data->virtuemart_product_id;

		if (empty($this->_id)) {
			vmError('Product not stored, no id');
			return FALSE;
		}

		//We may need to change this, the reason it is not in the other list of commands for parents
		if (!$isChild) {
			$modelCustomfields = VmModel::getModel ('Customfields');
			$modelCustomfields->storeProductCustomfields ('product', $data, $product_data->virtuemart_product_id);
		}

		// Get old IDS
		$old_price_ids = $this->loadProductPrices($this->_id,array(0),false);

		if (isset($data['mprices']['product_price']) and count($data['mprices']['product_price']) > 0){

			foreach($data['mprices']['product_price'] as $k => $product_price){

				$pricesToStore = array();
				$pricesToStore['virtuemart_product_id'] = $this->_id;
				$pricesToStore['virtuemart_product_price_id'] = (int)$data['mprices']['virtuemart_product_price_id'][$k];

				if (!$isChild){
					//$pricesToStore['basePrice'] = $data['mprices']['basePrice'][$k];
					$pricesToStore['product_override_price'] = $data['mprices']['product_override_price'][$k];
					$pricesToStore['override'] = isset($data['mprices']['override'][$k])?(int)$data['mprices']['override'][$k]:0;
					$pricesToStore['virtuemart_shoppergroup_id'] = (int)$data['mprices']['virtuemart_shoppergroup_id'][$k];
					$pricesToStore['product_tax_id'] = (int)$data['mprices']['product_tax_id'][$k];
					$pricesToStore['product_discount_id'] = (int)$data['mprices']['product_discount_id'][$k];
					$pricesToStore['product_currency'] = (int)$data['mprices']['product_currency'][$k];
					$pricesToStore['product_price_publish_up'] = $data['mprices']['product_price_publish_up'][$k];
					$pricesToStore['product_price_publish_down'] = $data['mprices']['product_price_publish_down'][$k];
					$pricesToStore['price_quantity_start'] = (int)$data['mprices']['price_quantity_start'][$k];
					$pricesToStore['price_quantity_end'] = (int)$data['mprices']['price_quantity_end'][$k];
				}

				if (!$isChild and isset($data['mprices']['use_desired_price'][$k]) and $data['mprices']['use_desired_price'][$k] == "1") {
					if (!class_exists ('calculationHelper')) {
						require(VMPATH_ADMIN . DS . 'helpers' . DS . 'calculationh.php');
					}
					$calculator = calculationHelper::getInstance ();
					$pricesToStore['salesPrice'] = $data['mprices']['salesPrice'][$k];
					$pricesToStore['product_price'] = $data['mprices']['product_price'][$k] = $calculator->calculateCostprice ($this->_id, $pricesToStore);
					unset($data['mprices']['use_desired_price'][$k]);
				} else {
					if(isset($data['mprices']['product_price'][$k]) ){
						$pricesToStore['product_price'] = $data['mprices']['product_price'][$k];
					}

				}

				if ($isChild) $childPrices = $this->loadProductPrices($this->_id,array(0),false);

				if ((isset($pricesToStore['product_price']) and $pricesToStore['product_price']!='' and $pricesToStore['product_price']!=='0') || (isset($childPrices) and count($childPrices)>1)) {

					if ($isChild) {

						if(is_array($old_price_ids) and count($old_price_ids)>1){

							//We do not touch multiple child prices. Because in the parent list, we see no price, the gui is
							//missing to reflect the information properly.
							$pricesToStore = false;
							$old_price_ids = array();
						} else {
							unset($data['mprices']['product_override_price'][$k]);
							unset($pricesToStore['product_override_price']);
							unset($data['mprices']['override'][$k]);
							unset($pricesToStore['override']);
						}

					}

					if($pricesToStore){
						$toUnset = array();
						if (!empty($old_price_ids) and count($old_price_ids) ) {
							foreach($old_price_ids as $key => $oldprice){
								if($pricesToStore['virtuemart_product_price_id'] == $oldprice['virtuemart_product_price_id'] ){
									$pricesToStore = array_merge($oldprice,$pricesToStore);
									$toUnset[] = $key;
								}
							}
						}
						$this->updateXrefAndChildTables ($pricesToStore, 'product_prices',$isChild);

						foreach($toUnset as $key){
							unset( $old_price_ids[ $key ] );
						}
					}
				}
			}
		}
		if (!empty($old_price_ids) and count($old_price_ids) ) {
			$oldPriceIdsSql = array();
			foreach($old_price_ids as $oldPride){
				$oldPriceIdsSql[] = $oldPride['virtuemart_product_price_id'];
			}
			$db = JFactory::getDbo();
			// delete old unused Prices
			$db->setQuery( 'DELETE FROM `#__virtuemart_product_prices` WHERE `virtuemart_product_price_id` in ("'.implode('","', $oldPriceIdsSql ).'") ');
			$db->execute();
			$err = $db->getErrorMsg();
			if(!empty($err)){
				vmWarn('In store prodcut, deleting old price error',$err);
			}
		}

		if (!empty($data['childs'])) {
			foreach ($data['childs'] as $productId => $child) {
				if($productId!=$data['virtuemart_product_id']){

					if(empty($child['product_parent_id'])) $child['product_parent_id'] = $data['virtuemart_product_id'];
					$child['virtuemart_product_id'] = $productId;

					if(!empty($child['product_parent_id']) and $child['product_parent_id'] == $child['virtuemart_product_id']){
						$child['product_parent_id'] = 0;
					}

					$child['isChild'] = $this->_id;
					$this->store ($child);
				}
			}
		}

		if (!$isChild) {

			$data = $this->updateXrefAndChildTables ($data, 'product_shoppergroups');

			$data = $this->updateXrefAndChildTables ($data, 'product_manufacturers');

			if (!empty($data['categories']) && count ($data['categories']) > 0) {
				if(VmConfig::get('multix','none')!='none' and !vmAccess::manager('managevendors')){
					$vendorId = vmAccess::isSuperVendor();
					$vM = VmModel::getModel('vendor');
					$ven = $vM->getVendor($vendorId);
					if($ven->max_cats_per_product>=0){
						while($ven->max_cats_per_product<count($data['categories'])){
							array_pop($data['categories']);
						}
					}

				}
				$data['virtuemart_category_id'] = $data['categories'];
			} else {
				$data['virtuemart_category_id'] = array();
			}
			$data = $this->updateXrefAndChildTables ($data, 'product_categories');

			// Update waiting list
			//TODO what is this doing?
			if (!empty($data['notify_users'])) {
				if ($data['product_in_stock'] > 0 && $data['notify_users'] == '1') {
					$waitinglist = VmModel::getModel ('Waitinglist');
					$waitinglist->notifyList ($data['virtuemart_product_id']);
				}
			}

			// Process the images
			$mediaModel = VmModel::getModel ('Media');
			$mediaModel->storeMedia ($data, 'product');

		}

		$cache = JFactory::getCache('com_virtuemart_cat_manus','callback');
		$cache->clean();
        $dispatcher = JDispatcher::getInstance();
		$dispatcher->trigger('plgVmAfterStoreProduct',array(&$data, &$product_data));
		
		return $product_data->virtuemart_product_id;
	}

	public function updateXrefAndChildTables ($data, $tableName, $preload = FALSE) {

		vRequest::vmCheckToken();
		//First we load the xref table, to get the old data
		$product_table_Parent = $this->getTable ($tableName);
		//We must go that way, because the load function of the vmtablexarry
		// is working different.
		if($preload){
			$product_table_Parent->load($data['virtuemart_product_id']);
		}
		$product_table_Parent->bindChecknStore ($data);

		return $data;

	}

	/**
	 * This function creates a child for a given product id
	 *
	 * @author Max Milbers
	 * @author Patrick Kohl
	 * @param int id of parent id
	 */
	public function createChild ($id) {

		if(!vmAccess::manager('product.create')){
			vmWarn('Insufficient permission to create product');
			return false;
		}

		// created_on , modified_on
		$db = JFactory::getDBO ();

		$db->setQuery ('SELECT `product_name`,`slug`,`virtuemart_vendor_id` FROM `#__virtuemart_products` JOIN `#__virtuemart_products_' . VmConfig::$vmlang . '` as l using (`virtuemart_product_id`) WHERE `virtuemart_product_id`=' . (int)$id);
		$parent = $db->loadObject ();
		$prodTable = $this->getTable ('products');

		$childs = $this->getProductChildIds ($id);
		if($childs){
			$lastCId = end($childs);
			reset($childs);
			if(!empty($lastCId)){
				$db->setQuery ('SELECT `product_name`,`slug`,`virtuemart_vendor_id` FROM `#__virtuemart_products` JOIN `#__virtuemart_products_' . VmConfig::$vmlang . '` as l using (`virtuemart_product_id`) WHERE `virtuemart_product_id`=' . (int)$lastCId);
				$lastChild = $db->loadObject ();
				if(!empty($lastChild->slug)){
					$prodTable->slug = $lastChild->slug;
				}
			}
		} else if(empty($parent->slug)){
			$prodTable->slug = $parent->product_name;
		} else {
			$prodTable->slug = $parent->slug;
		}

		$prodTable->checkCreateUnique('#__virtuemart_products_' . VmConfig::$vmlang,'slug');
		//$newslug = $prodTable->checkCreateUnique('products_' . VmConfig::$vmlang,$parent->slug);
		$data = array('product_name' => $parent->product_name, 'slug' => $prodTable->slug, 'virtuemart_vendor_id' => (int)$prodTable->virtuemart_vendor_id, 'product_parent_id' => (int)$id);

		$prodTable = $this->getTable ('products');
		$prodTable->bindChecknStore ($data);

		return $data['virtuemart_product_id'];
	}

	/**
	 * Creates a clone of a given product id
	 *
	 * @author Max Milbers
	 * @param int $virtuemart_product_id
	 */

	public function createClone ($id) {

		if(!vmAccess::manager('product.create')){
			vmWarn('Insufficient permission to create product');
			return false;
		}
		$product = $this->getProduct ($id, FALSE, FALSE, FALSE);
		$product->field = $this->productCustomsfieldsClone ($id);
		$product->virtuemart_product_id = $product->virtuemart_product_price_id = 0;
		$product->mprices = $this->productPricesClone ($id);

		//Lets check if the user is admin or the mainvendor
		//Todo, what was the idea behind this? created_on should be always set to new?
		if(vmAccess::manager()){
			$product->created_on = "0000-00-00 00:00:00";
			$product->created_by = 0;
		}
		$product->slug = $product->slug . '-' . $id;
		$product->originId = $id;
		$product->published=0;
		$product->product_sales=0;
		$product->product_ordered=0;
		$newId = $this->store ($product);
		$product->virtuemart_product_id = $newId;
		JPluginHelper::importPlugin ('vmcustom');
		$dispatcher = JDispatcher::getInstance ();
		$result=$dispatcher->trigger ('plgVmCloneProduct', array($product));

		$langs = VmConfig::get('active_languages', array());
		if ($langs and count($langs)>1){
			$langTable = $this->getTable('products');
			foreach($langs as $lang){
				if($lang==VmConfig::$vmlangTag) continue;
				$langTable->emptyCache();
				$langTable->setLanguage($lang);
				//Disables the language fallback
				$langTable->_ltmp = true;
				$langTable->load($id);
				if($langTable->_loaded){
					if(!empty($langTable->virtuemart_product_id)){
						$langTable->virtuemart_product_id = $newId;
						$langTable->bindChecknStore($langTable);
					}
				}
			}
		}

		return $product->virtuemart_product_id;
	}
	
	private function productPricesClone ($virtuemart_product_id) {

		$db = JFactory::getDBO ();
		$q = "SELECT * FROM `#__virtuemart_product_prices`";
		$q .= " WHERE `virtuemart_product_id` = " . $virtuemart_product_id;
		$db->setQuery ($q);
		$prices = $db->loadAssocList ();

		if ($prices) {
			foreach ($prices as $k => &$price) {
				unset($price['virtuemart_product_id'], $price['virtuemart_product_price_id']);
				if(empty($mprices[$k])) $mprices[$k] = array();
				foreach ($price as $i => $value) {
					if(empty($mprices[$i])) $mprices[$i] = array();
					$mprices[$i][$k] = $value;
				}
			}
			return $mprices;
		}
		else {
			return NULL;
		}
	}

	/* look if whe have a product type */
	private function productCustomsfieldsClone ($virtuemart_product_id) {

		$db = JFactory::getDBO ();
		$q = "SELECT * FROM `#__virtuemart_product_customfields`";
		$q .= " WHERE `virtuemart_product_id` = " . $virtuemart_product_id;
		$db->setQuery ($q);
		$customfields = $db->loadAssocList ();
		if ($customfields) {
			foreach ($customfields as &$customfield) {
				unset($customfield['virtuemart_product_id'], $customfield['virtuemart_customfield_id']);
			}
			return $customfields;
		}
		else {
			return NULL;
		}
	}

	/**
	 * removes a product and related table entries
	 *
	 * @author Max Milberes
	 */
	public function remove ($ids) {

		if(!vmAccess::manager('product.delete')){
			vmWarn('Insufficient permissions to delete product');
			return false;
		}

		$table = $this->getTable ($this->_maintablename);

		$cats = $this->getTable ('product_categories');
		$customfields = $this->getTable ('product_customfields');
		$manufacturers = $this->getTable ('product_manufacturers');
		$medias = $this->getTable ('product_medias');
		$prices = $this->getTable ('product_prices');
		$shop = $this->getTable ('product_shoppergroups');

		$rating = $this->getTable ('ratings');
		$review = $this->getTable ('rating_reviews');
		$votes = $this->getTable ('rating_votes');

		$ok = TRUE;
		foreach ($ids as $id) {

			$childIds = $this->getProductChildIds ($id);
			if (!empty($childIds)) {
				vmError (vmText::_ ('COM_VIRTUEMART_PRODUCT_CANT_DELETE_CHILD'));
				$ok = FALSE;
				continue;
			}

			if (!$table->delete ($id)) {
				$ok = FALSE;
			}

			if (!$cats->delete ($id, 'virtuemart_product_id')) {
				$ok = FALSE;
			}

			if (!$customfields->delete ($id, 'virtuemart_product_id')) {
				$ok = FALSE;
			}

			$db = JFactory::getDbo();
			$q = 'SELECT `virtuemart_customfield_id` FROM `#__virtuemart_product_customfields` as pc ';
			$q .= 'LEFT JOIN `#__virtuemart_customs`as c using (`virtuemart_custom_id`) WHERE pc.`customfield_value` = "' . $id . '" AND `field_type`= "R"';
			$db->setQuery($q);
			$list = $db->loadColumn();

			if ($list) {
				$listInString = implode(',',$list);
				//Delete media xref
				$query = 'DELETE FROM `#__virtuemart_product_customfields` WHERE `virtuemart_customfield_id` IN ('. $listInString .') ';
				$db->setQuery($query);
				if(!$db->execute()){
					vmError( $db->getError() );
				}
			}

			if (!$manufacturers->delete ($id, 'virtuemart_product_id')) {
				$ok = FALSE;
			}

			if (!$medias->delete ($id, 'virtuemart_product_id')) {
				$ok = FALSE;
			}

			if (!$prices->delete ($id, 'virtuemart_product_id')) {
				$ok = FALSE;
			}

			if (!$shop->delete ($id, 'virtuemart_product_id')) {
				$ok = FALSE;
			}

			if (!$rating->delete ($id, 'virtuemart_product_id')) {
				$ok = FALSE;
			}

			if (!$review->delete ($id, 'virtuemart_product_id')) {
				$ok = FALSE;
			}

			if (!$votes->delete ($id, 'virtuemart_product_id')) {
				$ok = FALSE;
			}

			// delete plugin on product delete
			// $ok must be set to false if an error occurs
			JPluginHelper::importPlugin ('vmcustom');
			$dispatcher = JDispatcher::getInstance ();
			$dispatcher->trigger ('plgVmOnDeleteProduct', array($id, &$ok));
		}

		return $ok;
	}


	/**
	 * Gets the price for a variant
	 *
	 * @author Max Milbers
	 */
	public function getPrice ($product, $quantity) {

		if (!is_object ($product)) {
			$product = $this->getProduct ($product, TRUE, FALSE, TRUE,$quantity);
		}

		if (empty($product->customfields) and !empty($product->allIds)) {
			$customfieldsModel = VmModel::getModel ('Customfields');
			$product->modificatorSum = null;
			$product->customfields = $customfieldsModel->getCustomEmbeddedProductCustomFields ($product->allIds);
		}

		// Loads the product price details
		if (!class_exists ('calculationHelper')) {
			require(VMPATH_ADMIN . DS . 'helpers' . DS . 'calculationh.php');
		}
		$calculator = calculationHelper::getInstance ();

		// Calculate the modificator
		$customfieldsModel = VmModel::getModel('Customfields');
		$variantPriceModification = $customfieldsModel->calculateModificators ($product);

		$prices = $calculator->getProductPrices ($product, $variantPriceModification, $quantity);

		return $prices;

	}


	/**
	 * Get the Order By Select List
	 *
	 * notice by Max Milbers html tags should never be in a model. This function should be moved to a helper or simular,...
	 *
	 * @author Kohl Patrick, Max Milbers
	 * @access public
	 * @param $fieds from config Back-end
	 * @return $orderByList
	 * Order,order By, manufacturer and category link List to echo Out
	 **/
	function getOrderByList ($virtuemart_category_id = FALSE) {

		$getArray = vRequest::getGet(FILTER_SANITIZE_STRING);

		$fieldLink = '';

		foreach ($getArray as $key => $value) {

			$key = vRequest::filter($key,FILTER_SANITIZE_SPECIAL_CHARS,FILTER_FLAG_ENCODE_LOW);
			$value = vRequest::filter($value,FILTER_SANITIZE_SPECIAL_CHARS,FILTER_FLAG_ENCODE_LOW);

			if (is_array ($value)) {
				foreach ($value as $k => $v) {
					if( $v == '') continue;
					$fieldLink .= '&' . urlencode($key) . '[' . urlencode($k) . ']' . '=' . urlencode($v);
				}
			}
			else {
				if($key=='dir' or $key=='orderby') continue;
				if($value == '') continue;
				$fieldLink .= '&' . urlencode($key) . '=' . urlencode($value);
			}
		}

		$fieldLink = 'index.php?'. ltrim ($fieldLink,'&');

		$orderDirLink = '';
		$orderDirConf = VmConfig::get ('prd_brws_orderby_dir');
		$orderDir = vRequest::getCmd ('dir', $orderDirConf);
		if ($orderDir != $orderDirConf ) {
			$orderDirLink .= '&dir=' . $orderDir;	//was '&order='
		}

		$orderbyTxt = '';
		$orderby = vRequest::getString ('orderby', VmConfig::get ('browse_orderby_field'));
		$orderby = $this->checkFilterOrder ($orderby);

		$orderbyCfg = VmConfig::get ('browse_orderby_field');
		if ($orderby != $orderbyCfg) {
			$orderbyTxt = '&orderby=' . $orderby;
		}

		$manufacturerTxt = '';
		$manufacturerLink = '';
		if (VmConfig::get ('show_manufacturers')) {

			$manuM = VmModel::getModel('manufacturer');
			vmSetStartTime('mcaching');
			$mlang=(!VmConfig::get('prodOnlyWLang',false) and VmConfig::$defaultLang!=VmConfig::$vmlang and Vmconfig::$langCount>1);
			if(true){
				$cache = JFactory::getCache('com_virtuemart_cat_manus','callback');
				$cache->setCaching(true);
				$manufacturers = $cache->call( array( 'VirtueMartModelManufacturer', 'getManufacturersOfProductsInCategory' ),$virtuemart_category_id,VmConfig::$vmlang,$mlang);
				vmTime('Manufacturers by Cache','mcaching');
			} else {
				$manufacturers = $manuM ->getManufacturersOfProductsInCategory($virtuemart_category_id,VmConfig::$vmlang,$mlang);
				vmTime('Manufacturers by function','mcaching');
			}

			// manufacturer link list
			$manufacturerLink = '';
			$virtuemart_manufacturer_id = vRequest::getInt ('virtuemart_manufacturer_id', '');
			if ($virtuemart_manufacturer_id != '') {
				$manufacturerTxt = '&virtuemart_manufacturer_id=' . $virtuemart_manufacturer_id;
			}

			if (count ($manufacturers) > 0) {
				$manufacturerLink = '<div class="orderlist">';
				if ($virtuemart_manufacturer_id > 0) {
					$allLink = str_replace($manufacturerTxt,$fieldLink,'');
					$allLink .= '&virtuemart_manufacturer_id=0';
					$manufacturerLink .= '<div><a title="" href="' . JRoute::_ ($allLink . $orderbyTxt . $orderDirLink , FALSE) . '">' . vmText::_ ('COM_VIRTUEMART_SEARCH_SELECT_ALL_MANUFACTURER') . '</a></div>';
				}
				if (count ($manufacturers) > 1) {
					foreach ($manufacturers as $mf) {
						$link = JRoute::_ ($fieldLink . '&virtuemart_manufacturer_id=' . $mf->virtuemart_manufacturer_id . $orderbyTxt . $orderDirLink,FALSE);
						if ($mf->virtuemart_manufacturer_id != $virtuemart_manufacturer_id) {
							$manufacturerLink .= '<div><a title="' . $mf->mf_name . '" href="' . $link . '">' . $mf->mf_name . '</a></div>';
						}
						else {
							$currentManufacturerLink = '<div class="title">' . vmText::_ ('COM_VIRTUEMART_PRODUCT_DETAILS_MANUFACTURER_LBL') . '</div><div class="activeOrder">' . $mf->mf_name . '</div>';
						}
					}
				}
				elseif ($virtuemart_manufacturer_id > 0) {
					$currentManufacturerLink = '<div class="title">' . vmText::_ ('COM_VIRTUEMART_PRODUCT_DETAILS_MANUFACTURER_LBL') . '</div><div class="activeOrder">' . $manufacturers[0]->mf_name . '</div>';
				}
				else {
					$currentManufacturerLink = '<div class="title">' . vmText::_ ('COM_VIRTUEMART_PRODUCT_DETAILS_MANUFACTURER_LBL') . '</div><div class="Order"> ' . $manufacturers[0]->mf_name . '</div>';
				}
				$manufacturerLink .= '</div>';
			}
		}

		/* order by link list*/
		$orderByLink = '';
		$fields = VmConfig::get ('browse_orderby_fields');
		if (count ($fields) > 1) {
			$orderByLink = '<div class="orderlist">';
			foreach ($fields as $field) {
				if ($field != $orderby) {

					$dotps = strrpos ($field, '.');
					if ($dotps !== FALSE) {
						$prefix = substr ($field, 0, $dotps + 1);
						$fieldWithoutPrefix = substr ($field, $dotps + 1);
					}
					else {
						$prefix = '';
						$fieldWithoutPrefix = $field;
					}

					$text = vmText::_ ('COM_VIRTUEMART_' . strtoupper (str_replace(array(',',' '),array('_',''),$fieldWithoutPrefix)));

					$field = explode('.',$field);
					if(isset($field[1])){
						$field = $field[1];
					} else {
						$field = $field[0];
					}
					$link = JRoute::_ ($fieldLink . $manufacturerTxt . '&orderby=' . $field,FALSE);

					$orderByLink .= '<div><a title="' . $text . '" href="' . $link . '">' . $text . '</a></div>';
				}
			}
			$orderByLink .= '</div>';
		}


		if($orderDir == 'ASC'){
			$orderDir = 'DESC';
		} else {
			$orderDir = 'ASC';
		}

		if ($orderDir != $orderDirConf ) {
			$orderDirLink = '&dir=' . $orderDir;	//was '&order='
		} else {
			$orderDirLink = '';
		}

		$orderDirTxt = vmText::_ ('COM_VIRTUEMART_'.$orderDir);

		$link = JRoute::_ ($fieldLink . $orderbyTxt . $orderDirLink . $manufacturerTxt,FALSE);

		// full string list
		if ($orderby == '') {
			$orderby = $orderbyCfg;
		}
		$orderby = strtoupper ($orderby);


		$dotps = strrpos ($orderby, '.');
		if ($dotps !== FALSE) {
			$prefix = substr ($orderby, 0, $dotps + 1);
			$orderby = substr ($orderby, $dotps + 1);
		}
		else {
			$prefix = '';
		}
		$orderby=str_replace(',','_',$orderby);
		$orderByList = '<div class="orderlistcontainer"><div class="title">' . vmText::_ ('COM_VIRTUEMART_ORDERBY') . '</div><div class="activeOrder"><a title="' . $orderDirTxt . '" href="' . $link . '">' . vmText::_ ('COM_VIRTUEMART_SEARCH_ORDER_' . $orderby) . ' ' . $orderDirTxt . '</a></div>';
		$orderByList .= $orderByLink . '</div>';

		$manuList = '';
		if (VmConfig::get ('show_manufacturers')) {
			if (empty ($currentManufacturerLink)) {
				$currentManufacturerLink = '<div class="title">' . vmText::_ ('COM_VIRTUEMART_PRODUCT_DETAILS_MANUFACTURER_LBL') . '</div><div class="activeOrder">' . vmText::_ ('COM_VIRTUEMART_SEARCH_SELECT_MANUFACTURER') . '</div>';
			}
			$manuList = ' <div class="orderlistcontainer">' . $currentManufacturerLink;
			$manuList .= $manufacturerLink . '</div><div class="clear"></div>';

		}

		return array('orderby'=> $orderByList, 'manufacturer'=> $manuList);
	}

// **************************************************
//Stocks
//
	/**
	 * Get the stock level for a given product
	 *
	 * @author RolandD
	 * @access public
	 * @param object $product the product to get stocklevel for
	 * @return array containing product objects
	 */
	public function getStockIndicator ($product) {

		/* Assign class to indicator */
		$stock_level = $product->product_in_stock - $product->product_ordered;
		$reorder_level = $product->low_stock_notification;
		$level = 'normalstock';
		$stock_tip = vmText::_ ('COM_VIRTUEMART_STOCK_LEVEL_DISPLAY_NORMAL_TIP');
		if ($stock_level <= $reorder_level) {
			$level = 'lowstock';
			$stock_tip = vmText::_ ('COM_VIRTUEMART_STOCK_LEVEL_DISPLAY_LOW_TIP');
		}
		if ($stock_level <= 0) {
			$level = 'nostock';
			$stock_tip = vmText::_ ('COM_VIRTUEMART_STOCK_LEVEL_DISPLAY_OUT_TIP');
		}
		$stock = new Stdclass();
		$stock->stock_tip = $stock_tip;
		$stock->stock_level = $level;
		return $stock;
	}


	public function updateStockInDB ($product, $amount, $signInStock, $signOrderedStock) {

		$validFields = array('=', '+', '-');
		if (!in_array ($signInStock, $validFields)) {
			return FALSE;
		}
		if (!in_array ($signOrderedStock, $validFields)) {
			return FALSE;
		}
		//sanitize fields
		$id = (int)$product->virtuemart_product_id;

		$amount = (float)$amount;
		$update = array();

		if ($signInStock != '=' or $signOrderedStock != '=') {

			if ($signInStock != '=') {
				$update[] = '`product_in_stock` = `product_in_stock` ' . $signInStock . $amount;

				if (strpos ($signInStock, '+') !== FALSE) {
					$signInStock = '-';
				}
				else {
					$signInStock = '+';
				}
				$update[] = '`product_sales` = `product_sales` ' . $signInStock . $amount;

			}
			if ($signOrderedStock != '=') {
				$update[] = '`product_ordered` = `product_ordered` ' . $signOrderedStock . $amount;
			}
			$q = 'UPDATE `#__virtuemart_products` SET ' . implode (", ", $update) . ' WHERE `virtuemart_product_id` = ' . $id;

			$db = JFactory::getDbo();
			$db->setQuery ($q);
			$db->query ();

			//The low on stock notification comes now, when the people ordered.
			//You need to know that the stock is going low before you actually sent the wares, because then you ususally know it already yoursefl
			//note by Max Milbers
			if ($signInStock == '+') {

				$db->setQuery ('SELECT (IFNULL(`product_in_stock`,"0")+IFNULL(`product_ordered`,"0")) < IFNULL(`low_stock_notification`,"0") '
						. 'FROM `#__virtuemart_products` '
						. 'WHERE `virtuemart_product_id` = ' . $id
				);
				if ($db->loadResult () == 1) {
					$this->lowStockWarningEmail( $id) ;
				}
			}
		}

	}
function lowStockWarningEmail($virtuemart_product_id) {

	if(VmConfig::get('lstockmail',TRUE)){
		if (!class_exists ('shopFunctionsF')) {
			require(VMPATH_SITE . DS . 'helpers' . DS . 'shopfunctionsf.php');
		}

		/* Load the product details */
		$q = "SELECT l.product_name,product_in_stock,virtuemart_vendor_id FROM `#__virtuemart_products_" . VmConfig::$vmlang . "` l
				JOIN `#__virtuemart_products` p ON p.virtuemart_product_id=l.virtuemart_product_id
			   WHERE p.virtuemart_product_id = " . $virtuemart_product_id;
		$db = JFactory::getDbo();
		$db->setQuery ($q);
		$vars = $db->loadAssoc ();

		$url = JURI::root () . 'index.php?option=com_virtuemart&view=productdetails&virtuemart_product_id=' . $virtuemart_product_id;
		$link = '<a href="'. $url.'">'. $vars['product_name'].'</a>';
		$vars['subject'] = vmText::sprintf('COM_VIRTUEMART_PRODUCT_LOW_STOCK_EMAIL_SUBJECT',$vars['product_name']);
		$vars['mailbody'] =vmText::sprintf('COM_VIRTUEMART_PRODUCT_LOW_STOCK_EMAIL_BODY',$link, $vars['product_in_stock']);

		$virtuemart_vendor_id = 1;
		if(Vmconfig::get('multix','none')!=='none'){
			$virtuemart_vendor_id = $vars['virtuemart_vendor_id'];
		}

		$vendorModel = VmModel::getModel ('vendor');
		$vendor = $vendorModel->getVendor ($virtuemart_vendor_id);
		$vendorModel->addImages ($vendor);
		$vars['vendor'] = $vendor;

		$vars['vendorAddress']= shopFunctions::renderVendorAddress($virtuemart_vendor_id);
		$vars['vendorEmail'] = $vendorModel->getVendorEmail ($virtuemart_vendor_id);

		$vars['user'] =  $vendor->vendor_store_name ;
		shopFunctionsF::renderMail ('productdetails', $vars['vendorEmail'], $vars, 'productdetails', TRUE) ;

		return TRUE;
	} else {
		return FALSE;
	}

}

	public function getUncategorizedChildren ($withParent) {

		if (!isset($this->_uncategorizedChildren[$this->_id])) {

			//Todo add check for shoppergroup depended product display
			$q = 'SELECT `virtuemart_product_id` FROM `#__virtuemart_products` as p
				LEFT JOIN `#__virtuemart_product_categories` as pc
				USING (`virtuemart_product_id`) ';

			if ($withParent) {
				$q .= ' WHERE (`product_parent_id` = "' . $this->_id . '"  OR `virtuemart_product_id` = "' . $this->_id . '") ';
			}
			else {
				$q .= ' WHERE `product_parent_id` = "' . $this->_id . '" ';
			}

			$app = JFactory::getApplication ();
			if ($app->isSite () && !VmConfig::get ('use_as_catalog', 0) && VmConfig::get ('stockhandle', 'none') == 'disableit') {
				$q .= ' AND p.`product_in_stock`>"0" ';
			}

			if ($app->isSite ()) {

				$q .= ' AND p.`published`="1"';
			}

			$q .= ' GROUP BY `virtuemart_product_id` ORDER BY p.pordering ASC';
			$db = JFactory::getDbo();
			$db->setQuery ($q);
			$r = $db->loadColumn();
			if($r and count($r)>0){
				$this->_uncategorizedChildren[$this->_id] = $r;
			} else {
				$this->_uncategorizedChildren[$this->_id] = array();
			}

			$err = $db->getErrorMsg ();
			if (!empty($err)) {
				vmError ('getUncategorizedChildren sql error ' . $err, 'getUncategorizedChildren sql error');
				vmdebug ('getUncategorizedChildren ' . $err);
				return FALSE;
			}

		}
		return $this->_uncategorizedChildren[$this->_id];
	}

	/**
	 * Check if the product has any children
	 *
	 * @author RolandD
	 * @author Max Milbers
	 * @param int $virtuemart_product_id Product ID
	 * @return bool True if there are child products, false if there are no child products
	 */
	public function checkChildProducts ($product_ids) {

		if($product_ids!=0){

			$db = JFactory::getDbo();
			if(!is_array($product_ids)) $product_ids = array($product_ids);
			$vmpid = implode('","',$product_ids);
			if(!empty($vmpid)){
				$q = 'SELECT COUNT(virtuemart_product_id) FROM `#__virtuemart_products` WHERE `product_parent_id` IN ('.$vmpid.');'; //  "' . $virtuemart_product_id . '"';
				$db->setQuery ($q);
				return $db->loadResult ();
			}
		}
		return FALSE;
	}

	function getProductChilds ($product_id) {

		if (empty($product_id)) {
			return array();
		}
		$db = JFactory::getDBO ();
		$db->setQuery (' SELECT virtuemart_product_id, product_name FROM `#__virtuemart_products_' . VmConfig::$vmlang . '`
			JOIN `#__virtuemart_products` as C using (`virtuemart_product_id`)
			WHERE `product_parent_id` =' . (int)$product_id);
		return $db->loadObjectList ();

	}

	function getProductChildIds ($product_id) {

		if (empty($product_id)) {
			return array();
		}
		$db = JFactory::getDBO ();
		$db->setQuery (' SELECT virtuemart_product_id FROM `#__virtuemart_products` WHERE `product_parent_id` =' . (int)$product_id.' ORDER BY pordering, created_on ASC');

		return $db->loadColumn ();

	}


	public function getAllProductChildIds($product_ids,&$childIds){

		if (empty($product_ids)) {
			return array();
		}

		if(!is_array($product_ids)) $product_ids = array($product_ids);

		if($productsWithChilds = self::checkChildProducts($product_ids)){

			if($productsWithChilds){
				foreach($product_ids as $product_id){
					if(empty($product_id)) continue;
					$tmp = self::getProductChildIds($product_id);
					if($tmp){
						if(!isset($childIds[$product_id])){
							$childIds[$product_id] = $tmp;
							foreach($tmp as $t){
								//prevent looop
								if($t=!$product_id){
									self::getAllProductChildIds($t,$childIds[$product_id]);
								}
							}
						}
					}
				}
			}

		}
	}


	static function getProductParentId ($product_parent_id) {

		if (empty($product_parent_id)) {
			return 0;
		}
		static $parentCache = array();
		if(!isset($parentCache[$product_parent_id])){
			$db = JFactory::getDbo();
			$db->setQuery (' SELECT `product_parent_id` FROM `#__virtuemart_products` WHERE `virtuemart_product_id` =' . (int)$product_parent_id);
			$parentCache[$product_parent_id] = $db->loadResult ();
		}
		return $parentCache[$product_parent_id];
	}


	function sentProductEmailToShoppers () {

		if (!class_exists ('ShopFunctions')) {
			require(VMPATH_ADMIN . DS . 'helpers' . DS . 'shopfunctions.php');
		}

		$product_id = vRequest::getVar ('virtuemart_product_id', '');
		$vars = array();
		$vars['subject'] = vRequest::getVar ('subject');
		$vars['mailbody'] = vRequest::getVar ('mailbody');

		$order_states = vRequest::getInt ('statut');
		$productShoppers = $this->getProductShoppersByStatus ($product_id, $order_states);

		$productModel = VmModel::getModel ('product');
		$product = $productModel->getProduct ($product_id);

		$vendorModel = VmModel::getModel ('vendor');
		$vendor = $vendorModel->getVendor ($product->virtuemart_vendor_id);
		$vendorModel->addImages ($vendor);
		$vars['vendor'] = $vendor;
		$vars['vendorEmail'] = $vendorModel->getVendorEmail ($product->virtuemart_vendor_id);
		$vars['vendorAddress'] = shopFunctions::renderVendorAddress ($product->virtuemart_vendor_id);

		$orderModel = VmModel::getModel ('orders');
		foreach ($productShoppers as $productShopper) {
			$vars['user'] = $productShopper['name'];
			if (shopFunctionsF::renderMail ('productdetails', $productShopper['email'], $vars, 'productdetails', TRUE)) {
				$string = 'COM_VIRTUEMART_MAIL_SEND_SUCCESSFULLY';
			}
			else {
				$string = 'COM_VIRTUEMART_MAIL_NOT_SEND_SUCCESSFULLY';
			}
			// Update the order history  for each order
			foreach ($productShopper['order_info'] as $order_info) {
				$orderModel->_updateOrderHist ($order_info['order_id'], $order_info['order_status'], 1, $vars['subject'] . ' ' . $vars['mailbody']);
			}
			// todo: when there is an error while sending emails
			//vmInfo (vmText::sprintf ($string, $productShopper['email']));
		}

	}


	public function getProductShoppersByStatus ($product_id, $states) {

		if (empty($states)) {
			return FALSE;
		}
		$orderstatusModel = VmModel::getModel ('orderstatus');
		$orderStates = $orderstatusModel->getOrderStatusNames ();

		foreach ($states as &$status) {
			if (!array_key_exists ($status, $orderStates)) {
				unset($status);
			}
		}
		if (empty($states)) {
			return FALSE;
		}

		$q = 'SELECT ou.* , oi.product_quantity , o.order_number, o.order_status, oi.`order_status` AS order_item_status ,
		o.virtuemart_order_id FROM `#__virtuemart_order_userinfos` as ou
			JOIN `#__virtuemart_order_items` AS oi USING (`virtuemart_order_id`)
			JOIN `#__virtuemart_orders` AS o ON o.`virtuemart_order_id` =  oi.`virtuemart_order_id`
			WHERE ou.`address_type`="BT" AND oi.`virtuemart_product_id`=' . (int)$product_id;
		if (count ($orderStates) !== count ($states)) {
			$q .= ' AND oi.`order_status` IN ( "' . implode ('","', $states) . '") ';
		}
		$q .= '  ORDER BY ou.`email` ASC';
		$db = JFactory::getDbo();
		$db->setQuery ($q);
		$productShoppers = $db->loadAssocList ();

		$shoppers = array();
		foreach ($productShoppers as $productShopper) {
			$key = $productShopper['email'];
			if (!array_key_exists ($key, $shoppers)) {
				$shoppers[$key]['phone'] = !empty($productShopper['phone_1']) ? $productShopper['phone_1'] : (!empty($productShopper['phone_2']) ? $productShopper['phone_2'] : '-');
				$shoppers[$key]['name'] = $productShopper['first_name'] . ' ' . $productShopper['last_name'];
				$shoppers[$key]['email'] = $productShopper['email'];
				$shoppers[$key]['mail_to'] = 'mailto:' . $productShopper['email'];
				$shoppers[$key]['nb_orders'] = 0;
			}
			$i = $shoppers[$key]['nb_orders'];
			$shoppers[$key]['order_info'][$i]['order_number'] = $productShopper['order_number'];
			$shoppers[$key]['order_info'][$i]['order_id'] = $productShopper['virtuemart_order_id'];
			$shoppers[$key]['order_info'][$i]['order_status'] = $productShopper['order_status'];
			$shoppers[$key]['order_info'][$i]['order_item_status_name'] = $orderStates[$productShopper['order_item_status']]['order_status_name'];
			$shoppers[$key]['order_info'][$i]['quantity'] = $productShopper['product_quantity'];
			$shoppers[$key]['nb_orders']++;
		}
		return $shoppers;
	}
}
// No closing tag