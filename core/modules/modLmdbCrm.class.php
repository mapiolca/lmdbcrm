<?php
/* Copyright (C) 2004-2018	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2018-2019	Nicolas ZABOURI				<info@inovea-conseil.com>
 * Copyright (C) 2019-2024	Frédéric France				<frederic.france@free.fr>
 * Copyright (C) 2025		Pierre Ardoin				<erp@lesmetiersdubatiment.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * 	\defgroup   lmdbcrm     Module LmdbCrm
 *  \brief      LmdbCrm module descriptor.
 *
 *  \file       htdocs/lmdbcrm/core/modules/modLmdbCrm.class.php
 *  \ingroup    lmdbcrm
 *  \brief      Description and activation file for module LmdbCrm
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';


/**
 *  Description and activation class for module LmdbCrm
 */
class modLmdbCrm extends DolibarrModules
{
	/**
	 * Constructor. Define names, constants, directories, boxes, permissions
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		global $conf, $langs;

		$this->db = $db;

		// Id for module (must be unique).
		// Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
		$this->numero = 450011; // TODO Go on page https://wiki.dolibarr.org/index.php/List_of_modules_id to reserve an id number for your module

		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'lmdbcrm';

		// Family can be 'base' (core modules),'crm','financial','hr','projects','products','ecm','technic' (transverse modules),'interface' (link with external tools),'other','...'
		// It is used to group modules by family in module setup page
		$this->family = "Les Métiers du Bâtiment";

		// Module position in the family on 2 digits ('01', '10', '20', ...)
		$this->module_position = '90';

		// Gives the possibility for the module, to provide his own family info and position of this family (Overwrite $this->family and $this->module_position. Avoid this)
		//$this->familyinfo = array('myownfamily' => array('position' => '01', 'label' => $langs->trans("MyOwnFamily")));
		// Module label (no space allowed), used if translation string 'ModuleLmdbCrmName' not found (LmdbCrm is name of module).
		$this->name = preg_replace('/^mod/i', '', get_class($this));

		// DESCRIPTION_FLAG
		// Module description, used if translation string 'ModuleLmdbCrmDesc' not found (LmdbCrm is name of module).
		$this->description = "ModuleLmdbCrmDesc";
		// Used only if file README.md and README-LL.md not found.
		$this->descriptionlong = "ModuleLmdbCrmDesc";

		// Author
		$this->editor_name = 'Les Métiers du Bâtiment';
		$this->editor_url = 'lesmetiersdubatiment.fr';		// Must be an external online web site
		$this->editor_squarred_logo = '';					// Must be image filename into the module/img directory followed with @modulename. Example: 'myimage.png@lmdbcrm'

		// Possible values for version are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated', 'experimental_deprecated' or a version string like 'x.y.z'
		$this->version = '1.1';
		// Url to the file with your last numberversion of this module
		//$this->url_last_version = 'http://www.example.com/versionmodule.txt';

		// Key used in llx_const table to save module status enabled/disabled (where LMDBCRM is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);

		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
		// If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
		// To use a supported fa-xxx css style of font awesome, use this->picto='xxx'
		$this->picto = 'chart';

		// Define some features supported by module (triggers, login, substitutions, menus, css, etc...)
		$this->module_parts = array(
			'triggers' => 0,
			'login' => 0,
			'substitutions' => 0,
			'menus' => 0,
			'tpl' => 0,
			'barcode' => 0,
			'models' => 0,
			'printing' => 0,
			'theme' => 0,
			'css' => array(),
			'js' => array(),
			'hooks' => array(),
			'moduleforexternal' => 0,
			'websitetemplates' => 0,
			'captcha' => 0
		);

		// Data directories to create when module is enabled.
		$this->dirs = array("/lmdbcrm/temp");

		// Config pages. Put here list of php page, stored into lmdbcrm/admin directory, to use to setup module.
		//$this->config_page_url = array("setup.php@lmdbcrm");

		// Dependencies
		// A condition to hide module
		$this->hidden = getDolGlobalInt('MODULE_LMDBCRM_DISABLED'); // A condition to disable module;
		// List of module class names that must be enabled if this module is enabled. Example: array('always'=>array('modModuleToEnable1','modModuleToEnable2'), 'FR'=>array('modModuleToEnableFR')...)
		$this->depends = array();
		// List of module class names to disable if this one is disabled. Example: array('modModuleToDisable1', ...)
		$this->requiredby = array();
		// List of module class names this module is in conflict with. Example: array('modModuleToDisable1', ...)
		$this->conflictwith = array();

		// The language file dedicated to your module
		$this->langfiles = array("lmdbcrm@lmdbcrm");

		// Prerequisites
		$this->phpmin = array(7, 2); // Minimum version of PHP required by module
		// $this->phpmax = array(8, 0); // Maximum version of PHP required by module
		$this->need_dolibarr_version = array(19, -3); // Minimum version of Dolibarr required by module
		// $this->max_dolibarr_version = array(19, -3); // Maximum version of Dolibarr required by module
		$this->need_javascript_ajax = 0;

		// Messages at activation
		$this->warnings_activation = array(); 		// Warning to show when we activate a module. Example: array('always'='text') or array('FR'='textfr','MX'='textmx'...)
		$this->warnings_activation_ext = array(); 	// Warning to show when we activate a module if another module is on. Example: array('modOtherModule' => array('always'=>'text')) or array('always' => array('FR'=>'textfr','MX'=>'textmx'...))
		//$this->automatic_activation = array('FR'=>'LmdbCrmWasAutomaticallyActivatedBecauseOfYourCountryChoice');
		//$this->always_enabled = false;			// If true, can't be disabled. Value true is reserved for core modules. Not allowed for external modules.

		// Constants
		// List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive)
		// Example: $this->const=array(1 => array('LMDBCRM_MYNEWCONST1', 'chaine', 'myvalue', 'This is a constant to add', 1),
		//                             2 => array('LMDBCRM_MYNEWCONST2', 'chaine', 'myvalue', 'This is another constant to add', 0, 'current', 1)
		// );
		$this->const = array();

		// Some keys to add into the overwriting translation tables
		/*$this->overwrite_translation = array(
			'en_US:ParentCompany'=>'Parent company or reseller',
			'fr_FR:ParentCompany'=>'Maison mère ou revendeur'
		)*/

