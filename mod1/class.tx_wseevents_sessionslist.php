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
/***************************************************************
*  Because I dont want to redefine the wheel again, some ideas
*  and code snippets are taken from the seminar manager extension
*  tx_seminars
***************************************************************/

require_once('conf.php');
require_once($BACK_PATH.'init.php');
require_once($BACK_PATH.'template.php');
require_once(t3lib_extMgm::extPath('wse_events').'mod1/class.tx_wseevents_backendlist.php');


/**
 * Class 'tx_wseevents_sesionslist' for the 'wse_events' extension.
 *
 * @package		TYPO3
 * @subpackage	wse_events
 * @author		Michael Oehlhof <typo3@oehlhof.de>
 */
class tx_wseevents_sessionslist extends tx_wseevents_backendlist{

	// List of shortkeys of categories
	var $categories;

	// List of languages
	var $syslang;
	
	/**
	 * The constructor. Calls the constructor of the parent class and sets
	 * $this->tableName.
	 *
	 * @param	object		the current back-end page object
	 * @return	void		...
	 */
	function tx_wseevents_sessionslist(&$page) {
		global $BACK_PATH;

		parent::tx_wseevents_backendlist($page);
		$this->tableName = $this->tableSessions;

		$this->backPath = $BACK_PATH;
#debug($BACK_PATH,'BACK_PATH');	
#		$this->page = $page;
	}

	
	/**
	 * Generates and prints out an event list.
	 *
	 * @param	array		the table where the record data is to be addded
	 * @param	array		the current record
	 * @return	void
	 */
	function addRowToTable(&$table, $row) {
		global $BE_USER, $BACK_PATH;
		$uid = $row['uid'];
		$hidden = $row['hidden'];
		
		if ($row['sys_language_uid']==0) {
			$catnum = $this->categories[$row['category']].sprintf ('%02d', $row['number']);
		} else {
			$catnum = '';
		}
		// Get language flag
#		$imglang = $this->languageFlag($row['sys_language_uid']);
		list($imglang, $imgtrans) = $this->makeLocalizationPanel($this->tableName,$row);

		// Add the result row to the table array.
		$table[] = array(
			TAB.TAB.TAB.TAB.TAB
				.$catnum.LF,
			TAB.TAB.TAB.TAB.TAB
				.t3lib_div::fixed_lgd_cs(
					$row['name'],
					50
				).LF,
			TAB.TAB.TAB.TAB.TAB
				.$imglang.LF,
			TAB.TAB.TAB.TAB.TAB
				.$imgtrans.LF,
			TAB.TAB.TAB.TAB.TAB
				.$row['timeslots'].LF,
			TAB.TAB.TAB.TAB.TAB
				.$this->getEditIcon($uid).LF
				.TAB.TAB.TAB.TAB.TAB
				.$this->getDeleteIcon($uid).LF
				.TAB.TAB.TAB.TAB.TAB
				.$this->getHideUnhideIcon($uid,$hidden).LF,
		);
	}
	
	
	/**
	 * Generates and prints out an event list.
	 *
	 * @return	string		the HTML source code of the event list
	 * @access public
	 */
	function show() {
		global $TCA, $LANG, $BE_USER;

		// Loading all TCA details for this table:
		t3lib_div::loadTCA($this->tableSessions);

#debug($BE_USER);
		// Get selected backend language of user
		$userlang = $BE_USER->uc[moduleData][web_layout][language];
#		debug($userlang);
		
		// Initialize the variable for the HTML source code with info text
		$content = $this->page->doc->rfw($LANG->getLL('sessions.info')).'<br />'.LF;

		// Set the table layout of the event list.
		$tableLayout = array(
			'table' => array(
				TAB.TAB.'<table cellpadding="0" cellspacing="0" class="typo3-dblist" border="1" rules="rows">'.LF,
				TAB.TAB.'</table>'.LF
			),
			array(
				'tr' => array(
					TAB.TAB.TAB.'<thead>'.LF
						.TAB.TAB.TAB.TAB.'<tr>'.LF,
					TAB.TAB.TAB.TAB.'</tr>'.LF
						.TAB.TAB.TAB.'</thead>'.LF
				),
				'defCol' => array(
					TAB.TAB.TAB.TAB.TAB.'<td class="c-headLineTable">'.LF,
					TAB.TAB.TAB.TAB.TAB.'</td>'.LF
				)
			),
			'defRow' => array(
				'tr' => array(
					TAB.TAB.TAB.'<tr>'.LF,
					TAB.TAB.TAB.'</tr>'.LF
				),
				array(
					TAB.TAB.TAB.TAB.'<td>'.LF,
					TAB.TAB.TAB.TAB.'</td>'.LF
				),
				array(
					TAB.TAB.TAB.TAB.'<td>'.LF,
					TAB.TAB.TAB.TAB.'</td>'.LF
				),
				array(
					TAB.TAB.TAB.TAB.'<td>'.LF,
					TAB.TAB.TAB.TAB.'</td>'.LF
				),
				array(
					TAB.TAB.TAB.TAB.'<td>'.LF,
					TAB.TAB.TAB.TAB.'</td>'.LF
				),
				array(
					TAB.TAB.TAB.TAB.'<td>'.LF,
					TAB.TAB.TAB.TAB.'</td>'.LF
				),
				array(
					TAB.TAB.TAB.TAB.'<td nowrap>'.LF,
					TAB.TAB.TAB.TAB.'</td>'.LF
				),
				'defCol' => array(
					TAB.TAB.TAB.TAB.'<td>'.LF,
					TAB.TAB.TAB.TAB.'</td>'.LF
				)
			)
		);

		// Fill the first row of the table array with the header.
		$tableheader = array(
			array(
				TAB.TAB.TAB.TAB.TAB.TAB
					.'<span style="color: #ffffff; font-weight: bold;">'
					.$LANG->getLL('sessions.category').'</span>'.LF,
				TAB.TAB.TAB.TAB.TAB.TAB
					.'<span style="color: #ffffff; font-weight: bold;">'
					.$LANG->getLL('sessions.name').'</span>'.LF,
				TAB.TAB.TAB.TAB.TAB.TAB
					.'<span style="color: #ffffff; font-weight: bold;">'
					.$LANG->getLL('language').'</span>'.LF,
				TAB.TAB.TAB.TAB.TAB.TAB
					.'<span style="color: #ffffff; font-weight: bold;">'
					.$LANG->getLL('translate').'</span>'.LF,
				TAB.TAB.TAB.TAB.TAB.TAB
					.'<span style="color: #ffffff; font-weight: bold;">'
					.$LANG->getLL('sessions.timeslots').'</span>'.LF,
				'',
			)
		);

		// unserialize the configuration array
		$globalConfiguration = unserialize(
			$GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['wse_events']
		);

		# Get date format for selected language
		if (!$conf[$index.'.']['fmtDate']){
			$conf['strftime'] = '%d.%m.%Y';
		} else {
			$conf['strftime'] = $conf[$index.'.']['fmtDate'];
		}

		// Get list of pid 
		$this->selectedPids = $this->getRecursiveUidList($this->page->pageInfo['uid'],2);
		// Check if sub pages available and remove main page from list
		if ($this->selectedPids<>$this->page->pageInfo['uid']) {
			$this->selectedPids = t3lib_div::rmFromList($this->page->pageInfo['uid'],$this->selectedPids);
		} else {
			// if no sub pages, get one level up
			$this->selectedPids = $this->getRecursiveUidList($this->page->pageInfo['pid'],2);
			// remove up level page
			$this->selectedPids = t3lib_div::rmFromList($this->page->pageInfo['pid'],$this->selectedPids);
			// remove other event pages
			$this->selectedPids = $this->removeEventPages($this->selectedPids);
			// add this page to the list
			$this->selectedPids .= $this->selectedPids?','.$this->page->pageInfo['uid']:$this->page->pageInfo['uid'];
		}
		// Remove pages with common data
		$eventPids = $this->removeCommonPages($this->selectedPids);
		// Get page titles
		$this->selectedPidsTitle = $this->getPidTitleList($this->selectedPids);
		// Get the where clause
		$wherePid = 'pid IN ('.$GLOBALS['TYPO3_DB']->cleanIntList($this->selectedPids).')';

		// Add icon for new record
		if (!empty($eventPids)) {
			$content .= $this->getNewIconList($eventPids,$this->selectedPidsTitle);
		}

		// -------------------- Get list of categories --------------------
		// Initialize variables for the database query.
		$queryWhere = $wherePid.$thisPage.t3lib_BEfunc::deleteClause($this->tableCategories).' AND sys_language_uid=0';
		$additionalTables = '';
		$groupBy = '';
		$orderBy = 'shortkey';
		$limit = '';

		// Get list of all events
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'*',
			$this->tableCategories,
			$queryWhere,
			$groupBy,
			$orderBy,
			$limit);

