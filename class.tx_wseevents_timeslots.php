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
 * Class 'tx_wseevents_timeslots' for the 'wse_events' extension.
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

require_once(t3lib_extMgm::extPath('wse_events').'class.tx_wseevents_events.php');



	/**
	 * [Describe function...]
	 *
	 */
class tx_wseevents_timeslots {
	/** The extension key. */
	var $extKey = 'wseevents';

	/**
	 * Dummy constructor: Does nothing.
	 *
	 * The base classe's constructor is called in $this->init().
	 *
	 * @return	[type]		...
	 */
	function tx_wseevents_timeslots() {
	}

	/**
	 *
	 * @param	array		TypoScript configuration for the plugin
	 * @return	[type]		...
	 * @access protected
	 */
	function main($items) {
		return;
	}


	/**
	 * Format the name of a time slot
	 *
	 * @param	array		Recoord data of slot
	 * @return	string		Localized name of time slot
	 * @access protected
	 */
	function formatSlotName($row) {
		if (empty($row['event'])) {
			return;
		}
		$secofday = 60*60*24;
		// Get record with event data
		$eventdata = tx_wseevents_events::getEventInfo($row['event']);
		// Get localized name of weekday
		$thisday = $eventdata['begin']+($row['eventday']-1)*$secofday;
		$weekday = strftime('%A', $thisday);
		// Get list of time slots in format hh:mm
		$eventslots = tx_wseevents_events::getEventSlotList($row['event']);
		// Get name of room
		$eventrooms = tx_wseevents_events::getEventRooms($row['event']);
		// Compose name of time slot
		return	$weekday.' '.$eventslots[$row['begin']].'-'.$eventslots[$row['begin']+$row['length']].' '.$eventrooms[$row['room']];
	}

	/**
	 * Get the localized name of a time slot
	 *
	 * @param	integer		Id of slot
	 * @return	string		Localized name of time slot
	 * @access protected
	 */
	function getSlotName($slotid) {
		$slotname = '';
		$where = 'uid='.$slotid;
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid,event,eventday,room,begin,length', 'tx_wseevents_timeslots', $where);
		if ($res) {
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			$slotname = formatSlotName($row);
		}
		return $slotname;
	}

	/**
	 *
	 * @param	array		TypoScript configuration for the plugin
	 * @return	[type]		...
	 * @access protected
	 */
	function getTCAavailableSlots($PA) {
#debug($PA);
		// Clear the item array
		$PA['items'] = array();
		
		$eventid = $PA['row']['event'];
		// Get event record
		if ($eventid==0) {
			$queryWhere = 'pid='.$PA['row']['pid'];
			$tableName = 'tx_wseevents_events';
			$groupBy = '';
			$orderBy = 'uid';
			$limit = '';
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'*',
				$tableName,
				$queryWhere,
				$groupBy,
				$orderBy,
				$limit);
			if ($res) {
				$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
				$eventid = $row['uid'];
			}
		}
		if ($eventid>0) {
			// Get list of all time slots for the event
			$tableName = 'tx_wseevents_timeslots';
			$queryWhere = 'deleted=0 AND hidden=0 AND sys_language_uid=0 AND event='.$eventid;
			$groupBy = '';
			$orderBy = 'eventday,begin,room';
			$limit = '';
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'*',
				$tableName,
				$queryWhere,
				$groupBy,
				$orderBy,
				$limit);
			if ($res) {
				$slotlist = array();
				while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
					$slotlist[] = $row;
				}

				// Get list of speakers of the session
				$sp1 = $PA['row']['speaker'];
				$sp2 = array();
				$sp2 = explode(',',$sp1);
				$speakerlist = '';
				$speakerslotlist = '';
				foreach($sp2 as $i=>$n) {
					list($si, $sn) = explode('|',$n);
					if (empty($speakerlist)) {
						$speakerlist = $si;
					} else {
						$speakerlist .= ','.$si;
					}
				}
#debug($speakerlist,'$speakerlist');

