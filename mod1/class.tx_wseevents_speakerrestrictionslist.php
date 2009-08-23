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

require_once('conf.php');
require_once($BACK_PATH . 'init.php');
require_once($BACK_PATH . 'template.php');
require_once(t3lib_extMgm::extPath('wse_events') . 'mod1/class.tx_wseevents_backendlist.php');
require_once(t3lib_extMgm::extPath('wse_events') . 'class.tx_wseevents_events.php');


/**
 * Class 'tx_wseevents_speakerrestrictionslist' for the 'wse_events' extension.
 *
 * @package		TYPO3
 * @subpackage	wse_events
 * @author		Michael Oehlhof <typo3@oehlhof.de>
 */
class tx_wseevents_speakerrestrictionslist extends tx_wseevents_backendlist{

	/**
	 * The constructor. Calls the constructor of the parent class and sets
	 * $this->tableName.
	 *
	 * @param	object		the current back-end page object
	 * @return	void		...
	 */
	function tx_wseevents_speakerrestrictionslist(&$page) {
		parent::tx_wseevents_backendlist($page);
		$this->tableName = $this->tableSpeakerRestrictions;
#		$this->page = $page;
	}

	/**
	 * Generates and prints out an event list.
	 *
	 * @return	string		the HTML source code of the event list
	 * @access public
	 */
	function show() {
		global $TCA, $LANG, $BE_USER;

#debug ($LANG);
#debug ($BE_USER);

		// Get selected backend language of user
		$userlang = $BE_USER->uc[moduleData][web_layout][language];

		// Loading all TCA details for this table:
		t3lib_div::loadTCA($this->tableSpeakers);
		t3lib_div::loadTCA($this->tableEvents);
		t3lib_div::loadTCA($this->tableName);



		// Initialize the variable for the HTML source code.
		$content = '';

		// Set the table layout of the speaker restrictions list.
		$tableLayout = array(
			'table' => array(
				TAB . TAB . '<table cellpadding="0" cellspacing="0" class="typo3-dblist">' . LF,
				TAB . TAB . '</table>' . LF
			),
			array(
				'tr' => array(
					TAB . TAB . TAB . '<thead>' . LF
						. TAB . TAB . TAB . TAB . '<tr>' . LF,
					TAB . TAB . TAB . TAB . '</tr>' . LF
						. TAB . TAB . TAB . '</thead>' . LF
				),
				'defCol' => array(
					TAB . TAB . TAB . TAB . TAB . '<td class="c-headLineTable">' . LF,
					TAB . TAB . TAB . TAB . TAB . '</td>' . LF
				)
			),
			'defRow' => array(
				'tr' => array(
					TAB . TAB . TAB . '<tr>' . LF,
					TAB . TAB . TAB . '</tr>' . LF
				),
				array(
					TAB . TAB . TAB . TAB . '<td>' . LF,
					TAB . TAB . TAB . TAB . '</td>' . LF
				),
				array(
					TAB . TAB . TAB . TAB . '<td>' . LF,
					TAB . TAB . TAB . TAB . '</td>' . LF
				),
				array(
					TAB . TAB . TAB . TAB . '<td>' . LF,
					TAB . TAB . TAB . TAB . '</td>' . LF
				),
				'defCol' => array(
					TAB . TAB . TAB . TAB . '<td>' . LF,
					TAB . TAB . TAB . TAB . '</td>' . LF
				)
			)
		);

		// Fill the first row of the table array with the header.
		$table = array(
			array(
				TAB . TAB . TAB . TAB . TAB . TAB
					. '<span style="color: #ffffff; font-weight: bold;">'
					. $LANG->getLL('speakers.speaker') . '</span>' . LF,
				TAB . TAB . TAB . TAB . TAB . TAB
					. '<span style="color: #ffffff; font-weight: bold;">'
					. $LANG->getLL('speakers.eventday') . '</span>' . LF,
				TAB . TAB . TAB . TAB . TAB . TAB
					. '<span style="color: #ffffff; font-weight: bold;">'
					. $LANG->getLL('speakers.begin') . '</span>' . LF,
				TAB . TAB . TAB . TAB . TAB . TAB
					. '<span style="color: #ffffff; font-weight: bold;">'
					. $LANG->getLL('speakers.end') . '</span>' . LF,
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
		$secofday = 60*60*24;

		// Get list of pid
		$this->selectedPids = $this->getRecursiveUidList($this->page->pageInfo['uid'],2);
		// Check if sub pages available and remove main page from list
		if ($this->selectedPids<>$this->page->pageInfo['uid']) {
			$this->selectedPids = t3lib_div::rmFromList($this->page->pageInfo['uid'],$this->selectedPids);
		} else {
			// if no sub pages, get one level up
			if ($this->page->pageInfo['pid']<>0) {
				$this->selectedPids = $this->getRecursiveUidList($this->page->pageInfo['pid'],2);
				// remove up level page
				$this->selectedPids = t3lib_div::rmFromList($this->page->pageInfo['pid'],$this->selectedPids);
				// remove other event pages
				$this->selectedPids = $this->removeEventPages($this->selectedPids);
				// add this page to the list
				$this->selectedPids .= $this->selectedPids?',' . $this->page->pageInfo['uid']:$this->page->pageInfo['uid'];
			}
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

		// -------------------- Get list of speakers --------------------
		// Initialize variables for the database query.
		$queryWhere = $wherePid . t3lib_BEfunc::deleteClause($this->tableSpeakers)
			. ' AND ' . $TCA[$this->tableSpeakers]['ctrl']['languageField'] . '=0'
			. t3lib_BEfunc::versioningPlaceholderClause($this->tableSpeakers);
		$additionalTables = '';
		$groupBy = '';
		$orderBy = 'uid';
		$limit = '';

		// Get list of all events
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'*',
			$this->tableSpeakers,
			$queryWhere,
			$groupBy,
			$orderBy,
			$limit);

		$speakers = array();
		if ($res) {
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$speakers[$row['uid']] = $row['name'] . ', ' . $row['firstname'];
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($res);
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
				$event['name'] = $row['name'];
				$event['begin'] = $row['begin'];
				$events[] = $event;
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($res);
		}

		// Add box for event selection


		// Get list of sessions for an event
		foreach ($events as $event) {
			// Show name of event
			$content .= '<span style="font-size:1.2em"><b>' . $LANG->getLL('event') . ' ' . $event['name'] . '</b></span>';
//			$content .= '&nbsp;' . $this->getNewIcon($event['pid'],0) . '<br />';

			// Get list of timeslots for the event
			$slots = tx_wseevents_events::getEventSlotlist($event['uid']);

			// Initialize variables for the database query.
			$queryWhere = $wherePid . ' AND event='.$event['uid']
				. t3lib_BEfunc::deleteClause($this->tableName)
				. t3lib_BEfunc::versioningPlaceholderClause($this->tableName);
			$additionalTables = '';
			$groupBy = '';
			$orderBy = 'speaker,eventday';
			$limit = '';

			// Get list of all speaker restrictions
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'*',
				$this->tableName,
				$queryWhere,
				$groupBy,
				$orderBy,
				$limit);

			if ($res) {
				$found = false;
				while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
					$found = true;
					$uid = $row['uid'];
					$hidden = $row['hidden'];
					$eventday = $row['eventday'];
					// Format begin day of event
					$eventdate = strftime($conf['strftime'], ($event['begin']+($eventday-1)*$secofday));
					// Add the result row to the table array.
					$table[] = array(
						TAB . TAB . TAB . TAB . TAB
							. $speakers[$row['speaker']] . LF,
						TAB . TAB . TAB . TAB . TAB
							. $eventdate . LF,
						TAB . TAB . TAB . TAB . TAB
							. $slots[$row['begin']] . LF,
						TAB . TAB . TAB . TAB . TAB
							. $slots[$row['end']] . LF,
						TAB . TAB . TAB . TAB . TAB
							. $this->getEditIcon($uid) . LF
							. TAB . TAB . TAB . TAB . TAB
							. $this->getDeleteIcon($uid) . LF
							. TAB . TAB . TAB . TAB . TAB
							. $this->getHideUnhideIcon(
								$uid,
								$hidden
							) . LF,
					);
				}
				$GLOBALS['TYPO3_DB']->sql_free_result($res);
				if ($found) {
					// Output the table array using the tableLayout array with the template
					// class.
					$content .= $this->page->doc->table($table, $tableLayout) . '<br />' . LF;
				} else {
					$content .= '<br />' . $LANG->getLL('norecords') . '<br /><br />' . LF;
				}
			}
		}


		return $content;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wse_events/mod1/class.tx_wseevents_speakerrestrictionslist.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wse_events/mod1/class.tx_wseevents_speakerrestrictionslist.php']);
}

?>
