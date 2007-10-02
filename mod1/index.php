<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007 Michael Oehlhof (michael@oehlhof.de)
*  All rights reserved
*
*  Because I dont want to redefine the wheel again, some ideas
*  and code snippets are taken from the seminar manager extension
*  tx_seminars
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/


// initialization of the module
unset($MCONF);
require_once('conf.php');
require_once($BACK_PATH.'init.php');
require_once($BACK_PATH.'template.php');

require_once(PATH_t3lib.'class.t3lib_scbase.php');
require_once(t3lib_extMgm::extPath('wse_events').'mod1/class.tx_wseevents_sessionslist.php');
require_once(t3lib_extMgm::extPath('wse_events').'mod1/class.tx_wseevents_timeslotslist.php');
require_once(t3lib_extMgm::extPath('wse_events').'mod1/class.tx_wseevents_speakerattendanceslist.php');
require_once(t3lib_extMgm::extPath('wse_events').'mod1/class.tx_wseevents_eventslist.php');

require_once(t3lib_extMgm::extPath('wse_events').'mod1/class.tx_wseevents_speakerslist.php');
require_once(t3lib_extMgm::extPath('wse_events').'mod1/class.tx_wseevents_locationslist.php');
require_once(t3lib_extMgm::extPath('wse_events').'mod1/class.tx_wseevents_roomslist.php');
require_once(t3lib_extMgm::extPath('wse_events').'mod1/class.tx_wseevents_categorieslist.php');

$LANG->includeLLFile('EXT:lang/locallang_show_rechis.xml');
$LANG->includeLLFile('EXT:lang/locallang_mod_web_list.xml');
$LANG->includeLLFile('EXT:wse_events/mod1/locallang.xml');


// This checks permissions and exits if the users has no permission for entry.
$BE_USER->modAccess($MCONF,1);



/**
 * Module 'WSE Events' for the 'wse_events' extension.
 *
 * @author	 	Michael Oehlhof
 * @package	TYPO3
 * @subpackage	wse_events
 */
class  tx_wseevents_module1 extends t3lib_SCbase {
	var $pageInfo;

	/** an array of available sub modules */
	var $availableSubModules;

	/** the currently selected sub module */
	var $subModule;


	/**
	 * Initializes the Module
	 *
	 * @return	void
	 */
	function init()	{
#		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;

		/*
		 * This is a workaround for the wrong generated links. The workaround is needed to
		 * get the right values from the GET Parameter. This workaround is from Elmar Hinz
		 * who also noted this in the bug tracker (http://bugs.typo3.org/view.php?id=2178).
		 */
		$matches = array();
		foreach ($GLOBALS['_GET'] as $key => $value) {
			if (preg_match('/amp;(.*)/', $key, $matches)) {
				$GLOBALS['_GET'][$matches[1]] = $value;
			}
		}
		/* --- END OF Workaround --- */

		parent::init();

		$this->id = intval($this->id);

		/*
		if (t3lib_div::_GP('clear_all_cache'))	{
			$this->include_once[] = PATH_t3lib.'class.t3lib_tcemain.php';
		}
		*/
	}

	/**
	 * Adds items to the ->MOD_MENU array. Used for the function menu selector.
	 *
	 * @return	void
	 */
	function menuConfig()	{
		global $LANG;
		$this->MOD_MENU = Array (
			'function' => Array (
				'1' => $LANG->getLL('function1'),
				'2' => $LANG->getLL('function2'),
				'3' => $LANG->getLL('function3'),
			)
		);
		parent::menuConfig();
	}

