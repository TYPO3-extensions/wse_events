<?php
/***************************************************************
* Copyright notice
*
* (c) 2007 Niels Pardon (mail@niels-pardon.de)
* All rights reserved
*
* Adapted and modified 2007-2009 for use by the 'wse_events'
* extension from Michael Oehlhof <typo3@oehlhof.de>
*
* This script is part of the TYPO3 project. The TYPO3 project is
* free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
* (at your option) any later version.
*
* The GNU General Public License can be found at
* http://www.gnu.org/copyleft/gpl.html.
*
* This script is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/***************************************************************
*  Because I dont want to redefine the wheel again, some ideas
*  and code snippets are taken from the seminar manager extension
*  tx_seminars
***************************************************************/

/**
 * Class 'tx_wseevents_dbplugin' for the 'wse_events' extension.
 *
 * It defines the database table names, provides the configuration
 * and calles the base class init functions.
 *
 * This is an abstract class; don't instantiate it.
 *
 * @package		TYPO3
 * @subpackage	tx_wseevents
 * @author		Michael Oehlhof <typo3@oehlhof.de>
 */

// the UTF-8 representation of an en dash
DEFINE(UTF8_EN_DASH, chr(0xE2) . chr(0x80) . chr(0x93));
// a CR-LF combination (the default Unix line ending)
DEFINE(CRLF, chr(0x0D) . chr(0x0A));

require_once(PATH_t3lib . 'class.t3lib_tstemplate.php');
require_once(PATH_t3lib . 'class.t3lib_page.php');

// In case we're on the back end, PATH_tslib isn't defined yet.
if (!defined('PATH_tslib')) {
	define('PATH_tslib', t3lib_extMgm::extPath('cms') . 'tslib/');
}
require_once(PATH_tslib . 'class.tslib_pibase.php');

// If we are in the back end, we include the extension's locallang.xml.
if ((TYPO3_MODE == 'BE') && is_object($LANG)) {
    $LANG->includeLLFile('EXT:wse_events/mod1/locallang.xml');
}

/**
 * Class 'tx_wseevents_dbplugin' for the 'wse_events' extension.
 *
 * @package		TYPO3
 * @subpackage	wse_events
 * @author		Michael Oehlhof <typo3@oehlhof.de>
 */
class tx_wseevents_dbplugin extends tslib_pibase {
	/** The extension key. */
	var $extKey = 'wseevents';

	/** whether init() already has been called (in order to avoid double calls) */
	var $isInitialized = false;

	// Database table names. Will be initialized (indirectly) by $this->init.
	var $tableLocations;
	var $tableRooms;
	var $tableSpeakers;
	var $tableCategories;
	var $tableEvents;
	var $tableSpeakerRestrictions;
	var $tableSessions;
	var $tableTimeslots;

	// Constants for the types of records
	var $recordTypeComplete;
	var $recordTypeTopic;
	var $recordTypeDate;

	/** The front-end user who currently is logged in. */
	var $feuser = null;

	var $pageOverlays = array();			// Contains page translation languages
	var $languageIconTitles = array();		// Contains sys language icons and titles

	var $id;								// Page id
	var $script = 'db_list.php';			// Current script name

	var $backPath = '';

	/**
	 * Dummy constructor: Does nothing.
	 *
	 * The base classe's constructor is called in $this->init().
	 *
	 * @return	void		...
	 */
	function tx_wseevents_dbplugin() {
	}

