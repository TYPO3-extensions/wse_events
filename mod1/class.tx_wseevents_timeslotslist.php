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
/***************************************************************
*  Because I dont want to redefine the wheel again, some ideas
*  and code snippets are taken from the seminar manager extension
*  tx_seminars
***************************************************************/


/**
 * Class 'tx_wseevents_timeslotslist' for the 'wse_events' extension.
 *
 * @package		TYPO3
 * @subpackage	wse_events
 * @author		Michael Oehlhof <typo3@oehlhof.de>
 */
class tx_wseevents_timeslotslist extends tx_wseevents_backendlist{

	/**
	 * The constructor. Calls the constructor of the parent class and sets
	 * $this->tableName.
	 *
	 * @param	object		the current back-end page object
	 * @return	void		...
	 */
	function tx_wseevents_timeslotslist(&$page) {
		parent::tx_wseevents_backendlist($page);
		$this->tableName = $this->tableTimeslots;
#		$this->page = $page;
	}

	/**
	 * Generates and prints out a form for generating many time slot entries in one action.
	 *
	 * @return	string		the HTML source code of the form
	 * @access public
	 */
	function showCreateForm() {
		global $BACK_PATH, $TCA, $LANG, $BE_USER;

		$content = $LANG->getLL('showCreateForm') . '<br />';
		// Show name of event
		$eventid = t3lib_div::_GET('event');
		$event = tx_wseevents_events::getEventInfo($eventid);
		$content .= TAB . '<br /><span style="font-size:1.2em"><b>' . $LANG->getLL('event') . ' ' . $event['name'] . '</b></span><br /><br />' . LF;

		$defslotlen = $event['defslotcount'];
		$slotlist = tx_wseevents_events::getEventSlotList($eventid);
		$rooms = tx_wseevents_events::getEventRooms($eventid);
		$daycount = $event['length'];
		$roomcount = count($rooms);
		$slotsize = $defslotlen * $event['slotsize'];
		// Description for the columns, table header
		$content .= TAB . '<table border="1"><tr><td>' . $LANG->getLL('tscf_begin') . '</td><td>' . $LANG->getLL('tscf_len') . '</td>';
		$secofday = 60*60*24;
		for ( $d = 1; $d <= $daycount; $d++ ) {
			$thisday = $event['begin']+($d-1)*$secofday;
			$dayname[$d] = strftime('%Y-%m-%d', $thisday);
			$weekdays[$d] = strftime('%A', $thisday);
			$content .= '<td colspan="' . ($roomcount) . '">' . $weekdays[$d] . ' ' . $dayname[$d] . '</td>';
		}
		$content .= '</tr>' . LF;
		$content .= TAB . '<tr><td></td><td></td>';
		for ( $d = 1; $d <= $daycount; $d++ ) {
			foreach ( array_keys($rooms) as $roomid ) {
				$content .= '<td>' . $rooms[$roomid] . '</td>';
			}
		}
		$content .= '</tr>' . LF;

		// Input table
		$idx = 1; $x = 1;
		while ($idx < count($slotlist)) {
			// Input field for beginning of time slot
			$content .= TAB . '<tr><td><input name="slotbegin[' . $x . ']" type="text" size="5" maxlength="5" value="' . $slotlist[$idx] . '"></td>';
			// Input field for length of time slot
			$content .= '<td><input name="slotlen[' . $x . ']" type="text" size="2" maxlength="2" value="' . $slotsize . '"></td>';
			// Selectors for all rooms for all days
			for ( $d = 1; $d <= $daycount; $d++ ) {
//				$content .= '<td>Day ' . $d . '</td>';
				foreach ( array_keys($rooms) as $roomid ) {
					$checked = '';
					if (($roomid==0) and ($slotsize != $event['slotsize'])) {
						$checked = ' checked="checked"';
					}
					if (($roomid>0) and ($slotsize == $event['slotsize'])) {
						$checked = ' checked="checked"';
					}
					$content .= '<td><input type="checkbox" name="room_' . $d . '_' . $roomid . '[' . $x . ']" value="yes"' . $checked . '></td>';
				}
			}
			// End of input forone time slot
			$content .= '</tr>' . LF;
			// Alternate the time slot length between minimum and default time slot size
			if (($x % 2) == 0) {
				$idx++;
				$slotsize = $defslotlen * $event['slotsize'];
			} else {
				$idx += $defslotlen;
				$slotsize = $event['slotsize'];
			}
			$x++;
		}

//		for ($x = 1; $x < 15; $x++ ) {
//			$content .= TAB . '<input name="slotbegin[' . $x . ']" type="text" size="5" maxlength="5"><br />' . LF;
//		}
		$content .= TAB . '</table>' . LF;
		$content .= TAB . '<br /><input type="submit" name="_CREATE" value=" ' . $LANG->getLL('createSlots') . ' "><br />' . LF;

//$content .= '<br />showCreateForm<br />';
		// Finished creating the form
		return $content;
	}

