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
	 * @param	array		$row Record data of slot
	 * @return	string		Localized name of time slot
	 * @access protected
	 */
	function formatSlotName($row) {
		if (empty($row['event'])) {
			return '';
		}
		$secOfDay = 60*60*24;
		// Get record with event data
		$eventData = tx_wseevents_events::getEventInfo($row['event']);
		// Get localized name of weekday
		$thisDay = $eventData['begin']+($row['eventday']-1)*$secOfDay;
		$weekday = strftime('%A', $thisDay);
		// Get list of time slots in format hh:mm
		$eventSlots = tx_wseevents_events::getEventSlotList($row['event']);
		// Get name of room
		$eventRooms = tx_wseevents_events::getEventRooms($row['event']);
		// Compose name of time slot
		return	$weekday.' '.$eventSlots[$row['begin']].' '.$eventRooms[$row['room']];
	}

	/**
	 * Get the localized name of a time slot
	 *
	 * @param	integer		$slotId Id of slot
	 * @return	string		Localized name of time slot
	 * @access protected
	 */
	function getSlotName($slotId) {
		global $TCA;

		$slotName = '';
		$where = 'uid='.$slotId.
			' AND '.$TCA[$this->tableName]['ctrl']['languageField'].'=0'.
			t3lib_BEfunc::versioningPlaceholderClause($this->tableName);
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid,event,eventday,room,begin,length', 'tx_wseevents_timeslots', $where);
		if ($res) {
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			$slotName = formatSlotName($row);
			$GLOBALS['TYPO3_DB']->sql_free_result($res);
		}
		return $slotName;
	}

	/**
	 * Get list of available time slots
	 *
	 * @param	array		$PA TypoScript configuration for the plugin
	 * @return	void		...
	 * @access protected
	 */
	function getTCAavailableSlots($PA) {
		global $TCA;
#debug($PA);
		// Clear the item array
		$PA['items'] = array();

		$tableName = 'tx_wseevents_events';
		$eventId = $PA['row']['event'];
		// Get event record
		if ($eventId==0) {
			$queryWhere = 'pid='.$PA['row']['pid'];
		} else {
			$queryWhere = 'uid='.$eventId;
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
			$eventId = $row['uid'];
			$location = $row['location'];
			$GLOBALS['TYPO3_DB']->sql_free_result($res);
		}

		if ($eventId>0) {
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
				$GLOBALS['TYPO3_DB']->sql_free_result($res);
			}
#debug($rooms,'$rooms');
			// Get list of all time slots for the event
			$tableName = 'tx_wseevents_timeslots';
			t3lib_div::loadTCA($tableName);
			$queryWhere = 'event='.$eventId.
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
				$slotList = array();
				while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
					$slotList[] = $row;
				}
				$GLOBALS['TYPO3_DB']->sql_free_result($res);
#debug($slotlist,'$slotlist');

				// Get list of speakers of the session
				$sp1 = $PA['row']['speaker'];
				$sp2 = array();
				$sp2 = explode(',',$sp1);
				$speakerList = '';
				$speakerSlotList = '';
				foreach($sp2 as $i=>$n) {
					list($si, $sn) = explode('|',$n);
					if (empty($speakerList)) {
						$speakerList = $si;
					} else {
						$speakerList .= ','.$si;
					}
				}