	/**
	 * Initializes the FE plugin stuff, read the configuration
	 * and set the table names while we're at it.
	 *
	 * It is harmless if this function gets called multiple times as it recognizes
	 * this and ignores all calls but the first one.
	 *
	 * This is merely a convenience function.
	 *
	 * If the parameter is omited, the configuration for plugin.tx_seminar is used instead.
	 *
	 * @param	array		TypoScript configuration for the plugin
	 * @return	void		...
	 * @access protected
	 */
	function init($conf = null) {
		global $BACK_PATH;

		static $cachedConfigs = array();

		if (!$this->isInitialized) {
			if ($GLOBALS['TSFE'] && !isset($GLOBALS['TSFE']->config['config'])) {
				$GLOBALS['TSFE']->config['config'] = array();
			}

			$this->backPath = $BACK_PATH;

			// call the base classe's constructor manually as this isn't done automatically
			parent::tslib_pibase();

			if ($conf !== null) {
				$this->conf = $conf;
			} else {
				// We need to create our own template setup if we are in the BE
				// and we aren't currently creating a DirectMail page.
				if ((TYPO3_MODE == 'BE') && !is_object($GLOBALS['TSFE'])) {
					$pageId = $this->getCurrentBePageId();

					if (isset($cachedConfigs[$pageId])) {
						$this->conf =& $cachedConfigs[$pageId];
					} else {
						$template = t3lib_div::makeInstance('t3lib_TStemplate');
						// do not log time-performance information
						$template->tt_track = 0;
						$template->init();

						// Get the root line
						$sys_page = t3lib_div::makeInstance('t3lib_pageSelect');
						// the selected page in the BE is found
						// exactly as in t3lib_SCbase::init()
						$rootline = $sys_page->getRootLine($pageId);

						// This generates the constants/config + hierarchy info for the template.
						$template->runThroughTemplates($rootline, 0);
						$template->generateConfig();

						$this->conf =& $template->setup['plugin.']['tx_' . $this->extKey . '.'];
						$cachedConfigs[$pageId] =& $this->conf;
					}
				} else {
					// On the front end, we can use the provided template setup.
					$this->conf =& $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_' . $this->extKey . '.'];
				}
			}

			$this->pi_setPiVarDefaults();
			$this->pi_loadLL();

			$this->setTableNames();
			$this->setRecordTypes();

			// unserialize the configuration array
			$globalConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['wse_events']);

			$this->isInitialized = true;
		}