		$this->categories = array();
		if ($res) {
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$this->categories[$row['uid']] = $row['shortkey'];
			}
		}
		
		// -------------------- Get list of events --------------------
		// Initialize variables for the database query.
		$queryWhere = $wherePid.t3lib_BEfunc::deleteClause($this->tableEvents).' AND sys_language_uid=0';
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
		}
		
		// Add box for event selection
		
		
		// Get list of sessions for an event
		foreach ($events as $event) {
			$this->id = $event['pid'];
			
			// Show name of event
			$content .= '<span style="font-size:1.2em"><b>'.$LANG->getLL('event').' '.$event['name'].'</b></span>';
//			$content .= '&nbsp;'.$this->getNewIcon($event['pid'],0).'<br />';

			// Initialize languages
			$this->initializeLanguages($event['pid']);
			
			// Initialize variables for the database query.
			$queryWhere = $wherePid.' AND event='.$event['uid'].t3lib_BEfunc::deleteClause($this->tableName).' AND sys_language_uid=0';
			$additionalTables = '';
			$groupBy = '';
			$orderBy = 'category,number,name';
			$limit = '';

			// Get list of all sessions
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
					$this->addRowToTable($table, $row);
					// Check for translations.
					$queryWhere = $wherePid.' AND l18n_parent='.$row['uid'].t3lib_BEfunc::deleteClause($this->tableName);
					$additionalTables = '';
					$groupBy = '';
					$orderBy = 'sys_language_uid';
					$limit = '';

					// Get list of all translated sessions
					$reslang = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
						'*',
						$this->tableName,
						$queryWhere,
						$groupBy,
						$orderBy,
						$limit);
					if ($reslang) {
						while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($reslang)) {
							$this->addRowToTable($table, $row);
						}
					}
				}
				if ($found) {
					// Output the table array using the tableLayout array with the template
					// class.
					$content .= $this->page->doc->table($table, $tableLayout).'<br />'.LF;
				} else {
					$content .= '<br />'.$LANG->getLL('norecords').'<br /><br />'.LF;
				}
			}
		}

		return $content;
	}

	/**
	 * Generates a linked hide or unhide icon depending on the record's hidden
	 * status.
	 *
	 * @param	string		the name of the table where the record is in
	 * @param	integer		the UID of the record
	 * @param	boolean		indicates if the record is hidden (true) or is visible (false)
	 * @return	string		the HTML source code of the linked hide or unhide icon
	 * @access protected
	 */
	function getHideUnhideIcon($uid, $hidden) {
		global $BACK_PATH, $LANG, $BE_USER;
		$result = '';

		if ($BE_USER->check('tables_modify', $this->tableName)
			&& $BE_USER->doesUserHaveAccess(t3lib_BEfunc::getRecord('pages', $this->page->pageInfo['uid']), 16)) {
			if ($hidden) {
				$params = '&data['.$this->tableName.']['.$uid.'][hidden]=0';
				$icon = 'gfx/button_unhide.gif';
				$langHide = $LANG->getLL('unHide');
			} else {
				$params = '&data['.$this->tableName.']['.$uid.'][hidden]=1';
				$icon = 'gfx/button_hide.gif';
				$langHide = $LANG->getLL('hide');
			}

			$result = '<a href="'
				.htmlspecialchars($this->page->doc->issueCommand($params)).'">'
				.'<img'
				.t3lib_iconWorks::skinImg(
					$BACK_PATH,
					$icon,
					'width="11" height="12"'
				)
				.' title="'.$langHide.'" alt="'.$langHide.'" class="hideicon" />'
				.'</a>';
		}

		return $result;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wse_events/mod1/class.tx_wseevents_sessionslist.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wse_events/mod1/class.tx_wseevents_sessionslist.php']);
}

?>
