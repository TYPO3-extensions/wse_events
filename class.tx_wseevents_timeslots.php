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
 * Class cmp
 */
class cmp {
	var $key;
	function __construct($key) {
		$this->key = $key;
	}

	function cmp__($a,$b) {
		$key = $this->key;
		if ($a[$key] == $b[$key]) return 0;
		return (($a[$key] > $b[$key]) ? 1 : -1);
	}
}

/**
 * Class 'tx_wseevents_timeslots' for the 'wse_events' extension.
 *
 * @package		TYPO3
 * @subpackage	wse_events
 * @author		Michael Oehlhof <typo3@oehlhof.de>
 */
class tx_wseevents_timeslots implements t3lib_singleton {
	/** The extension key. */
	var $extKey = 'wseevents';

	/**
	 * Event class
	 * @var tx_wseevents_events
	 */
	var $events;

	/**
	 * List of all slots of an event
	 * @var array
	 */
	var $slotList;

	/**
	 * @var array
	 */
	var $sessionList;

	/**
	 * @var tslib_cObj
	 */
	var $cObj;

	/**
	 * @var array
	 */
	var $categories;

	/**
	 * Constructor: Create needed class.
	 *
	 * @return \tx_wseevents_timeslots
	 */
	function __construct() {
		// Initialize classes
		$this->events = t3lib_div::makeInstance('tx_wseevents_events');
		$this->cObj = t3lib_div::makeInstance('tslib_cObj');
		// Initialize variables
		$this->slotList = array();
		$this->sessionList = array();
		$this->categories = array();
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
		$eventData = $this->events->getEventInfo($row['event']);
		// Get localized name of weekday
		$thisDay = $eventData['begin'] + ($row['eventday'] - 1) * $secOfDay;
		$weekday = strftime('%A', $thisDay);
		// Get list of time slots in format hh:mm
		$eventSlots = $this->events->getEventSlotList($row['event']);
		// Get name of room
		$eventRooms = $this->events->getEventRooms($row['event']);
		// Compose name of time slot
		return	$weekday . ' ' . $eventSlots[$row['begin']] . ' ' . $eventRooms[$row['room']];
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
		$tableName = 'tx_wseevents_timeslots';
		$where = 'uid=' . $slotId .
			' AND ' . $TCA[$tableName]['ctrl']['languageField'] . '=0' .
			t3lib_BEfunc::versioningPlaceholderClause($tableName);
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid,event,eventday,room,begin,length', $tableName, $where);
		if ($res) {
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			$slotName = $this->formatSlotName($row);
			$GLOBALS['TYPO3_DB']->sql_free_result($res);
		}
		return $slotName;
	}