				// Get list of all used time slots from sessions of the event
				// and subtract them from the slot list
				// Get list of time slots from speakers of the event 
				$tableName = 'tx_wseevents_sessions';
				$queryWhere = 'sys_language_uid=0 AND event='.$eventid;
				$groupBy = '';
				$orderBy = 'uid';
				$limit = '';
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'*',
					$tableName,
					$queryWhere,
					$groupBy,
					$orderBy,
					$limit);
				if ($res) {
					while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
						$usedslots = $row['timeslots'];
						if (!empty($usedslots)) {
							foreach ($slotlist as &$checkslot) {
								if (t3lib_div::inList($usedslots,$checkslot['uid'])) {
									$checkslot['uid'] = 0;
								}
							}
						}
						// Get speaker of session and check if sama as for actual session
						$usedspeaker = $row['speaker'];
						if (!empty($usedspeaker)) {
							$foundslot = false;
							$usedspeakerlist = explode(',',$usedspeaker);
							foreach ($usedspeakerlist as $checkspeaker) {
								if (t3lib_div::inList($speakerlist,$checkspeaker)) {
									if (!$foundslot) {
										$foundslot = true;
										if (empty($speakerslotlist)) {
											$speakerslotlist = $row['timeslots'];
										} else {
											$speakerslotlist .= ','.$row['timeslots'];
										}
									}
								}
							}
						}
					}
				}
				$speakerslotlist = t3lib_div::uniqueList($speakerslotlist);
#debug($speakerslotlist,'$speakerslotlist');
	
				// Subtract time slots of speakers of the event 
				// from the slot list if slot has same time
				// if speaker held a session at the same time in an other room

				// Get array with all slots of all rooms of all days
				$eventslotarray = tx_wseevents_events::getEventSlotArray($eventid);

				// Loop over all speaker slots
				$roomcount = count($eventslotarray['1']);
#debug($roomcount,'$roomcount');
				foreach (explode(',',$speakerslotlist) as $speakerslot){
					// Get slot record
					$slotrow = t3lib_BEfunc::getRecord ('tx_wseevents_timeslots', $speakerslot);
					for ( $s = 0; $s < $slotrow['length']; $s++ ) {
						for ($r = 1; $r <= $roomcount; $r++) {
							$eventslotarray[$slotrow['eventday']][$r][$slotrow['begin']+$s] = 0;
						}
					}
				}

				// Get list of all restrictions from speakers of the event
				// and subtract them from the slot list if speaker is not present at the time
				foreach (explode(',',$speakerlist) as $speaker) {
					$tableName = 'tx_wseevents_speakerrestrictions';
					$queryWhere = 'speaker='.$speaker.' AND event='.$eventid;
					$groupBy = '';
					$orderBy = 'uid';
					$limit = '';
					$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
						'*',
						$tableName,
						$queryWhere,
						$groupBy,
						$orderBy,
						$limit);
					if ($res) {
						while ($speakerrow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
							for ( $s = $speakerrow['begin']; $s <= $speakerrow['end']; $s++ ) {
								for ($r = 1; $r <= $roomcount; $r++) {
									$eventslotarray[$speakerrow['eventday']][$r][$s] = 0;
								}
							}
						}
					}
				}

				// Now subtract the not possible slots
				foreach ($slotlist as &$slot) {
					if ($slot['uid']>0) {
						// Get slot record
						$slotrow = t3lib_BEfunc::getRecord ('tx_wseevents_timeslots', $slot['uid']);
						for ( $s = 0; $s < $slotrow['length']; $s++ ) {
							if ($eventslotarray[$slotrow['eventday']][$slotrow['room']][$slotrow['begin']+$s] == 0) {
								$slot['uid'] = 0;
							}
						}
					}
				}
#debug($eventslotarray,'$eventslotarray');

				// Put remained slots in result array
				$thisslot = 1;
				foreach ($slotlist as $slot) {
					if ($slot['uid']>0) {
						// Add the name and id to the itemlist
						$entry = array();
						$entry[0] = $this->formatSlotName($slot);
						$entry[1] = $slot['uid'];
						$entry[2] = '';
						$PA['items'][] = $entry;
						$thisslot += 1;
					}
				}
				
			}
		}
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wse_events/class.tx_wseevents_timeslots.php']) {
	include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wse_events/class.tx_wseevents_timeslots.php']);
}

?>