	/**
	 * Main function of the module. Write the content to $this->content
	 * If you chose "web" as main module, you will need to consider the $this->id parameter which will contain the uid-number of the page clicked in the page tree
	 *
	 * @return	[type]		...
	 */
	function main()	{
		global $BE_USER,$LANG,$BACK_PATH; //,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;

		// Access check!
		// The page will show only if there is a valid page and if this page may be viewed by the user
		$this->pageInfo = t3lib_BEfunc::readPageAccess($this->id,$this->perms_clause);
		$access = is_array($this->pageInfo) ? 1 : 0;

		if (($this->id && $access) || ($BE_USER->user['admin'] && !$this->id))	{

			// Draw the header.
			$this->doc = t3lib_div::makeInstance('bigDoc');
			$this->doc->backPath = $BACK_PATH;
			$this->doc->form='<form action="" method="POST">';

			// JavaScript
			$this->doc->JScode = '
				<script language="javascript" type="text/javascript">
					script_ended = 0;
					function jumpToUrl(URL)	{
						document.location = URL;
					}
				</script>
			';
			$this->doc->postCode='
				<script language="javascript" type="text/javascript">
					script_ended = 1;
					if (top.fsMod) top.fsMod.recentIds["web"] = 0;
				</script>
			';

			$headerSection = $this->doc->getHeader('pages',$this->pageInfo,$this->pageInfo['_thePath']).'<br />'.$LANG->sL('LLL:EXT:lang/locallang_core.xml:labels.path').': '.t3lib_div::fixed_lgd_pre($this->pageInfo['_thePath'],50);

			$this->content.=$this->doc->startPage($LANG->getLL('title'));
			$this->content.=$this->doc->header($LANG->getLL('title'));
			$this->content.=$this->doc->spacer(5);
			$this->content.=$this->doc->section('',$this->doc->funcMenu($headerSection,t3lib_BEfunc::getFuncMenu($this->id,'SET[function]',$this->MOD_SETTINGS['function'],$this->MOD_MENU['function'])));
			$this->content.=$this->doc->divider(5);


			// Render content:
			$this->moduleContent();

			// For debuging purpose only
			$debugline = '<br /><br /><hr />
						<br />### DEBUG ###<br />This is the GET/POST vars sent to the script:<br /><br />'.
						'GET:'.t3lib_div::view_array($_GET).'<br />'.
						'POST:'.t3lib_div::view_array($_POST).'<br />'.
#						'pageInfo:'.t3lib_div::view_array($this->pageInfo).'<br />'.
#						debug($_GET,'GET:').
						'';
						
#			$this->content .= $debugline;

			// ShortCut
			if ($BE_USER->mayMakeShortcut())	{
				$this->content.=$this->doc->spacer(20).$this->doc->section('',$this->doc->makeShortcutIcon('id',implode(',',array_keys($this->MOD_MENU)),$this->MCONF['name']));
			}

			$this->content.=$this->doc->spacer(10);
		} else {
			// If no access or if ID == zero

			$this->doc = t3lib_div::makeInstance('bigDoc');
			$this->doc->backPath = $BACK_PATH;

			$this->content.=$this->doc->startPage($LANG->getLL('title'));
			$this->content.=$this->doc->header($LANG->getLL('title'));
			$this->content.=$this->doc->spacer(5);
			$this->content.=$this->doc->spacer(10);
		}
	}

	/**
	 * Prints out the module HTML
	 *
	 * @return	void
	 */
	function printContent()	{
		$this->content.=$this->doc->endPage();
		echo $this->content;
	}

	/**
	 * Generates the module content
	 *
	 * @return	void
	 */
	function moduleContent()	{
		switch((string)$this->MOD_SETTINGS['function'])	{
			case 1:
				$this->moduleEventContent();
			break;
			case 2:
				$this->moduleCommonContent();
			break;
			case 3:
				$content='<div align=center><strong>Not implemented yet ...</strong></div>';
				$this->content.=$this->doc->section('Session planning:',$content,0,1);
			break;
		}
	}

	/**
	 * Generates the content for event data
	 *
	 * @return	void
	 */
	function moduleEventContent()	{
		global $BE_USER,$LANG;

		// define the sub modules that should be available in the tabmenu
		$this->availableSubModules = array();

		// only show the tabs if the back-end user has access to the corresponding tables
		if ($BE_USER->check('tables_select', 'tx_wseevents_sessions')) {
			$this->availableSubModules[1] = $LANG->getLL('subModuleTitle_sessions');
		}
		if ($BE_USER->check('tables_select', 'tx_wseevents_timeslots')) {
			$this->availableSubModules[2] = $LANG->getLL('subModuleTitle_time_slots');
		}
		if ($BE_USER->check('tables_select', 'tx_wseevents_speaker_attendance')) {
			$this->availableSubModules[3] = $LANG->getLL('subModuleTitle_speaker_attendance');
		}
		if ($BE_USER->check('tables_select', 'tx_wseevents_events')) {
			$this->availableSubModules[4] = $LANG->getLL('subModuleTitle_events');
		}

		// Read the selected sub module (from the tab menu) and make it available within this class.
		$this->subModule = intval(t3lib_div::_GET('subModule'));

		// If $this->subModule is not a key of $this->availableSubModules,
		// set it to the key of the first element in $this->availableSubModules
		// so the first tab is activated.
		if (!array_key_exists($this->subModule, $this->availableSubModules)) {
			reset($this->availableSubModules);
			$this->subModule = key($this->availableSubModules);
		}

		// Only generate the tab menu if the current back-end user has the
		// rights to show any of the tabs.
		if ($this->subModule) {
			$this->content .= $this->doc->getTabMenu(array('id' => $this->id),
				'subModule',
				$this->subModule,
				$this->availableSubModules);
			$this->content .= $this->doc->spacer(5);
		}

		// Select which sub module to display.
		// If no sub module is specified, an empty page will be displayed.
		switch ($this->subModule) {
			case 1:
				$eventsListClassname = t3lib_div::makeInstanceClassName('tx_wseevents_sessionslist');
				$eventsList = new $eventsListClassname($this);
				$this->content .= $eventsList->show();
				break;
			case 2:
				$eventsListClassname = t3lib_div::makeInstanceClassName('tx_wseevents_timeslotslist');
				$eventsList = new $eventsListClassname($this);
				$this->content .= $eventsList->show();
				break;
			case 3:
				$eventsListClassname = t3lib_div::makeInstanceClassName('tx_wseevents_speakerattendanceslist');
				$eventsList = new $eventsListClassname($this);
				$this->content .= $eventsList->show();
				break;
			case 4:
				$this->content .= '<br />';
				$eventsListClassname = t3lib_div::makeInstanceClassName('tx_wseevents_eventslist');
				$eventsList = new $eventsListClassname($this);
				$this->content .= $eventsList->show();
				break;
			default:
				$this->content .= '';
				break;
		}
	}

