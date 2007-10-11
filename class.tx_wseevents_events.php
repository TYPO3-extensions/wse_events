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
 * Class 'tx_wseevents_events' for the 'wse_events' extension.
 *
 * xxxxxxx
 * xxxxx
 *
 *
 * @package		TYPO3
 * @subpackage	tx_wseevents
 * @author		Michael Oehlhof <typo3@oehlhof.de>
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
 * Class 'tx_wseevents_events' for the 'wse_events' extension.
 *
 * @package		TYPO3
 * @subpackage	wse_events
 * @author		Michael Oehlhof <typo3@oehlhof.de>
 */
class tx_wseevents_events {
	/** The extension key. */
	var $extKey = 'wseevents';

	/**
	 * Dummy constructor: Does nothing.
	 *
	 * The base classe's constructor is called in $this->init().
	 *
	 * @return	void		...
	 */
	function tx_wseevents_events() {
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
	 * Get list of event days
	 *
	 * @param	array		TypoScript configuration for the plugin
	 * @return	void		...
	 * @access protected
	 */
	function getTCAeventDays($PA) {
		global $LANG;

		// Clear the item array
		$PA['items'] = array();
		// Get the event info
		$eventinfo = $this->getEventInfo($PA['row']['event']);

		$thisday = 1;
		$maxday = $eventinfo['length'];
		// Create list of event days
		while ($thisday<=$maxday) {
			// Add the name and id to the itemlist
			$entry = array();
			$entry[0] = $LANG->getLL('events.length').' '.$thisday;
			$entry[1] = $thisday;
			$entry[2] = '';
			$PA['items'][] = $entry;
			$thisday += 1;
		}
	}

	/**
	 * Get length of seesion
	 *
	 * @param	array		TypoScript configuration for the plugin
	 * @return	void		...
	 * @access protected
	 */
	function getTCAsessionLength($PA) {
		// Clear the item array
		$PA['items'] = array();
		// Get the event info
		$eventinfo = $this->getEventInfo($PA['row']['event']);

		$thisslot = 1;
		$maxslot = $eventinfo['maxslot'];
		$defslot = $eventinfo['defslotcount'];
		$slotsize = $eventinfo['slotsize'];
		// Create list of event days
		while ($thisslot<=$maxslot) {
			// Add the name and id to the itemlist
			$entry = array();
			$entry[0] = $thisslot*$slotsize;
			$entry[1] = $thisslot;
			$entry[2] = '';
			$PA['items'][] = $entry;
			$thisslot += 1;
		}
		$PA['row']['length'] = $defslot;
	}

	/**
	 * Get default session length
	 *
	 * @param	array		TypoScript configuration for the plugin
	 * @return	void		...
	 * @access protected
	 */
	function getTCAsessionDefault($PA) {
		// Clear the item array
		$PA['items'] = array();
		// Get the event info
		if (isset($PA['row']['event'])) {
			$event = $PA['row']['event'];
		} else {
			$event = 0;
		}
		$eventinfo = $this->getEventInfo($event);

		$thisslot = 1;
		$defslot = $eventinfo['defslotcount'];

		return $defslot;
	}

	/**
	 * Get list of slots for the event
	 *
	 * @param	array		TypoScript configuration for the plugin
	 * @return	void		...
	 * @access protected
	 */
	function getTCAslotList($PA) {

		// Clear the item array
		$PA['items'] = array();
		// Get the event info
		$slotlist = $this->getEventSlotList($PA['row']['event']);

		$thisslot = 1;
		// Create list of event days
		foreach ($slotlist as $slot) {
			// Add the name and id to the itemlist
			$entry = array();
			$entry[0] = $slot;
			$entry[1] = $thisslot;
			$entry[2] = '';
			$PA['items'][] = $entry;
			$thisslot += 1;
		}
	}

	/**
	 * Get info about an event
	 *
	 * @param	integer		Id of an event
	 * @return	array		Event record
	 * @access protected
	 */
	function getEventInfo($event) {

		// --------------------- Get the list of time slots ---------------------
		// Initialize variables for the database query.
		$tableName ='tx_wseevents_events';
		if ($event>0) {
			$queryWhere = 'uid='.$event;
		} else {
			$queryWhere = '0=0'.t3lib_BEfunc::deleteClause($tableName);
		}
		$groupBy = '';
		$orderBy = 'uid';
		$limit = '';

		// Get info about time slots of the event
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'*',
			$tableName,
			$queryWhere,
			$groupBy,
			$orderBy,
			$limit);
		$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
		return $row;
	}