		if (!isModEnabled("lmdbcrm")) {
			$conf->lmdbcrm = new stdClass();
			$conf->lmdbcrm->enabled = 0;
		}

		// Array to add new pages in new tabs
		/* BEGIN MODULEBUILDER TABS */
		// Don't forget to deactivate/reactivate your module to test your changes
		$this->tabs = array();
		/* END MODULEBUILDER TABS */
		// Example:
		// To add a new tab identified by code tabname1
		// $this->tabs[] = array('data' => 'objecttype:+tabname1:Title1:mylangfile@lmdbcrm:$user->hasRight('lmdbcrm', 'myobject', 'read'):/lmdbcrm/mynewtab1.php?id=__ID__');
		// To add another new tab identified by code tabname2. Label will be result of calling all substitution functions on 'Title2' key.
		// $this->tabs[] = array('data' => 'objecttype:+tabname2:SUBSTITUTION_Title2:mylangfile@lmdbcrm:$user->hasRight('othermodule', 'otherobject', 'read'):/lmdbcrm/mynewtab2.php?id=__ID__',
		// To remove an existing tab identified by code tabname
		// $this->tabs[] = array('data' => 'objecttype:-tabname:NU:conditiontoremove');
		//
		// Where objecttype can be
		// 'categories_x'	  to add a tab in category view (replace 'x' by type of category (0=product, 1=supplier, 2=customer, 3=member)
		// 'contact'          to add a tab in contact view
		// 'contract'         to add a tab in contract view
		// 'delivery'         to add a tab in delivery view
		// 'group'            to add a tab in group view
		// 'intervention'     to add a tab in intervention view
		// 'invoice'          to add a tab in customer invoice view
		// 'supplier_invoice' to add a tab in supplier invoice view
		// 'member'           to add a tab in foundation member view
		// 'opensurveypoll'	  to add a tab in opensurvey poll view
		// 'order'            to add a tab in sale order view
		// 'supplier_order'   to add a tab in supplier order view
		// 'payment'		  to add a tab in payment view
		// 'supplier_payment' to add a tab in supplier payment view
		// 'product'          to add a tab in product view
		// 'propal'           to add a tab in propal view
		// 'project'          to add a tab in project view
		// 'stock'            to add a tab in stock view
		// 'thirdparty'       to add a tab in third party view
		// 'user'             to add a tab in user view


