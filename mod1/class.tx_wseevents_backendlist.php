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
 * Class 'tx_wseevents_backendlist' for the 'wse_events' extension.
 *
 * @package		TYPO3
 * @subpackage	wse_events
 * @author		Michael Oehlhof <typo3@oehlhof.de>
 */

require_once('conf.php');
require_once($BACK_PATH.'init.php');
require_once($BACK_PATH.'template.php');
require_once(t3lib_extMgm::extPath('wse_events').'class.tx_wseevents_dbplugin.php');

define('TAB', chr(9));
define('LF', chr(10));

class tx_wseevents_backendlist extends tx_wseevents_dbplugin {
	/** the table we're working on */
	var $tableName;

	/** Holds a reference to the back-end page object. */
	var $page;

	/**
	 * The constructor. Sets the table name and the back-end page object.
	 *
	 * @param	object		the current back-end page object
	 * @return	[type]		...
	 * @access public
	 */
	function tx_wseevents_backendlist(&$page) {
		$this->setTableNames();
		$this->page =& $page;
	}

	/**
	 * Generates an edit record icon which is linked to the edit view of
	 * a record.
	 *
	 * @param	string		the name of the table where the record is in
	 * @param	integer		the uid of the record
	 * @return	string		the HTML source code to return
	 * @access public
	 */
	function getEditIcon($uid) {
		global $BACK_PATH, $LANG, $BE_USER;

		$result = '';

		if ($BE_USER->check('tables_modify', $this->tableName)
			&& $BE_USER->doesUserHaveAccess(t3lib_BEfunc::getRecord('pages', $this->page->pageInfo['uid']), 16)) {
			$params = '&edit['.$this->tableName.']['.$uid.']=edit';
			$editOnClick = $this->editNewUrl($params, $BACK_PATH);
			$langEdit = $LANG->getLL('edit');
			$result = '<a href="'.htmlspecialchars($editOnClick).'">'
				.'<img '
				.t3lib_iconWorks::skinImg(
					$BACK_PATH,
					'gfx/edit2.gif',
					'width="11" height="12"')
				.' title="'.$langEdit.'" alt="'.$langEdit.'" class="icon" />'
				.'</a>';
		}

		return $result;
	}

	/**
	 * Generates a linked delete record icon whith a JavaScript confirmation
	 * window.
	 *
	 * @param	string		the name of the table where the record is in
	 * @param	integer		the uid of the record
	 * @return	string		the HTML source code to return
	 * @access public
	 */
	function getDeleteIcon($uid) {
		global $BACK_PATH, $LANG, $BE_USER;

		$result = '';

		if ($BE_USER->check('tables_modify', $this->tableName)
			&& $BE_USER->doesUserHaveAccess(t3lib_BEfunc::getRecord('pages', $this->page->pageInfo['uid']), 16)) {
			$params = '&cmd['.$this->tableName.']['.$uid.'][delete]=1';

			$referenceWarning = '';
			if ((float) $GLOBALS['TYPO3_CONF_VARS']['SYS']['compat_version'] >= 4.0) {
				$referenceWarning = t3lib_BEfunc::referenceCount(
					$this->tableName,
					$uid,
					' '.$LANG->getLL('referencesWarning'));
			}

			$confirmation = htmlspecialchars(
				'if (confirm('
				.$LANG->JScharCode(
					$LANG->getLL('deleteWarning')
					.$referenceWarning)
				.')) {return true;} else {return false;}');
			$langDelete = $LANG->getLL('delete', 1);
			$result = '<a href="'
				.htmlspecialchars($this->page->doc->issueCommand($params))
				.'" onclick="'.$confirmation.'">'
				.'<img'
				.t3lib_iconWorks::skinImg(
					$BACK_PATH,
					'gfx/garbage.gif',
					'width="11" height="12"'
				)
				.' title="'.$langDelete.'" alt="'.$langDelete.'" class="deleteicon" />'
				.'</a>';
		}

		return $result;
	}

	/**
	 * Returns a "create new record" image tag that is linked to the new record view.
	 *
	 * @param	integer		the page id where the record should be stored
	 * @return	string		the HTML source code to return
	 * @access public
	 */
	function getNewIcon($pid) {
		global $BACK_PATH, $LANG, $BE_USER;

		# the name of the table where the record should be saved to is stored in $this->tableName
		if ($BE_USER->check('tables_modify', $this->tableName)
			&& $BE_USER->doesUserHaveAccess(t3lib_BEfunc::getRecord('pages', $this->page->pageInfo['uid']), 16)
			&& $this->page->pageInfo['doktype'] == 254) {
			$params = '&edit['.$this->tableName.']['.$pid.']=new';
			$editOnClick = $this->editNewUrl($params, $BACK_PATH);
			$langNew = $LANG->getLL('newRecordGeneral');
			$result = TAB.TAB
				.'<div id="typo3-newRecordLink">'.LF
				.TAB.TAB.TAB
				.'<a href="'.htmlspecialchars($editOnClick).'">'.LF
				.TAB.TAB.TAB.TAB
				.'<img'
				.t3lib_iconWorks::skinImg(
					$BACK_PATH,
					'gfx/new_record.gif',
					'width="7" height="4"')
				// We use an empty alt attribute as we already have a textual
				// representation directly next to the icon.
				.' title="'.$langNew.'" alt="" />'.LF
				.TAB.TAB.TAB.TAB
				.$langNew.LF
				.TAB.TAB.TAB
				.'</a>'.LF
				.TAB.TAB
				.'</div>'.LF;
		}

		return $result;
	}

	/**
	 * Returns the url for the "create new record" link and the "edit record" link.
	 *
	 * @param	string		the parameters for tce
	 * @param	string		the back-path to the /typo3 directory
	 * @return	string		the url to return
	 * @access protected
	 */
	function editNewUrl($params, $backPath = '') {
		$returnUrl = 'returnUrl='.rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI'));

		return $backPath.'alt_doc.php?'.$returnUrl.$params;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wse_events/mod1/class.tx_wseevents_backendlist.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wse_events/mod1/class.tx_wseevents_backendlist.php']);
}

?>