	/**
	 * Creates many time slot entries in one action.
	 *
	 * @return	string		the HTML source code of the result
	 * @access public
	 */
	function createSlots() {
		global $BACK_PATH, $TCA, $LANG, $BE_USER;

		$content = $LANG->getLL('slotCreated') . '<br />' . LF;
		// Show name of event
		$eventid = t3lib_div::_GET('event');
		$event = tx_wseevents_events::getEventInfo($eventid);
		$content .= TAB . '<br /><span style="font-size:1.2em"><b>' . $LANG->getLL('event') . ' ' . $event['name'] . '</b></span><br /><br />' . LF;

if (1==0) {
$content .= '---POST begin ---' . '<br />' . LF;
$content .= t3lib_utility_Debug::view_array(t3lib_div::_POST()) . '<br />' . LF;
$content .= '---POST end ---' . '<br />' . LF;
}
		$slotlist = tx_wseevents_events::getEventSlotList($eventid);
		$slotbegin = t3lib_div::_POST('slotbegin');
		$slotlen = t3lib_div::_POST('slotlen');
		$rooms = tx_wseevents_events::getEventRooms($eventid);
		$daycount = $event['length'];
		$secofday = 60*60*24;
		for ( $d = 1; $d <= $daycount; $d++ ) {
			$thisday = $event['begin']+($d-1)*$secofday;
			$dayname[$d] = strftime('%Y-%m-%d', $thisday);
			$weekdays[$d] = strftime('%A', $thisday);
		}

		$idx = 1;
		$comment = $LANG->getLL('slotCreatedComment');
		while ($idx < count($slotlist)) {
			if (!empty($slotbegin[$idx])) {
				for ( $d = 1; $d <= $daycount; $d++ ) {
					foreach ( array_keys($rooms) as $roomid ) {
						$cbx = t3lib_div::_POST('room_' . $d . '_' . $roomid);
						if ($cbx[$idx]) {
							// Create timeslot record
							$insertArray = array (
								'event' => $eventid,
								'pid' => $event['pid'],
								'eventday' => $d,
								'room' => $roomid,
								'begin' => array_search($slotbegin[$idx], $slotlist),
								'length' => intval($slotlen[$idx] / $event['slotsize']),
								'name' => $weekdays[$d] . ' ' . $slotbegin[$idx] . ' ' . $rooms[$roomid],
								'comment' => $comment,
							);

							$query  = $GLOBALS['TYPO3_DB']->exec_INSERTquery($this->tableName, $insertArray);
							$lastID = $GLOBALS['TYPO3_DB']->sql_insert_id();
							// Print out info
							$content .= TAB . '[' . $lastID . '] ' . $slotbegin[$idx] . ', ' . $slotlen[$idx] . ', ' . $weekdays[$d] . ' ' . $dayname[$d] . ', ' . $rooms[$roomid] . '<br />' . LF;
						}
					}
				}
				$content .= '<hr>' . LF;
			}
			$idx += 1;
		}

//$content .= '<br />createForm<br />';
		// Finished creating the form
		return $content;
	}


	/**
	 * Update the name of all time slot entries in one action.
	 *
	 * @return	string		the HTML source code of the result
	 * @access public
	 */
	function updateSlots() {
		global $BACK_PATH, $TCA, $LANG, $BE_USER;

		$content = $LANG->getLL('slotUpdate') . '<br />' . LF;
		// Show name of event
		$eventid = t3lib_div::_GET('event');
		$event = tx_wseevents_events::getEventInfo($eventid);

		// Show name of event
		$content .= LF . TAB . '<br /><span style="font-size:1.2em"><b>' . $LANG->getLL('event') . ' ' . $event['name'] . '</b></span><br />';

		// Get list of all possible timeslots for the event
		$slots = tx_wseevents_events::getEventSlotList($event['uid']);

		// Get info about event
		$eventinfo = tx_wseevents_events::getEventInfo($event['uid']);

		// -------------------- Get list of rooms --------------------
		// Initialize variables for the database query.
		$queryWhere = 'location=' . $event['location']
			. ' AND ' . $TCA[$this->tableRooms]['ctrl']['languageField'] . '=0'
			. t3lib_BEfunc::versioningPlaceholderClause($this->tableRooms)
			. t3lib_BEfunc::deleteClause($this->tableRooms);
		$additionalTables = '';
		$groupBy = '';
		$orderBy = 'number';
		$limit = '';

		// Get list of all rooms for the location of the event
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'*',
			$this->tableRooms,
			$queryWhere,
			$groupBy,
			$orderBy,
			$limit);