		return;
	}

	/**
	 * Generates a list of pids of all sub pages for the given depth.
	 *
	 * @param	integer		the pid of the page
	 * @param	integer		the depth for the search
	 * @return	string		the list of pids
	 * @access public
	 */
	function getRecursiveUidList($parentUid, $depth){
		global $TCA;

		if($depth != -1) {
			$depth = $depth-1; //decreasing depth
		}
		# Get ressource records:
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery (
			'uid',
			'pages',
			'pid IN (' . $GLOBALS['TYPO3_DB']->cleanIntList($parentUid) . ') '
				. t3lib_BEfunc::deleteClause('pages')
				. t3lib_BEfunc::versioningPlaceholderClause('pages')
			);
		if($depth > 0){
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$parentUid .= ',' . $this->getRecursiveUidList($row['uid'], $depth);
			}
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($res);
		return $parentUid;
	}


	/**
	 * Removes all pages which have common content from pid list.
	 *
	 * @param	string		list with page pids
	 * @return	string		the list of pids, without pages with common data
	 * @access public
	 */
	function removeCommonPages($pageList){
		global $TCA;

		$resultList = $pageList;
		foreach (explode(',', $pageList) as $thisPage) {
			// Initialize variables for the database query.
			$queryWhere = 'pid=' . $thisPage . t3lib_BEfunc::deleteClause($this->tableLocations)
				. ' AND ' . $TCA[$this->tableLocations]['ctrl']['languageField'] . '=0'
				. t3lib_BEfunc::versioningPlaceholderClause($this->tableLocations);
			$additionalTables = '';
			$groupBy = '';
			$orderBy = 'uid';
			$limit = '';
			// Get list of all events
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'count(*)',
				$this->tableLocations,
				$queryWhere,
				$groupBy,
				$orderBy,
				$limit);
			if ($res) {
				$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
				if ($row['count(*)']>0) {
					$resultList = t3lib_div::rmFromList($thisPage, $resultList);
				}
				$GLOBALS['TYPO3_DB']->sql_free_result($res);
			}
		}
		return $resultList;
	}


	/**
	 * Removes all pages which have event content from pid list.
	 *
	 * @param	string		list with page pids
	 * @return	string		the list of pids, without pages with event data
	 * @access public
	 */
	function removeEventPages($pageList){
		global $TCA;

		$resultList = $pageList;
		foreach (explode(',', $pageList) as $thisPage) {
			// Initialize variables for the database query.
			$queryWhere = 'pid=' . $thisPage . t3lib_BEfunc::deleteClause($this->tableEvents)
//				. ' AND ' . $TCA[$this->tableEvents]['ctrl']['languageField'] . '=0'
				. t3lib_BEfunc::versioningPlaceholderClause($this->tableEvents);
			$additionalTables = '';
			$groupBy = '';
			$orderBy = '';
			$limit = '';
			// Check if event exist
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'count(*)',
				$this->tableEvents,
				$queryWhere,
				$groupBy,
				$orderBy,
				$limit);
			if ($res) {
				$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
				if ($row['count(*)']>0) {
					$resultList = t3lib_div::rmFromList($thisPage, $resultList);
				}
				$GLOBALS['TYPO3_DB']->sql_free_result($res);
			}
		}
		return $resultList;
	}

	/**
	 * Get all pages which have common content
	 *
	 * @param	string		page uid
	 * @param	string		page pid
	 * @return	string		the list of pids, without pages with event data
	 * @access public
	 */
	function getCommonPids($pageUid, $pagePid){
		// Check for root page
		if ($pageUid<>0) {
			// Get list of pid
			$selectedPids = $this->getRecursiveUidList($pageUid, 2);
			// Check if sub pages available and remove main page from list
			if ($selectedPids<>$pageUid) {
				$selectedPids = t3lib_div::rmFromList($pageUid, $selectedPids);
			} else {
				// Get id of parent page

				// if no sub pages and parent page is not root, get one level up
				if ($pagePid<>0) {
					$selectedPids = $this->getRecursiveUidList($pagePid, 2);
					// remove up level page
					$selectedPids = t3lib_div::rmFromList($pagePid, $selectedPids);
					// remove other event pages
					$selectedPids = $this->removeEventPages($selectedPids);
					// add this page to the list
					$selectedPids .= $selectedPids?',' . $pageUid:$pageUid;
				}
			}
		}
		return $GLOBALS['TYPO3_DB']->cleanIntList($selectedPids);
	}


	/**
	 * Sets the table names.
	 *
	 * @return	void		...
	 * @access protected
	 */
	function setTableNames() {
		global $TCA;
		$dbPrefix = 'tx_' . $this->extKey . '_';

		$this->tableLocations           = $dbPrefix . 'locations';
		$this->tableRooms               = $dbPrefix . 'rooms';
		$this->tableSpeakers            = $dbPrefix . 'speakers';
		$this->tableCategories          = $dbPrefix . 'categories';
		$this->tableEvents              = $dbPrefix . 'events';
		$this->tableSpeakerRestrictions = $dbPrefix . 'speakerrestrictions';
		$this->tableSessions            = $dbPrefix . 'sessions';
		$this->tableTimeslots           = $dbPrefix . 'timeslots';

		// Loading all TCA details for this table:
		t3lib_div::loadTCA($this->tableLocations);
		t3lib_div::loadTCA($this->tableRooms);
		t3lib_div::loadTCA($this->tableSpeakers);
		t3lib_div::loadTCA($this->tableCategories);
		t3lib_div::loadTCA($this->tableEvents);
		t3lib_div::loadTCA($this->tableSpeakerRestrictions);
		t3lib_div::loadTCA($this->tableSessions);
		t3lib_div::loadTCA($this->tableTimeslots);

		return;
	}

	/**
	 * Sets the record types.
	 *
	 * @return	void		...
	 * @access private
	 */
	function setRecordTypes() {
		$this->recordTypeComplete	= 0;
		$this->recordTypeTopic		= 1;
		$this->recordTypeDate		= 2;

		return;
	}

	/**
	 * Gets a value from flexforms or TS setup.
	 * The priority lies on flexforms; if nothing is found there, the value
	 * from TS setup is returned. If there is no field with that name in TS setup,
	 * an empty string is returned.
	 *
	 * @param	string		field name to extract
	 * @param	string		sheet pointer, eg. "sDEF"
	 * @param	boolean		whether this is a filename, which has to be combined with a path
	 * @param	boolean		whether to ignore the flexform values and just get the settings from TypoScript, may be empty
	 * @return	string		the value of the corresponding flexforms or TS setup entry (may be empty)
	 * @access private
	 */
	function getConfValue($fieldName, $sheet = 'sDEF', $isFileName = false, $ignoreFlexform = false) {
		$flexformsValue = '';
		if (!$ignoreFlexform) {
			$flexformsValue = $this->pi_getFFvalue(
				$this->cObj->data['pi_flexform'],
				$fieldName,
				$sheet
			);
		}
		if ($isFileName && !empty($flexformsValue)) {
			$flexformsValue = $this->addPathToFileName($flexformsValue);
		}
		$confValue = isset($this->conf[$fieldName]) ? $this->conf[$fieldName] : '';

		return ($flexformsValue) ? $flexformsValue : $confValue;
	}

	/**
	 * Adds a path in front of the file name.
	 * This is used for files that are selected in the Flexform of the front-end plugin.
	 *
	 * If no path is provided, the default (uploads/[extension_name]/) is used as path.
	 *
	 * An example (default, with no path provided):
	 * If the file is named 'template.tmpl', the output will be 'uploads/[extension_name]/template.tmpl'.
	 * The '[extension_name]' will be replaced by the name of the calling extension.
	 *
	 * @param	string		the file name
	 * @param	string		the path to the file (without filename), must contain a slash at the end, may contain a slash at the beginning (if not relative)
	 * @return	string		the complete path including file name
	 * @access private
	 */
	function addPathToFileName($fileName, $path = '') {
		if (empty($path)) {
			$path = 'uploads/tx_' . $this->extKey . '/';
		}

		return $path . $fileName;
	}

	/**
	 * Gets a trimmed string value from flexforms or TS setup.
	 * The priority lies on flexforms; if nothing is found there, the value
	 * from TS setup is returned. If there is no field with that name in TS setup,
	 * an empty string is returned.
	 *
	 * @param	string		field name to extract
	 * @param	string		sheet pointer, eg. "sDEF"
	 * @param	boolean		whether this is a filename, which has to be combined with a path
	 * @param	boolean		whether to ignore the flexform values and just get the settings from TypoScript, may be empty
	 * @return	string		the trimmed value of the corresponding flexforms or TS setup entry (may be empty)
	 * @access public
	 */
	function getConfValueString($fieldName, $sheet = 'sDEF', $isFileName = false, $ignoreFlexform = false) {
		return trim($this->getConfValue($fieldName, $sheet, $isFileName, $ignoreFlexform));
	}

	/**
	 * Checks whether a string value from flexforms or TS setup is set.
	 * The priority lies on flexforms; if nothing is found there, the value
	 * from TS setup is checked. If there is no field with that name in TS setup,
	 * false is returned.
	 *
	 * @param	string		field name to extract
	 * @param	string		sheet pointer, eg. "sDEF"
	 * @param	boolean		whether to ignore the flexform values and just get the settings from TypoScript, may be empty
	 * @return	boolean		whether there is a non-empty value in the corresponding flexforms or TS setup entry
	 * @access public
	 */
	function hasConfValueString($fieldName, $sheet = 'sDEF', $ignoreFlexform = false) {
		return ($this->getConfValueString($fieldName, $sheet, false, $ignoreFlexform) != '');
	}

	/**
	 * Gets an integer value from flexforms or TS setup.
	 * The priority lies on flexforms; if nothing is found there, the value
	 * from TS setup is returned. If there is no field with that name in TS setup,
	 * zero is returned.
	 *
	 * @param	string		field name to extract
	 * @param	string		sheet pointer, eg. "sDEF"
	 * @return	integer		the inval'ed value of the corresponding flexforms or TS setup entry
	 * @access public
	 */
	function getConfValueInteger($fieldName, $sheet = 'sDEF') {
		return intval($this->getConfValue($fieldName, $sheet));
	}

	/**
	 * Checks whether an integer value from flexforms or TS setup is set and non-zero.
	 * The priority lies on flexforms; if nothing is found there, the value
	 * from TS setup is checked. If there is no field with that name in TS setup,
	 * false is returned.
	 *
	 * @param	string		field name to extract
	 * @param	string		sheet pointer, eg. "sDEF"
	 * @return	boolean		whether there is a non-zero value in the corresponding flexforms or TS setup entry
	 * @access public
	 */
	function hasConfValueInteger($fieldName, $sheet = 'sDEF') {
		return (boolean) $this->getConfValueInteger($fieldName, $sheet);
	}

	/**
	 * Gets a boolean value from flexforms or TS setup.
	 * The priority lies on flexforms; if nothing is found there, the value
	 * from TS setup is returned. If there is no field with that name in TS setup,
	 * false is returned.
	 *
	 * @param	string		field name to extract
	 * @param	string		sheet pointer, eg. "sDEF"
	 * @return	boolean		the boolean value of the corresponding flexforms or TS setup entry
	 * @access public
	 */
	function getConfValueBoolean($fieldName, $sheet = 'sDEF') {
		return (boolean) $this->getConfValue($fieldName, $sheet);
	}

	/**
	 * Extracts a value within listView.
	 *
	 * @param	string		TS setup field name to extract (within listView.), must not be empty
	 * @return	string		the trimmed contents of that field within listView. (may be empty)
	 * @access public
	 */
	function getListViewConfValueString($fieldName) {
		$result = '';
		if (isset($this->conf['listView.'])
			&& isset($this->conf['listView.'][$fieldName])) {
			$result = trim($this->conf['listView.'][$fieldName]);
		}

		return $result;
	}

	/**
	 * Checks whether a front-end user is logged in.
	 *
	 * @return	boolean		true if a user is logged in, false otherwise
	 * @access public
	 */
	function isLoggedIn() {
		return ((boolean) $GLOBALS['TSFE']) && ((boolean) $GLOBALS['TSFE']->loginUser);
	}

	/**
	 * If a user logged in, retrieves that user's data as stored in the
	 * table "feusers" and stores it in $this->feuser.
	 *
	 * If no user is logged in, $this->feuser will be null.
	 *
	 * @return	void		...
	 * @access private
	 */
	function retrieveFEUser() {
		$this->feuser = $this->isLoggedIn() ? $GLOBALS['TSFE']->fe_user->user : null;
	}

	/**
	 * Returns the UID of the currently logged-in FE user
	 * or 0 if no FE user is logged in.
	 *
	 * @return	integer		the UID of the logged-in FE user or 0 if no FE user is logged in
	 * @access public
	 */
	function getFeUserUid() {
		// If we don't have the FE user's UID (yet), try to retrieve it.
		if (!$this->feuser) {
			$this->retrieveFEUser();
		}

		return ($this->isLoggedIn() ? intval($this->feuser['uid']) : 0);
	}


	/**
	 * Provides data items from the DB.
	 *
	 * By default, the field "title" is used as the name that will be returned
	 * within the array (as caption). For FE users, the field "name" is used.
	 *
	 * @param	array		array that contains any pre-filled data (may be empty, but not null)
	 * @param	string		the table name to query
	 * @param	string		query parameter that will be used as the WHERE clause (may be omitted)
	 * @param	string		whether to append a <br /> at the end of each caption
	 * @return	array		$items with additional items from the $params['what'] table as an array with the keys "caption" (for the title) and "value" (for the uid), might be empty, will not be null
	 * @access public
	 */
	function populateList($items, $tableName, $queryParameter = '1=1', $appendBr = false) {
		$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'*',
			$tableName,
				$queryParameter
				. $this->enableFields($tableName),
			'',
			'title',
			'');

		$titlePostfix = $appendBr ? '<br />' : '';

		if ($dbResult) {
			while ($dbResultRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult)) {
				$uid = $dbResultRow['uid'];
				// Use the field "name" instead of "title" if we are
				// selecting FE users.
				if ($tableName == 'fe_users') {
					$title = $dbResultRow['name'];
				} else {
					$title = $dbResultRow['title'];
				}

				$items[$uid] = array(
					'caption'	=> $title . $titlePostfix,
					'value'		=> $uid
				);
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($dbResult);
		}

		// Reset the array pointer as the populateList* functions expect
		// arrays with a reset array pointer.
		reset($items);

		return $items;
	}

	/**
	 * Gets the ID of the currently selected back-end page.
	 *
	 * @return	integer		the current back-end page ID (or 0 if there is an error)
	 * @access public
	 */
	function getCurrentBePageId() {
		return intval(t3lib_div::_GP('id'));
	}

	/**
	 * Wrapper function for t3lib_pageSelect::enableFields() since it is no longer
	 * accessible statically.
	 *
	 * Returns a part of a WHERE clause which will filter out records with start/end
	 * times or deleted/hidden/fe_groups fields set to values that should de-select
	 * them according to the current time, preview settings or user login.
	 * Is using the $TCA arrays "ctrl" part where the key "enablefields" determines
	 * for each table which of these features applies to that table.
	 *
	 * 						records are ignored. NOTICE: If you call this function,
	 * 						consider what to do with the show_hidden parameter.
	 * 						Maybe it should be set? See tslib_cObj->enableFields
	 * 						where it's implemented correctly.
	 * 						"starttime", "endtime", "fe_group" (keys from
	 * 						"enablefields" in TCA) and if set they will make sure
	 * 						that part of the clause is not added. Thus disables
	 * 						the specific part of the clause. For previewing etc.
	 * 						any versioning preview settings which might otherwise
	 * 						disable enableFields.
	 *
	 * @param	string		table name found in the $TCA array
	 * @param	integer		If $show_hidden is set (0/1), any hidden-fields in
	 * @param	array		Array you can pass where keys can be "disabled",
	 * @param	boolean		If set, enableFields will be applied regardless of
	 * @return	string		the clause starting like " AND ...=... AND ...=..."
	 * @access protected
	 */
	function enableFields($table, $show_hidden = -1, $ignore_array = array(), $noVersionPreview = false) {
		$pageSelect = t3lib_div::makeInstance('t3lib_pageSelect');
		return $pageSelect->enableFields(
			$table,
			$show_hidden,
			$ignore_array,
			$noVersionPreview
		);
	}

	/**
	 * Initializes page languages and icons
	 *
	 * @param	integer		$pageId: Page to look up for page overlays
	 * @return	void
	 */
	function initializeLanguages($pageId)	{
		global $TCA, $LANG, $BACK_PATH;

			// Look up page overlays:
		$this->pageOverlays = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'*',
			'pages_language_overlay',
			'pid=' . intval($pageId)
				. t3lib_BEfunc::deleteClause('pages_language_overlay')
				. t3lib_BEfunc::versioningPlaceholderClause('pages_language_overlay'),
			'',
			'',
			'',
			'sys_language_uid'
		);

		$t8Tools = t3lib_div::makeInstance('t3lib_transl8tools');
		$this->languageIconTitles = $t8Tools->getSystemLanguages($pageId, $BACK_PATH);
	}

	/**
	 * Return the icon for the language
	 *
	 * @param	integer		Sys language uid
	 * @return	string		Language icon
	 */
	function languageFlag($sys_language_uid)	{
		return ($this->languageIconTitles[$sys_language_uid]['flagIcon'] ? '<img src="' . $this->languageIconTitles[$sys_language_uid]['flagIcon'] . '" class="absmiddle" alt="" />&nbsp;' : '')
			. htmlspecialchars($this->languageIconTitles[$sys_language_uid]['title']);
	}

	/**
	 * Creates the URL to this script, including all relevant GPvars
	 *
	 * @param	string		Alternative id value. Enter blank string for the current id ($this->id)
	 * @return	string		URL
	 */
	function listURL($altId='')	{
		return $this->script . '?id=' . (strcmp($altId, '')?$altId:$this->id);
	}

	/**
	 * Creates the localization panel
	 *
	 * @param	string		The table
	 * @param	array		The record for which to make the localization panel.
	 * @return	array		Array with key 0/1 with content for column 1 and 2
	 */
	function makeLocalizationPanel($table, $row)	{
		global $TCA, $LANG, $BE_USER, $BACK_PATH;

#debug($TCA[$table], 'TCA[' . $table . ']');

		$out = array(
			0 => '',
			1 => '',
		);

		$t8Tools = t3lib_div::makeInstance('t3lib_transl8tools');
		$translations = $t8Tools->translationInfo($table, $row['uid']);

		# Language title and icon:
		$out[0] = $this->languageFlag($row[$TCA[$table]['ctrl']['languageField']]);

		if (is_array($translations))	{

#			if ($BE_USER->check('tables_modify', $table)
#				&& $BE_USER->doesUserHaveAccess(t3lib_BEfunc::getRecord('pages', $row['pid']), 16)) {
#				$params = '&edit[' . $table . '][' . $uid . ']=edit';
#				$returnUrl = 'returnUrl=' . rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI'));
#				$editOnClick = 'alt_doc.php?' . $returnUrl . $params;
#			}

			# Traverse page translations and add icon for each language that does NOT yet exist:
			$lNew = '';
			foreach($this->pageOverlays as $lUid_OnPage => $lsysRec)	{
				if (!isset($translations['translations'][$lUid_OnPage]) && $GLOBALS['BE_USER']->checkLanguageAccess($lUid_OnPage))	{
					$href = $GLOBALS['TBE_TEMPLATE']->issueCommand(
						'&cmd[' . $table . '][' . $row['uid'] . '][localize]=' . $lUid_OnPage //,
#						$editOnClick
#						$this->listURL() . '&justLocalized=' . rawurlencode($table . ':' . $row['uid'] . ':' . $lUid_OnPage)
#						. '&returnUrl=' . t3lib_div::getIndpEnv('REQUEST_URI')
					);

					$lC = ($this->languageIconTitles[$lUid_OnPage]['flagIcon'] ? '<img src="' . $this->languageIconTitles[$lUid_OnPage]['flagIcon'] . '" class="absmiddle" alt="" />' : $this->languageIconTitles[$lUid_OnPage]['title']);
					$lC = '<a href="' . t3lib_div::getIndpEnv('TYPO3_SITE_URL') . 'typo3/' . htmlspecialchars($href) . '">' . $lC . '</a> ';

					$lNew .= $lC;
				}
			}

			if ($lNew) $out[1] .= $lNew;
		} else {
			$out[0] = '&nbsp;&nbsp;&nbsp;&nbsp;' . $out[0];
		}


		return $out;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wse_events/class.tx_wseevents_dbplugin.php']) {
	include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wse_events/class.tx_wseevents_dbplugin.php']);
}

?>
