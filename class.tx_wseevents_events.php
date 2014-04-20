<?php
/***************************************************************
* Copyright notice
*
* (c) 2007-2009 Michael Oehlhof <typo3@oehlhof.de>
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
	define('PATH_tslib', t3lib_extMgm::extPath('cms') . 'tslib/');
}

// If we are in the back end, we include the extension's locallang.xml.
if ((TYPO3_MODE == 'BE') && is_object($LANG)) {
    $LANG->includeLLFile('EXT:wse_events/mod1/locallang.xml');
}

/**
 * Class 'tx_wseevents_events' for the 'wse_events' extension.
 *
 * @package		TYPO3
 * @subpackage	wse_events
 * @author		Michael Oehlhof <typo3@oehlhof.de>
 */
class tx_wseevents_events implements t3lib_singleton {
	/** The extension key. */
	var $extKey = 'wseevents';

	// List of id's of rooms
	var $roomIds;

	// Array to cache event data
	var $cacheEvent;

	/**
	 * @var tx_wseevents_rooms
	 */
	var $rooms;

	/**
	 * Constructor: Initializes cache.
	 *
	 * @return \tx_wseevents_events ...
	 */
	function __construct() {
		// Initialize classes
		$this->rooms = t3lib_div::makeInstance('tx_wseevents_rooms', $this);
		// Initialize variables
		$this->cacheEvent = array();
	}

	/**
	 * Get list of event days
	 *
	 * @param	array		$params Array of items passed by reference.
	 * @param	object		$pObj The parent object (t3lib_TCEforms / t3lib_transferData depending on context)
	 * @return	void		...
	 */
	function getTCAeventDays(&$params, &$pObj) {
		global $LANG;

		$thisPid = $params['row']['pid'];
		// If pid is negative than the pid is the uid of the last saved record
		// and we must get the pid of the folder from the last saved record
		if (0 > $thisPid) {
			$lastPage = t3lib_BEfunc::getRecord($params['table'], abs($thisPid));
			$thisPid = $lastPage['pid'];
		}

		// Get the event info
		$eventInfo = $this->getEventInfo($params['row']['event'], $thisPid);

		// Clear the item array
		$params['items'] = array();

		$thisDay = 1;
		$maxDay = $eventInfo['length'];
		// Create list of event days
		while ($thisDay<=$maxDay) {
			// Add the name and id to the itemlist
			$entry = array();
			$entry[0] = $LANG->getLL('events.length') . ' ' . $thisDay;
			$entry[1] = $thisDay;
			$entry[2] = '';
			$params['items'][] = $entry;
			$thisDay += 1;
		}
	}

	/**
	 * Get length of session
	 *
	 * @param	array		$params Array of items passed by reference.
	 * @return	void
	 * @access protected
	 */
	function getTCAsessionLength(&$params) {
		// Clear the item array
		$params['items'] = array();

		$thisPid = $params['row']['pid'];
		// If pid is negative than the pid is the uid of the last saved record
		// and we must get the pid of the folder from the last saved record
		if ($thisPid < 0) {
			$lastPage = t3lib_BEfunc::getRecord($params['table'], abs($thisPid));
			$thisPid = $lastPage['pid'];
		}

		// Get the event info
		$eventInfo = $this->getEventInfo($params['row']['event'], $thisPid);

		$thisSlot = 1;
		$maxSlot = $eventInfo['maxslot'];
		$defSlot = $eventInfo['defslotcount'];
		$slotSize = $eventInfo['slotsize'];
		// Create list of event days
		while ($thisSlot<=$maxSlot) {
			// Add the name and id to the itemlist
			$entry = array();
			$entry[0] = $thisSlot * $slotSize;
			$entry[1] = $thisSlot;
			$entry[2] = '';
			$params['items'][] = $entry;
			$thisSlot += 1;
		}
		$params['row']['length'] = $defSlot;
	}

