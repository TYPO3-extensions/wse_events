<?php
/***************************************************************
* Copyright notice
*
* (c) 2007-2009 Michael Oehlhof
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

// In case we're on the back end, PATH_tslib isn't defined yet.
if (!defined('PATH_tslib')) {
	define('PATH_tslib', t3lib_extMgm::extPath('cms').'tslib/');
}

// If we are in the back end, we include the extension's locallang.xml.
if ((TYPO3_MODE == 'BE') && is_object($LANG)) {
    $LANG->includeLLFile('EXT:wse_events/mod1/locallang.xml');
}

/**
 * Class 'tx_wseevents_rooms' for the 'wse_events' extension.
 *
 * @package		TYPO3
 * @subpackage	wse_events
 * @author		Michael Oehlhof <typo3@oehlhof.de>
 */
class tx_wseevents_rooms {
	/** The extension key. */
	var $extKey = 'wseevents';

	/**
	 * Event class
	 * @var tx_wseevents_events
	 */
	var $events;

	/**
	 * Room count, index is location id
	 * @var array
	 */
	var $count;

	/**
	 * List of rooms, first index is location id
	 * @var array
	 */
	var $rooms;

	/**
	 * Constructor: Create needed class.
	 *
	 * @param $event
	 * @return \tx_wseevents_rooms
	 */
	function __construct($event) {
		// Initialize classes
		$this->events = $event;
		// and variables
		$this->count = array();
		$this->rooms = array();
	}

	/**
	 * Get count of rooms for a location
	 *
	 * @param int $location
	 * @return int
	 */
	function getRoomCount($location) {
		global $TCA;

		if (isset($this->count[$location])) {
			return $this->count[$location];
		}

		$groupBy = '';
		$orderBy = '';
		$limit = '';
		$tableName ='tx_wseevents_rooms';
		$queryWhere = 'location=' . $location
			. ' AND ' . $TCA[$tableName]['ctrl']['languageField'] . '=0'
			. t3lib_BEfunc::versioningPlaceholderClause($tableName);
		// Get info about rooms of the event
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'count(*)',
			$tableName,
			$queryWhere,
			$groupBy,
			$orderBy,
			$limit);
		$locationRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
		$roomCount = $locationRow['count(*)'];
		$GLOBALS['TYPO3_DB']->sql_free_result($res);
		$this->count[$location] = $roomCount;

		return $roomCount;
	}

	/**
	 * @param $location
	 *
	 */
	function getRoomList($location) {
		global $TCA;

		if (isset($this->rooms[$location])) {
			return $this->rooms[$location];
		}

		$tableName ='tx_wseevents_rooms';
		$queryWhere = 'location=' . $location
			. ' AND ' . $TCA[$tableName]['ctrl']['languageField'] . '=0'
			. t3lib_BEfunc::versioningPlaceholderClause($tableName);
		$groupBy = '';
		$orderBy = 'uid';
		$limit = '';

		$roomList = array();
		// Get info about the rooms of the location
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'*',
			$tableName,
			$queryWhere,
			$groupBy,
			$orderBy,
			$limit);
		if ($res) {
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$roomList[$row['uid']] = $row;
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($res);
			$this->rooms[$location] = $roomList;
		}
		return $roomList;
	}

	/**
	 * Get list of available rooms
	 *
	 * @param	array		$PA TypoScript configuration for the plugin
	 * @param	object		$fobj: t3lib_TCEforms
	 * @return	void		...
	 * @access protected
	 */
	function getTCAroomlist($PA, $fobj) {
		global $TCA;

		// Check if events class is loaded
		if (empty($this->events)) {
			// Initialize classes
			$this->events = t3lib_div::makeInstance('tx_wseevents_events', $PA['row']['event']);
		}

		$row = $this->events->getEventInfo($PA['row']['event'], $PA['row']['pid']);
		$location = $row['location'];
		if (!empty($location)) {
			$location = 'location='.$location;
		} else {
			$location = '1=1';
		}

		// --------------------- Get the rooms of the location of the selected event ---------------------
		// Initialize variables for the database query.
		$tableName ='tx_wseevents_rooms';
		$queryWhere = $location.
			t3lib_BEfunc::BEenableFields($tableName).
			t3lib_BEfunc::deleteClause($tableName).
			' AND '.$TCA[$tableName]['ctrl']['languageField'].'=0'.
			t3lib_BEfunc::versioningPlaceholderClause($tableName);
		$groupBy = '';
		$orderBy = 'name';
		$limit = '';

		// Get list of all rooms of the location
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'*',
			$tableName,
			$queryWhere,
			$groupBy,
			$orderBy,
			$limit);

		// check if selected room is in location
		$roomFound = false;

		// Clear the item array
		$PA['items'] = array();
		// Fill item array with rooms of location of selected event
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			// Add the name and id to the itemlist
			$entry = array();
			$entry[0] = $row['name'];
			$entry[1] = $row['uid'];
			$entry[2] = '';
			$PA['items'][] = $entry;
			if ($row['uid'] = $PA['row']['room']) {
				$roomFound = true;
			}
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($res);

		// Add the name and id of ALL ROOMS to the itemlist
		$entry = array();
		$entry[0] = '- All rooms -'; //$LANG->getLL('timeslots.allrooms');
		$entry[1] = 0;
		$entry[2] = '';
		$PA['items'][] = $entry;

		// Set selected room to first room of location, if given room is from another location
		if (!$roomFound) {
			$PA['row']['room'] = $PA['items']['0']['1'];
		}

		return;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wse_events/class.tx_wseevents_rooms.php']) {
	include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wse_events/class.tx_wseevents_rooms.php']);
}