		// Dictionaries
		/* Example:
		 $this->dictionaries=array(
		 'langs' => 'lmdbcrm@lmdbcrm',
		 // List of tables we want to see into dictionary editor
		 'tabname' => array("table1", "table2", "table3"),
		 // Label of tables
		 'tablib' => array("Table1", "Table2", "Table3"),
		 // Request to select fields
		 'tabsql' => array('SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.$this->db->prefix().'table1 as f', 'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.$this->db->prefix().'table2 as f', 'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.$this->db->prefix().'table3 as f'),
		 // Sort order
		 'tabsqlsort' => array("label ASC", "label ASC", "label ASC"),
		 // List of fields (result of select to show dictionary)
		 'tabfield' => array("code,label", "code,label", "code,label"),
		 // List of fields (list of fields to edit a record)
		 'tabfieldvalue' => array("code,label", "code,label", "code,label"),
		 // List of fields (list of fields for insert)
		 'tabfieldinsert' => array("code,label", "code,label", "code,label"),
		 // Name of columns with primary key (try to always name it 'rowid')
		 'tabrowid' => array("rowid", "rowid", "rowid"),
		 // Condition to show each dictionary
		 'tabcond' => array(isModEnabled('lmdbcrm'), isModEnabled('lmdbcrm'), isModEnabled('lmdbcrm')),
		 // Tooltip for every fields of dictionaries: DO NOT PUT AN EMPTY ARRAY
		 'tabhelp' => array(array('code' => $langs->trans('CodeTooltipHelp'), 'field2' => 'field2tooltip'), array('code' => $langs->trans('CodeTooltipHelp'), 'field2' => 'field2tooltip'), ...),
		 );
		 */
		/* BEGIN MODULEBUILDER DICTIONARIES */
		$this->dictionaries = array();
		/* END MODULEBUILDER DICTIONARIES */

		// Boxes/Widgets
		$this->boxes = array(
			0 => array(
				'file' => 'lmdbcrm_podium_signedquotes.php@lmdbcrm',
				'note' => 'LmdbCrmSignedQuotesPodiumDescription',
				'enabledbydefaulton' => 'Home',
			),
			1 => array(
				'file' => 'lmdbcrm_graph_conversionrates.php@lmdbcrm',
				'note' => 'lmdbcrm_graph_conversionratesDescription',
				'enabledbydefaulton' => 'Home',
			),
			2 => array(
				'file' => 'lmdbcrm_podium_signedturnover.php@lmdbcrm',
				'note' => 'LmdbCrmSignedTurnoverPodium',
				'enabledbydefaulton' => 'Home',
			),
			3 => array(
				'file' => 'lmdbcrm_graph_marginrates.php@lmdbcrm',
				'note' => 'LmdbcrmGraphMarginRatesDescription',
				'enabledbydefaulton' => 'Home',
			),
		);

		// Cronjobs
		$this->cronjobs = array();

		// Permissions provided by this module
		$this->rights = array();

		// Main menu entries to add
		$this->menu = array();

		$this->menu[] = array(
			'fk_menu' => 'fk_mainmenu=commercial,fk_leftmenu=propals',
			'type' => 'left',
			'titre' => 'LmdbCrmSalesRepRanking',
			'mainmenu' => 'commercial',
			'leftmenu' => 'lmdbcrm_salesrepranking',
			'url' => '/lmdbcrm/commercial_ranking.php',
			'langs' => 'lmdbcrm@lmdbcrm',
			'position' => 1000,
			'perms' => '$user->rights->propal->lire',
			'enabled' => 'isModEnabled("lmdbcrm")',
			'target' => '',
			'user' => 2,
		);

		// Export definitions provided by this module
		$this->export_code = array();
		$this->export_label = array();
		$this->export_icon = array();
		$this->export_enabled = array();
		$this->export_fields_array = array();
		$this->export_TypeFields_array = array();
		$this->export_entities_array = array();
		$this->export_sql_start = array();
		$this->export_sql_end = array();
		$this->export_sql_order = array();
		$this->export_dependencies_array = array();
		$this->export_examplevalues_array = array();
		$this->export_help_array = array();

		// Import definitions provided by this module
		$this->import_code = array();
		$this->import_label = array();
		$this->import_icon = array();
		$this->import_tables_array = array();
		$this->import_tables_creator_array = array();
		$this->import_fields_array = array();
		$this->import_fieldshidden_array = array();
		$this->import_regex_array = array();
		$this->import_examplevalues_array = array();
		$this->import_updatekeys_array = array();
		$this->import_convertvalue_array = array();
		$this->import_run_sql_after_array = array();
	}


	/**
	 *  Function called when module is enabled.
	 *  The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *  It also creates data directories
	 *
	 *  @param      string  $options    Options when enabling module ('', 'noboxes')
	 *  @return     int<-1,1>               1 if OK, <=0 if KO
	 */
	public function init($options = '')
	{
		// Initialize module (create SQL schema and widgets)
		$result = $this->_load_tables('/lmdbcrm/sql/');
		if ($result < 0) {
			return -1;
		}
		
		$this->remove($options);
		
		$sql = array();
		
		return $this->_init($sql, $options);
	}

	/**
	 *      Function called when module is disabled.
	 *      Remove from database constants, boxes and permissions from Dolibarr database.
	 *      Data directories are not deleted
	 *
	 *      @param  string          $options        Options when enabling module ('', 'noboxes')
	 *      @return int<-1,1>                               1 if OK, <=0 if KO
	 */
	public function remove($options = '')
	{
		$sql = array();
		return $this->_remove($sql, $options);
	}
}
