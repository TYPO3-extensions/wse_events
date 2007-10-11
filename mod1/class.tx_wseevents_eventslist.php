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
 * Class 'tx_wseevents_eventslist' for the 'wse_events' extension.
 *
 * @package		TYPO3
 * @subpackage	wse_events
 * @author		Michael Oehlhof <typo3@oehlhof.de>
 */
class tx_wseevents_eventslist extends tx_wseevents_backendlist{

	/**
	 * The constructor. Calls the constructor of the parent class and sets
	 * $this->tableName.
	 *
	 * @param	object		the current back-end page object
	 * @return	void		...
	 */
	function tx_wseevents_eventslist(&$page) {
		parent::tx_wseevents_backendlist($page);
		$this->tableName = $this->tableEvents;
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
			$imglang = $this->syslang[0][0];
		} else {
			$catnum = '';
			$imglang = '';
			foreach ($this->syslang as $thislang) {
				if ($row['sys_language_uid'] == $thislang[1]) {
					$imglang = '<img'.t3lib_iconWorks::skinImg(
										$BACK_PATH,
										'gfx/'.$thislang[2],
										'width="20" height="14"')
									.' alt="'.$thislang[0].'"> '.$thislang[0];
				}
			}
			
		}
		// Add the result row to the table array.
		$table[] = array(
			TAB.TAB.TAB.TAB.TAB
				.t3lib_div::fixed_lgd_cs(
					$row['name'],
					$BE_USER->uc['titleLen']
				).LF,
			TAB.TAB.TAB.TAB.TAB
				.strftime($this->conf['strftime'], $row['begin']).LF,
			TAB.TAB.TAB.TAB.TAB
				.$row['length'].LF,
			TAB.TAB.TAB.TAB.TAB
				.$row['timebegin'].LF,
			TAB.TAB.TAB.TAB.TAB
				.$row['timeend'].LF,
			TAB.TAB.TAB.TAB.TAB
				.$imglang.LF,
			TAB.TAB.TAB.TAB.TAB
				.$this->getEditIcon($uid).LF
				.TAB.TAB.TAB.TAB.TAB
				.$this->getDeleteIcon($uid).LF
				.TAB.TAB.TAB.TAB.TAB
				.$this->getHideUnhideIcon(
					$uid,
					$hidden
				).LF,
		);
	}
	
	
	/**
	 * Generates and prints out an event list.
	 *
	 * @return	string		the HTML source code of the event list
	 * @access public
	 */
	function show() {
		global $LANG, $BE_USER;

		// Initialize the variable for the HTML source code.
		$content = '';

		// Set the table layout of the event list.
		$tableLayout = array(
			'table' => array(
				TAB.TAB.'<table cellpadding="0" cellspacing="0" class="typo3-dblist">'.LF,
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
				'defCol' => array(
					TAB.TAB.TAB.TAB.'<td>'.LF,
					TAB.TAB.TAB.TAB.'</td>'.LF
				)
			)
		);

		// Fill the first row of the table array with the header.
		$tableHdr = array(
			array(
				TAB.TAB.TAB.TAB.TAB.TAB
					.'<span style="color: #ffffff; font-weight: bold;">'
					.$LANG->getLL('events.name').'</span>'.LF,
				TAB.TAB.TAB.TAB.TAB.TAB
					.'<span style="color: #ffffff; font-weight: bold;">'
					.$LANG->getLL('events.begin').'</span>'.LF,
				TAB.TAB.TAB.TAB.TAB.TAB
					.'<span style="color: #ffffff; font-weight: bold;">'
					.$LANG->getLL('events.length').'</span>'.LF,
				TAB.TAB.TAB.TAB.TAB.TAB
					.'<span style="color: #ffffff; font-weight: bold;">'
					.$LANG->getLL('events.timebegin').'</span>'.LF,
				TAB.TAB.TAB.TAB.TAB.TAB
					.'<span style="color: #ffffff; font-weight: bold;">'
					.$LANG->getLL('events.timeend').'</span>'.LF,
				TAB.TAB.TAB.TAB.TAB.TAB
					.'<span style="color: #ffffff; font-weight: bold;">'
					.$LANG->getLL('language').'</span>'.LF,
				'',
			)
		);

		// unserialize the configuration array
		$globalConfiguration = unserialize(
			$GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['wse_events']
		);

		# Get date format for selected language
		if (!$this->conf[$index.'.']['fmtDate']){
			$this->conf['strftime'] = '%d.%m.%Y';
		} else {
			$this->conf['strftime'] = $this->conf[$index.'.']['fmtDate'];
		}

		// Get list of pid 
		$this->selectedPids = $this->getRecursiveUidList($this->page->pageInfo['uid'],2);
		// Check if sub pages available and remove main page from list
		if ($this->selectedPids<>$this->page->pageInfo['uid']) {
			$this->selectedPids = t3lib_div::rmFromList($this->page->pageInfo['uid'],$this->selectedPids);
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

		foreach (explode(',',$eventPids) as $eventPid) {
			// Show name of page
			$content .= '<span style="font-size:1.2em"><b>'.$this->selectedPidsTitle[$eventPid].'</b></span>';
//			$content .= '&nbsp;'.$this->getNewIcon($event['pid'],0).'<br />';

			// Initialize variables for the database query.
			$queryWhere = 'pid='.$eventPid.t3lib_BEfunc::deleteClause($this->tableName).' AND sys_language_uid=0';
			$additionalTables = '';
			$groupBy = '';
			$orderBy = 'name';
			$limit = '';

			// Get list of all events
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'*',
				$this->tableName,
				$queryWhere,
				$groupBy,
				$orderBy,
				$limit);

			if ($res) {
				$found = false;
				$table = $tableHdr;
				while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
					$found = true;
					$uid = $row['uid'];
					$hidden = $row['hidden'];
					$this->addRowToTable($table, $row);
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

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wse_events/mod1/class.tx_wseevents_eventslist.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wse_events/mod1/class.tx_wseevents_eventslist.php']);
}

?>
