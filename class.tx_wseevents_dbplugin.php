<?php
/***************************************************************
* Copyright notice
*
* (c) 2007 Michael Oehlhof
* All rights reserved
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
 * @package	TYPO3
 * @subpackage	tx_wseevents
 * @author		Michael Oehlhof
 */

// the UTF-8 representation of an en dash
DEFINE(UTF8_EN_DASH, chr(0xE2).chr(0x80).chr(0x93));
// a CR-LF combination (the default Unix line ending)
DEFINE(CRLF, chr(0x0D).chr(0x0A));

require_once(PATH_t3lib.'class.t3lib_tstemplate.php');
require_once(PATH_t3lib.'class.t3lib_page.php');

// In case we're on the back end, PATH_tslib isn't defined yet.
if (!defined('PATH_tslib')) {
	define('PATH_tslib', t3lib_extMgm::extPath('cms').'tslib/');
}
require_once(PATH_tslib.'class.tslib_pibase.php');

// If we are in the back end, we include the extension's locallang.xml.
if ((TYPO3_MODE == 'BE') && is_object($LANG)) {
    $LANG->includeLLFile('EXT:wse_events/mod1/locallang.xml');
}

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
	var $tableSpeakerAttendance;
	var $tableSessions;
	var $tableTimeslots;

	// Constants for the types of records
	var $recordTypeComplete;
	var $recordTypeTopic;
	var $recordTypeDate;

	/** The front-end user who currently is logged in. */
	var $feuser = null;

	/**
	 * Dummy constructor: Does nothing.
	 *
	 * The base classe's constructor is called in $this->init().
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
	 *
	 * @access	protected
	 */
	function init($conf = null) {
		static $cachedConfigs = array();

		if (!$this->isInitialized) {
			if ($GLOBALS['TSFE'] && !isset($GLOBALS['TSFE']->config['config'])) {
				$GLOBALS['TSFE']->config['config'] = array();
			}

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

						$this->conf =& $template->setup['plugin.']['tx_'.$this->extKey.'.'];
						$cachedConfigs[$pageId] =& $this->conf;
					}
				} else {
					// On the front end, we can use the provided template setup.
					$this->conf =& $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_'.$this->extKey.'.'];
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
	 * Sets the table names.
	 *
	 * @access	protected
	 */
	function setTableNames() {
		$dbPrefix = 'tx_'.$this->extKey.'_';

		$this->tableLocations         = $dbPrefix.'locations';
		$this->tableRooms             = $dbPrefix.'rooms';
		$this->tableSpeakers          = $dbPrefix.'speakers';
		$this->tableCategories        = $dbPrefix.'categories';
		$this->tableEvents            = $dbPrefix.'events';
		$this->tableSpeakerAttendance = $dbPrefix.'speaker_attendance';
		$this->tableSessions          = $dbPrefix.'sessions';
		$this->tableTimeslots         = $dbPrefix.'timeslots';

		return;
	}

	/**
	 * Sets the record types.
	 *
	 * @access	private
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
	 *
	 * @return	string		the value of the corresponding flexforms or TS setup entry (may be empty)
	 *
	 * @access	private
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
	 *
	 * @return	string		the complete path including file name
	 *
	 * @access	private
	 */
	function addPathToFileName($fileName, $path = '') {
		if (empty($path)) {
			$path = 'uploads/tx_'.$this->extKey.'/';
		}

		return $path.$fileName;
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
	 *
	 * @return	string		the trimmed value of the corresponding flexforms or TS setup entry (may be empty)
	 *
	 * @access	public
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
	 *
	 * @return	boolean		whether there is a non-empty value in the corresponding flexforms or TS setup entry
	 *
	 * @access	public
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
	 *
	 * @return	integer		the inval'ed value of the corresponding flexforms or TS setup entry
	 *
	 * @access	public
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
	 *
	 * @return	boolean		whether there is a non-zero value in the corresponding flexforms or TS setup entry
	 *
	 * @access	public
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
	 *
	 * @return	boolean		the boolean value of the corresponding flexforms or TS setup entry
	 *
	 * @access	public
	 */
	function getConfValueBoolean($fieldName, $sheet = 'sDEF') {
		return (boolean) $this->getConfValue($fieldName, $sheet);
	}

	/**
	 * Extracts a value within listView.
	 *
	 * @param	string		TS setup field name to extract (within listView.), must not be empty
	 *
	 * @return	string		the trimmed contents of that field within listView. (may be empty)
	 *
	 * @access	public
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
	 *
	 * @access	public
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
	 * @access	private
	 */
	function retrieveFEUser() {
		$this->feuser = $this->isLoggedIn() ? $GLOBALS['TSFE']->fe_user->user : null;
	}

	/**
	 * Returns the UID of the currently logged-in FE user
	 * or 0 if no FE user is logged in.
	 *
	 * @return	integer		the UID of the logged-in FE user or 0 if no FE user is logged in
	 *
	 * @access	public
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
	 *
	 * @return	array		$items with additional items from the $params['what'] table as an array with the keys "caption" (for the title) and "value" (for the uid), might be empty, will not be null
	 *
	 * @access	public
	 */
	function populateList($items, $tableName, $queryParameter = '1=1', $appendBr = false) {
		$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'*',
			$tableName,
				$queryParameter
				.$this->enableFields($tableName),
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
					'caption'	=> $title.$titlePostfix,
					'value'		=> $uid
				);
			}
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
	 *
	 * @access	public
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
	 * @param	string		table name found in the $TCA array
	 * @param	integer		If $show_hidden is set (0/1), any hidden-fields in
	 * 						records are ignored. NOTICE: If you call this function,
	 * 						consider what to do with the show_hidden parameter.
	 * 						Maybe it should be set? See tslib_cObj->enableFields
	 * 						where it's implemented correctly.
	 * @param	array		Array you can pass where keys can be "disabled",
	 * 						"starttime", "endtime", "fe_group" (keys from
	 * 						"enablefields" in TCA) and if set they will make sure
	 * 						that part of the clause is not added. Thus disables
	 * 						the specific part of the clause. For previewing etc.
	 * @param	boolean		If set, enableFields will be applied regardless of
	 * 						any versioning preview settings which might otherwise
	 * 						disable enableFields.
	 * @return	string		the clause starting like " AND ...=... AND ...=..."
	 *
	 * @access	protected
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
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wse_events/class.tx_wseevents_dbplugin.php']) {
	include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wse_events/class.tx_wseevents_dbplugin.php']);
}

?>
