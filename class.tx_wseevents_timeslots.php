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
 * Class 'tx_wseevents_timeslots' for the 'wse_events' extension.
 *
 * @package		TYPO3
 * @subpackage	wse_events
 * @author		Michael Oehlhof <typo3@oehlhof.de>
 */
class tx_wseevents_timeslots {
	/** The extension key. */
	var $extKey = 'wseevents';

	/**
	 * Dummy constructor: Does nothing.
	 *
	 * The base classe's constructor is called in $this->init().
	 *
	 * @return	void		...
	 */
	function tx_wseevents_timeslots() {
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
		return	$weekday.' '.$eventslots[$row['begin']].' '.$eventrooms[$row['room']];
	}

	/**
	 * Get the localized name of a time slot
	 *
	 * @param	integer		Id of slot
	 * @return	string		Localized name of time slot
	 * @access protected
	 */
	function getSlotName($slotid) {
		global $TCA;
		
		$slotname = '';
		$where = 'uid='.$slotid.
			' AND '.$TCA[$this->tableName]['ctrl']['languageField'].'=0'.
			t3lib_BEfunc::versioningPlaceholderClause($this->tableName);
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid,event,eventday,room,begin,length', 'tx_wseevents_timeslots', $where);
		if ($res) {
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			$slotname = formatSlotName($row);
		}
		return $slotname;
	}

	/**
	 * Get list of available time slots
	 *
	 * @param	array		TypoScript configuration for the plugin
	 * @return	void		...
	 * @access protected
	 */
	function getTCAavailableSlots($PA) {
		global $TCA;
#debug($PA);
		// Clear the item array
		$PA['items'] = array();

		$tableName = 'tx_wseevents_events';
		$eventid = $PA['row']['event'];
		// Get event record
		if ($eventid==0) {
			$queryWhere = 'pid='.$PA['row']['pid'];
		} else {
			$queryWhere = 'uid='.$eventid;
		}
		$queryWhere .= ' AND '.$TCA[$tableName]['ctrl']['languageField'].'=0'.
			t3lib_BEfunc::BEenableFields($tableName).
			t3lib_BEfunc::versioningPlaceholderClause($tableName);
#debug($queryWhere,'Nr 1');
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
			$location = $row['location'];
		}
		
		if ($eventid>0) {
			// Get list of room ids and numbers
			$rooms = array();
			$rooms[0] = 0;
			$tableName = 'tx_wseevents_rooms';
			$queryWhere = 'location='.$location.
				' AND '.$TCA[$tableName]['ctrl']['languageField'].'=0'.
				t3lib_BEfunc::BEenableFields($tableName).
				t3lib_BEfunc::versioningPlaceholderClause($tableName);
#debug($queryWhere,'Nr 2');
			$groupBy = '';
			$orderBy = 'number';
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
					$rooms[$row['uid']] = $row['number'];
				}
			}
#debug($rooms,'$rooms');			
			// Get list of all time slots for the event
			$tableName = 'tx_wseevents_timeslots';
			t3lib_div::loadTCA($tableName);
			$queryWhere = 'event='.$eventid.
				t3lib_BEfunc::BEenableFields($tableName).
				t3lib_BEfunc::versioningPlaceholderClause($tableName).
				t3lib_BEfunc::deleteClause($tableName);
#debug($queryWhere,'Nr 3');
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
#debug($slotlist,'$slotlist');			

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
				$queryWhere = 'event='.$eventid.
					' AND '.$TCA[$tableName]['ctrl']['languageField'].'=0'.
					t3lib_BEfunc::versioningPlaceholderClause($tableName).
					t3lib_BEfunc::deleteClause($tableName);
#debug($queryWhere,'Nr 4');
				$groupBy = '';
				$orderBy = 'uid';
				$limit = '';
				// Get list of time slots from speakers of the event
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
						// Get speaker of session and check if same as for actual session
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
				foreach (explode(',',$speakerslotlist) as $speakerslot) {
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
					if ($speaker) {
						$tableName = 'tx_wseevents_speakerrestrictions';
						$queryWhere = 'speaker='.$speaker.' AND event='.$eventid.
							t3lib_BEfunc::versioningPlaceholderClause($tableName).
							t3lib_BEfunc::deleteClause($tableName);
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
						} else {
#debug($queryWhere, '$queryWhere');
						}
					}
				}

#debug($slotlist,'$slotlist');
#debug($eventslotarray,'$eventslotarray');
				// Now subtract the not possible slots
				foreach ($slotlist as &$slot) {
					if ($slot['uid']>0) {
						// Get slot record
						$slotrow = t3lib_BEfunc::getRecord ('tx_wseevents_timeslots', $slot['uid']);
						for ( $s = 0; $s < $slotrow['length']; $s++ ) {
							if ($slotrow['room']<>0) {
								if ($eventslotarray[$slotrow['eventday']][$rooms[$slotrow['room']]][$slotrow['begin']+$s] == 0) {
									$slot['uid'] = 0;
								}
							} else {
								for ($r = 1; $r <= $roomcount; $r++) {
									if ($eventslotarray[$slotrow['eventday']][$r][$slotrow['begin']+$s] == 0) {
										$slot['uid'] = 0;
									}

								}
							}
						}
					}
				}

#debug($slotlist,'$slotlist');
				// Put remained slots in result array
				$thisslot = 1;
				foreach ($slotlist as $oneslot) {
#debug($oneslot,'$oneslot');
					if ($oneslot['uid']>0) {
						// Add the name and id to the itemlist
						$entry = array();
						$entry[0] = $this->formatSlotName($oneslot);
						$entry[1] = $oneslot['uid'];
						$entry[2] = '';
						$PA['items'][] = $entry;
						$thisslot += 1;
					}
				}
#debug($PA['items'],'$PA[items]');

			} else {
#debug($queryWhere, '$queryWhere');
			}
		}
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wse_events/class.tx_wseevents_timeslots.php']) {
	include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wse_events/class.tx_wseevents_timeslots.php']);
}

?>