	/**
	 * Generates the content for common data
	 *
	 * @return	void
	 */
	function moduleCommonContent()	{
		global $BE_USER,$LANG;

		// define the sub modules that should be available in the tabmenu
		$this->availableSubModules = array();

		// only show the tabs if the back-end user has access to the corresponding tables
		if ($BE_USER->check('tables_select', 'tx_wseevents_speakers')) {
			$this->availableSubModules[1] = $LANG->getLL('subModuleTitle_speakers');
		}
		if ($BE_USER->check('tables_select', 'tx_wseevents_locations')) {
			$this->availableSubModules[2] = $LANG->getLL('subModuleTitle_locations');
		}
		if ($BE_USER->check('tables_select', 'tx_wseevents_rooms')) {
			$this->availableSubModules[3] = $LANG->getLL('subModuleTitle_rooms');
		}
		if ($BE_USER->check('tables_select', 'tx_wseevents_categories')) {
			$this->availableSubModules[4] = $LANG->getLL('subModuleTitle_categories');
		}

		// Read the selected sub module (from the tab menu) and make it available within this class.
		$this->subModule = intval(t3lib_div::_GET('subModule'));

		// If $this->subModule is not a key of $this->availableSubModules,
		// set it to the key of the first element in $this->availableSubModules
		// so the first tab is activated.
		if (!array_key_exists($this->subModule, $this->availableSubModules)) {
			reset($this->availableSubModules);
			$this->subModule = key($this->availableSubModules);
		}

		// Only generate the tab menu if the current back-end user has the
		// rights to show any of the tabs.
		if ($this->subModule) {
			$this->content .= $this->doc->getTabMenu(array('id' => $this->id),
				'subModule',
				$this->subModule,
				$this->availableSubModules);
			$this->content .= $this->doc->spacer(5);
		}

		// Select which sub module to display.
		// If no sub module is specified, an empty page will be displayed.
		switch ($this->subModule) {
			case 1:
				$eventsListClassname = t3lib_div::makeInstanceClassName('tx_wseevents_speakerslist');
				$eventsList = new $eventsListClassname($this);
				$this->content .= $eventsList->show();
				break;
			case 2:
				$eventsListClassname = t3lib_div::makeInstanceClassName('tx_wseevents_locationslist');
				$eventsList = new $eventsListClassname($this);
				$this->content .= $eventsList->show();
				break;
			case 3:
				$eventsListClassname = t3lib_div::makeInstanceClassName('tx_wseevents_roomslist');
				$eventsList = new $eventsListClassname($this);
				$this->content .= $eventsList->show();
				break;
			case 4:
				$eventsListClassname = t3lib_div::makeInstanceClassName('tx_wseevents_categorieslist');
				$eventsList = new $eventsListClassname($this);
				$this->content .= $eventsList->show();
				break;
			default:
				$this->content .= '';
				break;
		}
	}

}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wse_events/mod1/index.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wse_events/mod1/index.php']);
}




// Make instance:
$SOBE = t3lib_div::makeInstance('tx_wseevents_module1');
$SOBE->init();

// Include files?
foreach($SOBE->include_once as $INC_FILE)	include_once($INC_FILE);

$SOBE->main();
$SOBE->printContent();

?>