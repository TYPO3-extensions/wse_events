<?php
/***************************************************************
* Copyright notice
*
* (c) 2007 Michael Oehlhof <typo3@oehlhof.de>
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


/**
 * Class 'tx_wseevents_speakers' for the 'wse_events' extension.
 *
 * xxxxxxx
 * xxxxx
 *
 *
 * @package		TYPO3
 * @subpackage	tx_wseevents
 * @author		Michael Oehlhof
 */

// In case we're on the back end, PATH_tslib isn't defined yet.
if (!defined('PATH_tslib')) {
	define('PATH_tslib', t3lib_extMgm::extPath('cms').'tslib/');
}

// If we are in the back end, we include the extension's locallang.xml.
if ((TYPO3_MODE == 'BE') && is_object($LANG)) {
    $LANG->includeLLFile('EXT:wse_events/mod1/locallang.xml');
}

/**
 * Class 'tx_wseevents_speakers' for the 'wse_events' extension.
 *
 * @package		TYPO3
 * @subpackage	wse_events
 * @author		Michael Oehlhof <typo3@oehlhof.de>
 */
class tx_wseevents_speakers {
	/** The extension key. */
	var $extKey = 'wseevents';

	/**
	 * Dummy constructor: Does nothing.
	 *
	 * The base classe's constructor is called in $this->init().
	 *
	 * @return	void		...
	 */
	function tx_wseevents_speakers() {
	}

	/**
	 * This is the main function
	 *
	 * @param	array		TypoScript configuration for the plugin
	 * @return	void		...
	 * @access protected
	 */
	function main($items) {
		return;
	}

	/**
	 * Get list of available speaker
	 *
	 * @param	array		TypoScript configuration for the plugin
	 * @param	object		$fobj: ToDo: insert description
	 * @return	void		...
	 * @access protected
	 */
	function getTCAspeakerlist($PA,$fobj) {
#		debug ($PA);
#		debug ($fobj);

		// --------------------- Get the list of speakers ---------------------
		// Initialize variables for the database query.
		$tableName ='tx_wseevents_speakers';
		$queryWhere = 'sys_language_uid=0'.t3lib_BEfunc::BEenableFields($tableName);
		$additionalTables = '';
		$groupBy = '';
		$orderBy = 'name,firstname';
		$limit = '';

		// Get list of all rooms of the location
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'*',
			$tableName,
			$queryWhere,
			$groupBy,
			$orderBy,
			$limit);

		// Clear the item array
		$PA['items'] = array();
		// Fill item array with rooms of location of selected event
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			// Add the name and id to the itemlist
			$entry = array();
			$entry[0] = $row['name'].', '.$row['firstname'];
			$entry[1] = $row['uid'];
			$entry[2] = '';
			$PA['items'][] = $entry;
		}

#		debug ($PA);

		return;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wse_events/class.tx_wseevents_speakers.php']) {
	include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wse_events/class.tx_wseevents_speakers.php']);
}

?>