#debug($speakerlist,'$speakerlist');

				// Get list of all used time slots from sessions of the event
				// and subtract them from the slot list
				// Get list of time slots from speakers of the event
				$tableName = 'tx_wseevents_sessions';
				$queryWhere = 'event='.$eventId.
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
						$usedSlots = $row['timeslots'];
						if (!empty($usedSlots)) {
							foreach ($slotList as &$checkSlot) {
								if (t3lib_div::inList($usedSlots,$checkSlot['uid'])) {
									$checkSlot['uid'] = 0;
								}
							}
						}
						// Get speaker of session and check if same as for actual session
						$usedSpeaker = $row['speaker'];
						if (!empty($usedSpeaker)) {
							$foundSlot = false;
							$usedSpeakerList = explode(',',$usedSpeaker);
							foreach ($usedSpeakerList as $checkSpeaker) {
								if (t3lib_div::inList($speakerList,$checkSpeaker)) {
									if (!$foundSlot) {
										$foundSlot = true;
										if (empty($speakerSlotList)) {
											$speakerSlotList = $row['timeslots'];
										} else {
											$speakerSlotList .= ','.$row['timeslots'];
										}
									}
								}
							}
						}
					}
					$GLOBALS['TYPO3_DB']->sql_free_result($res);
				}
				$speakerSlotList = t3lib_div::uniqueList($speakerSlotList);
#debug($speakerslotlist,'$speakerslotlist');

				// Subtract time slots of speakers of the event
				// from the slot list if slot has same time
				// if speaker held a session at the same time in an other room

				// Get array with all slots of all rooms of all days
				$eventSlotArray = tx_wseevents_events::getEventSlotArray($eventId);

				// Loop over all speaker slots
				$roomCount = count($eventSlotArray['1']);
#debug($roomcount,'$roomcount');
				foreach (explode(',',$speakerSlotList) as $speakerSlot) {
					// Get slot record
					$slotRow = t3lib_BEfunc::getRecord ('tx_wseevents_timeslots', $speakerSlot);
					for ( $s = 0; $s < $slotRow['length']; $s++ ) {
						for ($r = 1; $r <= $roomCount; $r++) {
							$eventSlotArray[$slotRow['eventday']][$r][$slotRow['begin']+$s] = 0;
						}
					}
				}

				// Get list of all restrictions from speakers of the event
				// and subtract them from the slot list if speaker is not present at the time
				foreach (explode(',',$speakerList) as $speaker) {
					if ($speaker) {
						$tableName = 'tx_wseevents_speakerrestrictions';
						$queryWhere = 'speaker='.$speaker.' AND event='.$eventId.
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
							while ($speakerRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
								for ( $s = $speakerRow['begin']; $s <= $speakerRow['end']; $s++ ) {
									for ($r = 1; $r <= $roomCount; $r++) {
										$eventSlotArray[$speakerRow['eventday']][$r][$s] = 0;
									}
								}
							}
							$GLOBALS['TYPO3_DB']->sql_free_result($res);
						} else {
#debug($queryWhere, '$queryWhere');
						}
					}
				}

#debug($slotlist,'$slotlist');
#debug($eventslotarray,'$eventslotarray');
				// Now subtract the not possible slots
				foreach ($slotList as &$slot) {
					if ($slot['uid']>0) {
						// Get slot record
						$slotRow = t3lib_BEfunc::getRecord ('tx_wseevents_timeslots', $slot['uid']);
						for ( $s = 0; $s < $slotRow['length']; $s++ ) {
							if ($slotRow['room']<>0) {
								if ($eventSlotArray[$slotRow['eventday']][$rooms[$slotRow['room']]][$slotRow['begin']+$s] == 0) {
									$slot['uid'] = 0;
								}
							} else {
								for ($r = 1; $r <= $roomCount; $r++) {
									if ($eventSlotArray[$slotRow['eventday']][$r][$slotRow['begin']+$s] == 0) {
										$slot['uid'] = 0;
									}

								}
							}
						}
					}
				}

#debug($slotlist,'$slotlist');
				// Put remained slots in result array
				$thisSlot = 1;
				foreach ($slotList as $oneSlot) {
#debug($oneslot,'$oneslot');
					if ($oneSlot['uid']>0) {
						// Add the name and id to the itemlist
						$entry = array();
						$entry[0] = $this->formatSlotName($oneSlot);
						$entry[1] = $oneSlot['uid'];
						$entry[2] = '';
						$PA['items'][] = $entry;
						$thisSlot += 1;
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