		// Initialize variables for the database query.
		$queryWhere = 'event=' . $event['uid']
			. t3lib_BEfunc::deleteClause($this->tableName)
			. t3lib_BEfunc::versioningPlaceholderClause($this->tableName);
		$additionalTables = '';
		$groupBy = '';
		$orderBy = 'eventday,begin,room';
		$limit = '';

		// Get list of all used time slots
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'*',
			$this->tableName,
			$queryWhere,
			$groupBy,
			$orderBy,
			$limit);

		if ($res) {
			$slotlist = tx_wseevents_events::getEventSlotList($eventid);
			$rooms = tx_wseevents_events::getEventRooms($event['uid']);
			$daycount = $event['length'];
			$secofday = 60*60*24;
			for ( $d = 1; $d <= $daycount; $d++ ) {
				$thisday = $event['begin']+($d-1)*$secofday;
//				$dayname[$d] = strftime('%Y-%m-%d', $thisday);
				$weekdays[$d] = strftime('%A', $thisday);
			}

			$found = false;
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$found = true;
				$uid = $row['uid'];
				$hidden = $row['hidden'];
				// Add the result row to the table array.
				$updateArray = array (
					'name' => $weekdays[$row['eventday']] . ' ' . $slotlist[$row['begin']] . ' ' . $rooms[$row['room']],
				);
				// Print out info
				$newname = '[' . $uid . '] ' . $weekdays[$row['eventday']] . ' ' . $slotlist[$row['begin']] . ' ' . $rooms[$row['room']];
				$content .= $newname . '<br />' . LF;
				$query  = $GLOBALS['TYPO3_DB']->exec_UPDATEquery($this->tableName, 'uid=' . $uid, $updateArray);
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($res);
			$content .= LF;
		}
		// Finished creating the form
		return $content;
	}


	/**
	 * Generates and prints out an time slot list.
	 *
	 * @return	string		the HTML source code of the event list
	 * @access public
	 */
	function show() {
		global $BACK_PATH, $TCA, $LANG, $BE_USER;

		// Define initial comment
		$initcomment = LF . TAB . '<!-- WSE_EVENTS time slot list -->' . LF;

		// Check for additonal parameters
		$action = t3lib_div::_GET('action');
		if ($action=='slotCreateForm') {
			if (t3lib_div::_POST('_CREATE')) {
				return $initcomment . $this->createSlots();
			} else {
				return $initcomment . $this->showCreateForm();
			}
		}
		if ($action=='slotCreate') {
			return $initcomment . $this->createSlots();
		}
		if ($action=='slotUpdate') {
			return $initcomment . $this->updateSlots();
		}

		// Initialize the variable for the HTML source code.
		$content = $initcomment;

		// Set the table layout of the time slot list.
		$tableLayout = array(
			'table' => array(
				TAB . '<table cellpadding="0" cellspacing="0" class="typo3-dblist">' . LF,
				TAB . '</table>' . LF
			),
			array(
				'tr' => array(
					TAB . TAB . '<thead>' . LF
						. TAB . TAB . TAB . '<tr>' . LF,
					TAB . TAB . TAB . '</tr>' . LF
						. TAB . TAB . '</thead>' . LF
				),
				'defCol' => array(
					TAB . TAB . TAB . TAB . '<td class="c-headLineTable">' . LF,
					TAB . TAB . TAB . TAB . '</td>' . LF
				)
			),
			'defRow' => array(
				'tr' => array(
					TAB . TAB . '<tr>' . LF,
					TAB . TAB . '</tr>' . LF
				),
				array(
					TAB . TAB . TAB . '<td>' . LF,
					TAB . TAB . TAB . '</td>' . LF
				),
				array(
					TAB . TAB . TAB . '<td>' . LF,
					TAB . TAB . TAB . '</td>' . LF
				),
				array(
					TAB . TAB . TAB . '<td>' . LF,
					TAB . TAB . TAB . '</td>' . LF
				),
				'defCol' => array(
					TAB . TAB . TAB . '<td>' . LF,
					TAB . TAB . TAB . '</td>' . LF
				)
			)
		);

		// Fill the first row of the table array with the header.
		$tableheader = array(
			array(
				TAB . TAB . TAB . TAB . TAB
					. '<span style="color: #ffffff; font-weight: bold;">'
					. $LANG->getLL('timeslots.name') . '</span>' . LF,
				TAB . TAB . TAB . TAB . TAB
					. '<span style="color: #ffffff; font-weight: bold;">'
					. $LANG->getLL('timeslots.eventday') . '</span>' . LF,
				TAB . TAB . TAB . TAB . TAB
					. '<span style="color: #ffffff; font-weight: bold;">'
					. $LANG->getLL('timeslots.room') . '</span>' . LF,
				TAB . TAB . TAB . TAB . TAB
					. '<span style="color: #ffffff; font-weight: bold;">'
					. $LANG->getLL('timeslots.begin') . '</span>' . LF,
				TAB . TAB . TAB . TAB . TAB
					. '<span style="color: #ffffff; font-weight: bold;">'
					. $LANG->getLL('timeslots.length') . '</span>' . LF,
				TAB . TAB . TAB . TAB . TAB
					. '<span style="color: #ffffff; font-weight: bold;">'
					. $LANG->getLL('timeslots.id') . '</span>' . LF,
				'',
			)
		);

		// unserialize the configuration array
		$globalConfiguration = unserialize(
			$GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['wse_events']
		);

		# Get date format for selected language
		if (!$conf[$index . '.']['fmtDate']){
			$conf['strftime'] = '%d.%m.%Y';
		} else {
			$conf['strftime'] = $conf[$index . '.']['fmtDate'];
		}

		// Get list of pid
		$this->selectedPids = $this->getRecursiveUidList($this->page->pageInfo['uid'],2);
		// Check if sub pages available and remove main page from list
		if ($this->selectedPids<>$this->page->pageInfo['uid']) {
			$this->selectedPids = t3lib_div::rmFromList($this->page->pageInfo['uid'],$this->selectedPids);
		}
		// Remove duplicate entries
		$this->selectedPids = t3lib_div::uniqueList($this->selectedPids);
		// Remove pages with common data
		$eventPids = $this->removeCommonPages($this->selectedPids);
		// If all in one page than use page id
		if (empty($eventPids)) {
			$eventPids = $this->page->pageInfo['uid'];
		}
		// Get page titles
		$this->selectedPidsTitle = $this->getPidTitleList($this->selectedPids);
		// Get the where clause
		$wherePid = 'pid IN (' . $GLOBALS['TYPO3_DB']->cleanIntList($this->selectedPids) . ')';

		// Add icon for new record
		if (!empty($eventPids)) {
			$content .= $this->getNewIconList($eventPids,$this->selectedPidsTitle);
		}


		if (!isset($this->page->pageInfo['uid'])) {
			return;
		}

		// -------------------- Get list of events --------------------
		// Initialize variables for the database query.
		$queryWhere = $wherePid . t3lib_BEfunc::deleteClause($this->tableEvents)
			. ' AND ' . $TCA[$this->tableEvents]['ctrl']['languageField'] . '=0'
			. t3lib_BEfunc::versioningPlaceholderClause($this->tableEvents);
		$additionalTables = '';
		$groupBy = '';
		$orderBy = 'name';
		$limit = '';

		// Get list of all events
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'*',
			$this->tableEvents,
			$queryWhere,
			$groupBy,
			$orderBy,
			$limit);

		$events = array();
		if ($res) {
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$event = array();
				$event['uid'] = $row['uid'];
				$event['pid'] = $row['pid'];
				$event['name'] = $row['name'];
				$event['location'] = $row['location'];
				$events[] = $event;
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($res);
		}

		// ToDo: Add box for event selection



		// Get list of time slots for an event
		foreach ($events as $event) {
			// Show name of event
			$content .= LF . TAB . '<br /><span style="font-size:1.2em"><b>' . $LANG->getLL('event') . ' ' . $event['name'] . '</b></span>';
//			$content .= '&nbsp;' . $this->getNewIcon($event['pid'],0) . '<br />';

			// Get list of all possible timeslots for the event
			$slots = tx_wseevents_events::getEventSlotList($event['uid']);

			// Get info about event
			$eventinfo = tx_wseevents_events::getEventInfo($event['uid']);

			// -------------------- Get list of rooms --------------------
			// Initialize variables for the database query.
			$queryWhere = $wherePid . t3lib_BEfunc::deleteClause($this->tableRooms)
				. ' AND location=' . $event['location']
				. ' AND ' . $TCA[$this->tableRooms]['ctrl']['languageField'] . '=0'
				. t3lib_BEfunc::versioningPlaceholderClause($this->tableRooms);
			$additionalTables = '';
			$groupBy = '';
			$orderBy = 'number';
			$limit = '';

			// Get list of all rooms for the location of the event
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'*',
				$this->tableRooms,
				$queryWhere,
				$groupBy,
				$orderBy,
				$limit);