	/**
	 * Get id of record from time slot for given event, day, room and slot
	 *
	 * @param	integer		$event id of event
	 * @param	integer		$day number of the event day
	 * @param	integer		$room number of the event location room
	 * @param	integer		$slot number of time slot
	 * @param	integer		$showDbgSql flag to show debug output of SQL query
	 * @return	integer		id of slot if a slot is found
	 */
	function getSlot($event, $day, $room, $slot, $showDbgSql) {
		if (empty($this->slotList[$event])) {
			$where = 'event=' . $event . $this->cObj->enableFields('tx_wseevents_timeslots');
			if (1 == $showDbgSql) { echo 'getSlot where:' . $where . '<br>'; };
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid, pid, name, comment, length, eventday, room, begin', 'tx_wseevents_timeslots', $where);
			if ($res) {
				while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
					$this->slotList[$event][$row['eventday']][$row['room']][$row['begin']] = $row;
				}
				$GLOBALS['TYPO3_DB']->sql_free_result($res);
			}
		}
		if (isset($this->slotList[$event][$day][$room][$slot])) {
			return $this->slotList[$event][$day][$room][$slot]['uid'];
		} else {
			return 0;
		}
	}

	/**
	 * Get length of time slot for given uid
	 *
	 * @param	integer		$slot_id id of a tme slot
	 * @return	integer		length of a time slot
	 */
	function getSlotLength($slot_id) {
		foreach ($this->slotList as $event) {
			foreach ($event as $day) {
				foreach ($day as $room) {
					foreach ($room as $slot) {
						if ($slot['uid'] == $slot_id) {
							return $slot['length'];
						}
					}
				}
			}
		}
	}

	/**
	 * Get session data for given slot
	 *
	 * @param integer $event
	 * @param integer $slot_id id of a time slot
	 * @return array  array with record of session data
	 */
	function getSlotSession($event, $slot_id) {
		if (empty($this->sessionList[$event])) {
			$this->initSessionList($event);
		}
		if (empty($this->categories)) {
			$this->initCategories();
		}
		$session = array();
		foreach ($this->sessionList[$event] as $row) {
			if (t3lib_div::inList($row['timeslots'], $slot_id)) {
				$session = $row;
				$dataCat = $this->categories[$row['category']];
				$session['catnum'] = $dataCat['shortkey'] . sprintf ('%02d', $row['number']);
				$session['catkey'] = $dataCat['shortkey'];
				$session['catcolor'] = $dataCat['color'];
			}
		}
		return $session;
	}

	/**
	 * @param $categoryList
	 * @param string $sortBy
	 * @return array
	 */
	function getSelectedCategories($categoryList, $sortBy='uid') {
		if (empty($this->categories)) {
				$this->initCategories();
		}
		$categories = array();
		foreach ($categoryList as $category) {
			if (!empty($this->categories[$category])) {
				$categories[] = $this->categories[$category];
			}
		}
		usort($categories, array(new cmp($sortBy), "cmp__"));
		return $categories;
	}

	/**
	 * @param $category
	 */
	function getCategory($category) {
		if (empty($this->categories)) {
			$this->initCategories();
		}
		return $this->categories[$category];
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

		// Clear the item array
		$PA['items'] = array();

		$row = $this->events->getEventInfo($PA['row']['event'], $PA['row']['pid']);
		$eventId = $row['uid'];
		$location = $row['location'];

		if (($eventId>0) and ($location>0)) {
			// Get list of room ids and numbers
			$rooms = array();
			$rooms[0] = 0;
			$tableName = 'tx_wseevents_rooms';
			$queryWhere = 'location=' . $location .
				' AND ' . $TCA[$tableName]['ctrl']['languageField'] . '=0' .
				t3lib_BEfunc::BEenableFields($tableName) .
				t3lib_BEfunc::versioningPlaceholderClause($tableName);
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
			// Get list of all time slots for the event
			$tableName = 'tx_wseevents_timeslots';
			t3lib_div::loadTCA($tableName);
			$queryWhere = 'event='.$eventId.
				t3lib_BEfunc::BEenableFields($tableName).
				t3lib_BEfunc::versioningPlaceholderClause($tableName).
				t3lib_BEfunc::deleteClause($tableName);
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

				// Get list of speakers of the session
				$sp1 = $PA['row']['speaker'];
				$sp2 = explode(',', $sp1);
				$speakerList = '';
				$speakerSlotList = '';
				foreach($sp2 as $i=>$n) {
					list($si, $sn) = explode('|', $n);
					if (empty($speakerList)) {
						$speakerList = $si;
					} else {
						$speakerList .= ',' . $si;
					}
				}

				// Get list of all used time slots from sessions of the event
				// and subtract them from the slot list
				// Get list of time slots from speakers of the event
				$tableName = 'tx_wseevents_sessions';
				$queryWhere = 'event=' . $eventId .
					' AND ' . $TCA[$tableName]['ctrl']['languageField'] . '=0' .
					t3lib_BEfunc::versioningPlaceholderClause($tableName) .
					t3lib_BEfunc::deleteClause($tableName);
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

				// Subtract time slots of speakers of the event
				// from the slot list if slot has same time
				// if speaker held a session at the same time in an other room

				// Get array with all slots of all rooms of all days
				$eventSlotArray = $this->events->getEventSlotArray($eventId);

				// Loop over all speaker slots
				$roomCount = count($eventSlotArray['1']);
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
						}
					}
				}

				// Now subtract the not possible slots
				foreach ($slotList as &$slot) {
					if ($slot['uid']>0) {
						// Get slot record
						$slotRow = t3lib_BEfunc::getRecord('tx_wseevents_timeslots', $slot['uid']);
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

				// Put remained slots in result array
				$thisSlot = 1;
				foreach ($slotList as $oneSlot) {
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
			}
		}
	}

	/**
	 * @param $event
	 * @return void
	 */
	private function initSessionList($event) {
		$where = 'sys_language_uid=0 AND event=' . $event; // . $this->cObj->enableFields('tx_wseevents_sessions');
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tx_wseevents_sessions', $where);
		// We must iterate thru all sessions to find the appropriate time slot
		// because the time slots are stored as a list in a blob field
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			// Get overload workspace record
			$GLOBALS['TSFE']->sys_page->versionOL('tx_wseevents_sessions', &$row);
			// fix pid for record from workspace
			$GLOBALS['TSFE']->sys_page->fixVersioningPid('tx_wseevents_sessions', &$row);
			// Get overload language record
			if ($GLOBALS['TSFE']->sys_language_content) {
				$row = $GLOBALS['TSFE']->sys_page->getRecordOverlay('tx_wseevents_sessions',
					$row, $GLOBALS['TSFE']->sys_language_content,
					$GLOBALS['TSFE']->sys_language_contentOL, '');
			}
			// Check for enabled fields
			$ctrl = $GLOBALS['TCA']['tx_wseevents_sessions']['ctrl'];
			if (is_array($ctrl)) {
				if (($ctrl['delete']) AND (1 == $row[$ctrl['delete']])) {
					unset ($row);
				}
				if (is_array($ctrl['enablecolumns'])) {
					if (isset($row)) {
						if (($ctrl['enablecolumns']['disabled']) AND (1 == $row[$ctrl['enablecolumns']['disabled']])) {
							unset ($row);
						}
					}
				}
			}
			if (isset($row)) {
				$this->sessionList[$event][$row['uid']] = $row;
			}
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($res);
	}

	/**
	 * @return void
	 */
	private function initCategories()
	{
		$where = 'sys_language_uid=0' . $this->cObj->enableFields('tx_wseevents_categories');
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tx_wseevents_categories', $where);
		// We must iterate thru all sessions to find the appropriate time slot
		// because the time slots are stored as a list in a blob field
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			// Get overload workspace record
			$GLOBALS['TSFE']->sys_page->versionOL('tx_wseevents_categories', &$row);
			// fix pid for record from workspace
			$GLOBALS['TSFE']->sys_page->fixVersioningPid('tx_wseevents_categories', &$row);
			// Get overload language record
			if ($GLOBALS['TSFE']->sys_language_content) {
				$row = $GLOBALS['TSFE']->sys_page->getRecordOverlay('tx_wseevents_categories',
					$row, $GLOBALS['TSFE']->sys_language_content,
					$GLOBALS['TSFE']->sys_language_contentOL, '');
			}
			// Check for enabled fields
			$ctrl = $GLOBALS['TCA']['tx_wseevents_categories']['ctrl'];
			if (is_array($ctrl)) {
				if (($ctrl['delete']) AND (1 == $row[$ctrl['delete']])) {
					unset ($row);
				}
				if (is_array($ctrl['enablecolumns'])) {
					if (isset($row)) {
						if (($ctrl['enablecolumns']['disabled']) AND (1 == $row[$ctrl['enablecolumns']['disabled']])) {
							unset ($row);
						}
					}
				}
			}
			if (isset($row)) {
				$this->categories[$row['uid']] = $row;
			}
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($res);
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wse_events/class.tx_wseevents_timeslots.php']) {
	include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wse_events/class.tx_wseevents_timeslots.php']);
}