	/**
	 * Get list of slots for an event
	 *
	 * @param	string		Id of event
	 * @return	array		List of slots for the event
	 * @access protected
	 */
	function getEventSlotList($event) {

		// --------------------- Get the list of time slots ---------------------
		// Initialize variables for the database query.
		$tableName ='tx_wseevents_events';
		if ($event>0) {
			$queryWhere = 'uid='.$event;
		} else {
			$queryWhere = '0=0'.t3lib_BEfunc::deleteClause($tableName);
		}
		$additionalTables = '';
		$groupBy = '';
		$orderBy = 'uid';
		$limit = '';

		// Get info about time slots of the event
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'*',
			$tableName,
			$queryWhere,
			$groupBy,
			$orderBy,
			$limit);
		$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
		// Clear the item array
		$slotlist = array();

		if (!empty($row)) {
			$begin = $row['timebegin'];
			$end = $row['timeend'];
			$size = $row['slotsize'];

			if ((!empty($begin)) && (!empty($begin))) {
				list($this_h, $this_m) = explode(':', $begin);
				list($end_h, $end_m) = explode(':', $end);

				$itemindex = 1;
				$finished = false;
				// Fill item array with time slots of selected event
				while (!$finished) {

					$thistime = sprintf('%02d:%02d', $this_h, $this_m);
					$slotlist[$itemindex] =$thistime;
					$this_m += intval($size);
					if ($this_m>=60) {
						$this_h += 1;
						$this_m -= 60;
					}
					if ($slotlist[$itemindex]==$end) {
						$finished = true;
					}
					$itemindex += 1;
				}
			}
		}
		return $slotlist;
	}

	/**
	 * Getlist of slots for an event
	 *
	 * @param	string		Id of event
	 * @return	array		List of slots for the event
	 * @access protected
	 */
	function getEventSlotArray($event) {

		// --------------------- Get the list of time slots ---------------------
		// Initialize variables for the database query.
		$tableName ='tx_wseevents_events';
		if ($event>0) {
			$queryWhere = 'uid='.$event;
		} else {
			$queryWhere = '0=0'.t3lib_BEfunc::deleteClause($tableName);
		}
		$additionalTables = '';
		$groupBy = '';
		$orderBy = 'uid';
		$limit = '';

		// Get info about time slots of the event
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'*',
			$tableName,
			$queryWhere,
			$groupBy,
			$orderBy,
			$limit);
		$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);

		// Clear the item array
		$slotlist = array();
		$slotarray = array();

		if (!empty($row)) {
			$begin = $row['timebegin'];
			$end = $row['timeend'];
			$size = $row['slotsize'];
			$daycount = $row['length'];
			$location = $row['location'];
			// Get info about rooms of the event
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'count(*)',
				'tx_wseevents_rooms',
				'location='.$location,
				$groupBy,
				$orderBy,
				$limit);
			$locationrow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			$roomcount = $locationrow['count(*)'];

			list($this_h, $this_m) = explode(':', $begin);

			$itemindex = 1;
			$finished = false;
			// Fill item array with time slots of selected event
			while (!$finished) {
				$thistime = sprintf('%02d:%02d', $this_h, $this_m);
				$slotlist[$itemindex] = 1;
				$this_m += intval($size);
				if ($this_m>=60) {
					$this_h += 1;
					$this_m -= 60;
				}
				if ($thistime==$end) {
					$finished = true;
				}
				$itemindex += 1;
			}
			for ( $d = 1; $d <= $daycount; $d++ ) {
				for ( $r = 1; $r <= $roomcount; $r++ ) {
					$slotarray[$d][$r] = $slotlist;
				}
			}
		}
		return $slotarray;
	}

	/**
	 * Get list of room names of an event
	 *
	 * @param	integer		Id of an event
	 * @return	array		List of room names
	 * @access protected
	 */
	function getEventRooms($event) {

		// --------------------- Get the list of time slots ---------------------
		// Initialize variables for the database query.
		$tableName ='tx_wseevents_events';
		if ($event>0) {
			$queryWhere = 'uid='.$event;
		} else {
			$queryWhere = '0=0'.t3lib_BEfunc::deleteClause($tableName);
		}
		$groupBy = '';
		$orderBy = 'uid';
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

		$roomlist = array();
		$roomlist[0] = '- All rooms -';
		// Get the room list from the location
		$location = $row['location'];
		if ($location>0) {
			$tableName ='tx_wseevents_rooms';
			$queryWhere = 'location='.$location;
			$groupBy = '';
			$orderBy = 'uid';
			$limit = '';

			// Get info about the event
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'*',
				$tableName,
				$queryWhere,
				$groupBy,
				$orderBy,
				$limit);
			$roomindex = 1;
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$roomlist[$roomindex] = $row['name'];
				$roomindex += 1;
			}
		}
		return $roomlist;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wse_events/class.tx_wseevents_events.php']) {
	include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wse_events/class.tx_wseevents_events.php']);
}

?>