/*
			$rooms = array();
			$rooms[0] = $LANG->getLL('timeslots.allrooms');
			if ($res) {
				while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
					$rooms[$row['uid']] = $row['name'];
				}
				$GLOBALS['TYPO3_DB']->sql_free_result($res);
			}
*/
			$rooms = tx_wseevents_events::getEventRooms($event['uid']);

			// Initialize variables for the database query.
			$queryWhere = $wherePid . ' AND event=' . $event['uid']
				. t3lib_BEfunc::deleteClause($this->tableName)
				. t3lib_BEfunc::versioningPlaceholderClause($this->tableName);
			$additionalTables = '';
			$groupBy = '';
			$orderBy = 'eventday,begin,room';
			$limit = '';

			// Get list of all used time slots
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'*',
				$this->tableName,
				$queryWhere,
				$groupBy,
				$orderBy,
				$limit);

			// Clear output table
			$table = $tableheader;

			if ($res) {
				$found = false;
				while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
					$found = true;
					$uid = $row['uid'];
					$hidden = $row['hidden'];
					// Add the result row to the table array.
					$table[] = array(
						TAB . TAB . TAB . TAB
							. t3lib_div::fixed_lgd_cs(
								$row['name'],
								$BE_USER->uc['titleLen']
							) . LF,
						TAB . TAB . TAB . TAB
							. $row['eventday'] . LF,
						TAB . TAB . TAB . TAB
							. $rooms[$row['room']] . LF,
						TAB . TAB . TAB . TAB
							. $slots[$row['begin']] . LF,
						TAB . TAB . TAB . TAB
							. $eventinfo['slotsize']*$row['length'] . LF,
						TAB . TAB . TAB . TAB
							. $row['uid'] . LF,
						TAB . TAB . TAB . TAB
							. $this->getEditIcon($uid) . LF
						. TAB . TAB . TAB . TAB
							. $this->getDeleteIcon($uid) . LF
						. TAB . TAB . TAB . TAB
							. $this->getHideUnhideIcon(
								$uid,
								$hidden
							) . LF,
					);
				}
				$GLOBALS['TYPO3_DB']->sql_free_result($res);
				$content .= LF;

				// Show no records message
				if (!$found) {
					$content .= TAB . '<br />' . $LANG->getLL('norecords') . '<br />' . LF;

					// Show button for creating many slots together
					$params = '&action=slotCreateForm&event=' . $event['uid'];
					$newOnClick = t3lib_div::getIndpEnv('REQUEST_URI') . $params;
					$langNew = $LANG->getLL('slotCreate');
					$content .= TAB . '<div id="typo3-newRecordLink">' . LF;
					$content .= TAB . TAB . '<a href="' . $newOnClick . '">'
						. '<img '
						. t3lib_iconWorks::skinImg(
							$BACK_PATH,
							'gfx/new_record.gif',
							'width="11" height="12"')
						. ' title="' . $langNew . '" alt="' . $langNew . '" class="icon" />'
						. $langNew
						. '</a>' . LF;
					$content .= TAB . '</div><br />' . LF;
				}

				// Show table with all defined time slots
				if ($found) {
					// Output the table array using the tableLayout array with the template
					// class.
					$content .= $this->page->doc->table($table, $tableLayout) . TAB . '<br />' . LF;

					// Show button for updating name of all slots together
					$params = '&action=slotUpdate&event=' . $event['uid'];
					$newOnClick = t3lib_div::getIndpEnv('REQUEST_URI') . $params;
					$langNew = $LANG->getLL('updateSlots');
					$content .= TAB . '<div id="typo3-newRecordLink">' . LF;
					$content .= TAB . TAB . '<a href="' . $newOnClick . '">'
						. '<img '
						. t3lib_iconWorks::skinImg(
							$BACK_PATH,
							'gfx/synchronize_el.gif',
							'width="16" height="16"')
						. ' title="' . $langNew . '" alt="' . $langNew . '" class="icon" />'
						. $langNew
						. '</a>' . LF;
					$content .= TAB . '</div><br />' . LF;

				}
			}
		}

		return $content;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wse_events/mod1/class.tx_wseevents_timeslotslist.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wse_events/mod1/class.tx_wseevents_timeslotslist.php']);
}

?>