	/**
	 * Get default session length
	 *
	 * @param	array		$params Array of items passed by reference.
	 * @return	int
	 * @access protected
	 */
	function getTCAsessionDefault(&$params) {
		// Clear the item array
		$params['items'] = array();
		// Get the event info
		if (isset($params['row']['event'])) {
			$event = $params['row']['event'];
		} else {
			$event = 0;
		}
		$eventInfo = $this->getEventInfo($event);
		$defSlot = $eventInfo['defslotcount'];

		return $defSlot;
	}

	/**
	 * Get list of slots for the event
	 *
	 * @param	array		$params Array of items passed by reference.
	 * @return	void
	 * @access protected
	 */
	function getTCAslotList(&$params) {

		// Clear the item array
		$params['items'] = array();

		$thisPid = $params['row']['pid'];
		// If pid is negative than the pid is the uid of the last saved record
		// and we must get the pid of the folder from the last saved record
		if (0 > $thisPid) {
			$lastPage = t3lib_BEfunc::getRecord($params['table'], abs($thisPid));
			$thisPid = $lastPage['pid'];
		}

		// Get the event info
		$slotList = $this->getEventSlotList($params['row']['event'], $thisPid);

		$thisSlot = 1;
		// Create list of event slots
		foreach ($slotList as $slot) {
			// Add the name and id to the itemlist
			$entry = array();
			$entry[0] = $slot;
			$entry[1] = $thisSlot;
			$entry[2] = '';
			$params['items'][] = $entry;
			$thisSlot += 1;
		}
	}

	/**
	 * Get info about an event
	 *
	 * @param	integer		$event Id of an event
	 * @param	integer		$eventPid Page to search for events if $event is set to 0
	 * @return	array		Event record
	 * @access protected
	 */
	function getEventInfo($event, $eventPid=0) {
		global $TCA;

		// Check if an event is cached
		if ($event > 0) {
			if (!empty($this->cacheEvent[$event])) {
				return $this->cacheEvent[$event];
			}
		}
		// Initialize variables for the database query.
		$tableName ='tx_wseevents_events';

		// Loading all TCA details for this table:
		t3lib_div::loadTCA($tableName);

		if (0 == $eventPid) {
			$pidWhere = '0=0';
		} else {
			$pidWhere = 'pid=' . $eventPid;
		}
		if (0 < $event) {
			$queryWhere = 'uid=' . $event;
		} else {
			$queryWhere = $pidWhere . t3lib_BEfunc::deleteClause($tableName);
		}
		$queryWhere .= ' AND ' . $TCA[$tableName]['ctrl']['languageField'] . '=0'
			. t3lib_BEfunc::versioningPlaceholderClause($tableName);
		$groupBy = '';
		$orderBy = 'name';
		$limit = '';

		// Get info about the event
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'*',
			$tableName,
			$queryWhere,
			$groupBy,
			$orderBy,
			$limit);
		$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
		$GLOBALS['TYPO3_DB']->sql_free_result($res);
		// Put event data into cache
		$this->cacheEvent[$row['uid']] = $row;
		return $row;
	}

	/**
	 * Get list of slots for an event
	 *
	 * @param	string		$event Id of event
	 * @param	integer		$eventPid Page to search for events if $event is set to 0
	 * @return	array		List of slots for the event
	 * @access protected
	 */
	function getEventSlotList($event, $eventPid = 0) {
		global $TCA;

		$row = $this->getEventInfo($event, $eventPid);

		// Clear the item array
		$slotList = array();

		if (!empty($row)) {
			$begin = $row['timebegin'];
			$end = $row['timeend'];
			$size = $row['slotsize'];

			if ((!empty($begin)) && (!empty($end))) {
				list($this_h, $this_m) = explode(':', $begin);
				list($end_h, $end_m) = explode(':', $end);

				$itemIndex = 1;
				$finished = false;
				// Fill item array with time slots of selected event
				while (!$finished) {
					$thisTime = sprintf('%02d:%02d', $this_h, $this_m);
					$slotList[$itemIndex] =$thisTime;
					$this_m += intval($size);
					if ($this_m>=60) {
						$this_h += 1;
						$this_m -= 60;
					}
					if ($slotList[$itemIndex]==$end) {
						$finished = true;
					}
					if (($this_m>=$end_m) && ($this_h>=$end_h)) {
						$finished = true;
					}
					$itemIndex += 1;
				}
			}
		}
		return $slotList;
	}

	/**
	 * Get list of slots for an event
	 *
	 * @param	string		$event Id of event
	 * @param	integer		$eventPid Page to search for events if $event is set to 0
	 * @return	array		List of slots for the event
	 * @access protected
	 */
	function getEventSlotArray($event, $eventPid = 0) {
		global $TCA;

		$row = $this->getEventInfo($event, $eventPid);

		// Clear the item array
		$slotList = array();
		$slotArray = array();

		if (!empty($row)) {
			$begin = $row['timebegin'];
			$end = $row['timeend'];
			$size = $row['slotsize'];
			$dayCount = $row['length'];
			$location = $row['location'];

			$roomCount = $this->rooms->getRoomCount($location);

			list($this_h, $this_m) = explode(':', $begin);

			$itemIndex = 1;
			$finished = false;
			// Fill item array with time slots of selected event
			while (!$finished) {
				$thisTime = sprintf('%02d:%02d', $this_h, $this_m);
				$slotList[$itemIndex] = 1;
				$this_m += intval($size);
				if ($this_m>=60) {
					$this_h += 1;
					$this_m -= 60;
				}
				if ($this_h>=24) {
					$finished = true;
				}
				if ($thisTime==$end) {
					$finished = true;
				}
				$itemIndex += 1;
			}
			for ( $d = 1; $d <= $dayCount; $d++ ) {
				for ( $r = 1; $r <= $roomCount; $r++ ) {
					$slotArray[$d][$r] = $slotList;
				}
			}
		}
		return $slotArray;
	}

	/**
	 * Get list of room names of an event
	 *
	 * @param	integer		$event Id of an event
	 * @param	integer		$eventPid Page to search for events if $event is set to 0
	 * @return	array		List of room names
	 * @access protected
	 */
	function getEventRooms($event, $eventPid = 0) {
		$row = $this->getEventInfo($event, $eventPid);

		$roomList = array();
		if ($row) {
			$roomList[0] = '- All rooms -';
			// Get the room list from the location
			$location = $row['location'];
			if ($location>0) {
				$allRooms = $this->rooms->getRoomList($location);
				foreach ($allRooms as $oneRoom) {
					$roomList[$oneRoom['uid']] = $oneRoom['name'];
				}
			}
		}
		return $roomList;
	}

	/**
	 *
	 */
	function checkForToday ($event) {
		$eventInfo = $this->getEventInfo($event);
		$eventBegin = $eventInfo['begin'];
		$today = time();
		$dayToBegin = intval(($today - intval($eventBegin)) / 86400) + 1; // (60 * 60 * 24)
		if (($dayToBegin < 1) or ($dayToBegin > intval($eventInfo['length']))) {
			$dayNr = 1;
			$slotNr = 0;
		} else {
			// Today is during the event
			$dayNr = $dayToBegin;
			$timeBegin = intval(intval(substr($eventInfo['timebegin'], 0, 2)) * 60 + intval(substr($eventInfo['timebegin'], 3, 2)));
			$timeEnd = intval(intval(substr($eventInfo['timeend'], 0, 2)) * 60 + intval(substr($eventInfo['timeend'], 3, 2)));
			$timeTodayString = date('H:i', $today);
			$timeToday = intval(intval(substr($timeTodayString, 0, 2)) * 60 + intval(substr($timeTodayString, 3, 2)));
			$slotCount = intval(($timeEnd - $timeBegin) / intval($eventInfo['slotsize']));
			$slotToday = intval(($timeToday - $timeBegin) / intval($eventInfo['slotsize'])) + 1;
			if (($slotToday < 1) or ($slotToday > $slotCount)) {
				$slotNr = 0;
			} else {
				$slotNr = $slotToday;
			}
		}
		return array($dayNr, $slotNr);
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wse_events/class.tx_wseevents_events.php']) {
	include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wse_events/class.tx_wseevents_events.php']);
}
