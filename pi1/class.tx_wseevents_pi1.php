<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007 Michael Oehlhof (michael@oehlhof.de)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Plugin 'WSE Events' for the 'wse_events' extension.
 * Displays session data as list and detail view
 *
 * @author	Michael Oehlhof <michael@oehlhof.de>
 */


require_once(PATH_tslib.'class.tslib_pibase.php');

class tx_wseevents_pi1 extends tslib_pibase {
	var $prefixId = 'tx_wseevents_pi1';		// Same as class name
	var $scriptRelPath = 'pi1/class.tx_wseevents_pi1.php';	// Path to this script relative to the extension dir.
	var $extKey = 'wse_events';	// The extension key.
	var $pi_checkCHash = TRUE;
	
	/**
	 *Main function, decides in which form the data is displayed
	 */
	function main($content,$conf)	{
		$this->pi_initPIflexform(); // Init and get the flexform data of the plugin
		$piFlexForm = $this->cObj->data['pi_flexform'];
		$index = $GLOBALS['TSFE']->sys_language_uid;

		$sDef = current($piFlexForm['data']);       
		$lDef = array_keys($sDef);
		
		$flexFormValuesArray['dynListType'] = $this->pi_getFFvalue($piFlexForm, 'dynListType', 'display', $lDef[$index]);	
		$conf['pidList'] = $this->pi_getFFvalue($piFlexForm, 'pages', 'sDEF');
		switch((string)$flexFormValuesArray['dynListType'])	{
			case 'sessionlist':
				$conf['recursive'] = $this->cObj->data['recursive'];
				$conf['singleSession'] = $this->pi_getFFvalue($piFlexForm, 'singleSession', 'display');
				$conf['singleSpeaker'] = $this->pi_getFFvalue($piFlexForm, 'singleSpeaker', 'display');
				$conf['fieldList'] = $this->pi_getFFvalue($piFlexForm, 'fieldList', 'display');
				if ($conf['fieldList']=='') { $conf['fieldList'] = 'number,name,speaker,timeslots'; }
				$conf['sorting'] = $this->pi_getFFvalue($piFlexForm, 'sorting', 'display');
				if ($conf['sorting']=='') { $conf['sorting'] = 'name:0'; }
				return $this->pi_wrapInBaseClass($this->listSessionView($content,$conf));
			break;
			case 'sessiondetail':
				// Set table to session table
				$this->internal['currentTable'] = 'tx_wseevents_sessions';
				$this->internal['currentRow']=$this->piVars['showUid'];
				return $this->pi_wrapInBaseClass($this->singleSessionView($content,$conf));
			break;
			case 'speakerlist':
				return $this->pi_wrapInBaseClass('Speaker list, not yet implemented');
			break;
			case 'speakerdetail':
				$this->internal['currentTable'] = 'tx_wseevents_speakers';
				$this->internal['currentRow']=$this->piVars['showUid'];
				return $this->pi_wrapInBaseClass($this->singleSpeakerView($content,$conf));
			break;
			case 'timeslots':
				return $this->pi_wrapInBaseClass('Time slot list, not yet implemented');
			break;
			default:
				return $this->pi_wrapInBaseClass('Not implemented: '.(string)$flexFormValuesArray['dynListType']);
			break;
		}
	}
	
	/**
	 * Display a list of sessions for the event that is set in the flex form settings
	 */
	function listSessionView($content,$conf)	{
		$this->conf=$conf;		// Setting the TypoScript passed to this function in $this->conf
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();		// Loading the LOCAL_LANG values
		$index = $GLOBALS['TSFE']->sys_language_uid;
		
		$lConf = $this->conf['listView.'];	// Local settings for the listView function
	
		// Set table to session table
		$this->internal['currentTable'] = 'tx_wseevents_sessions';
		
		if (!isset($this->piVars['pointer']))	$this->piVars['pointer']=0;
		if (!isset($this->piVars['mode']))	$this->piVars['mode']=1;

		// Initializing the query parameters:
		$sorting = $this->conf['sorting'];
		list($this->internal['orderBy'],$this->internal['descFlag']) = explode(':',$sorting);
		$this->internal['results_at_a_time']=t3lib_div::intInRange($lConf['results_at_a_time'],0,1000,100);		// Number of results to show in a listing.
		$this->internal['maxPages']=t3lib_div::intInRange($lConf['maxPages'],0,1000,2);;		// The maximum number of "pages" in the browse-box: "Page 1", 'Page 2', etc.
		$this->internal['searchFieldList']='uid,name,categorie,number,speaker,room,timeslots,teaser';
		$this->internal['orderByList']='name';

	    $where = ' AND '.$this->internal['currentTable'].'.sys_language_uid = '.$index;

		// Get number of records:
		$res = $this->pi_exec_query($this->internal['currentTable'],1,$where);
		list($this->internal['res_count']) = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);

		// Make listing query, pass query to SQL database:
		$res = $this->pi_exec_query($this->internal['currentTable'],0,$where);

		// Put the whole list together:
		$fullTable='';	// Clear var;
#		$fullTable.=t3lib_div::view_array($this->piVars);	// DEBUG: Output the content of $this->piVars for debug purposes. REMEMBER to comment out the IP-lock in the debug() function in t3lib/config_default.php if nothing happens when you un-comment this line!
#		$fullTable.=t3lib_div::view_array($this->conf);	// DEBUG: Output the content of $this->piVars for debug purposes. REMEMBER to comment out the IP-lock in the debug() function in t3lib/config_default.php if nothing happens when you un-comment this line!
		
		// Adds the whole list table
		$fullTable.=$this->pi_list_makelist($res);

		// Returns the content from the plugin.
		return $fullTable;
	}

	/**
	 * Display the details of a single session
	 */
	function singleSessionView($content,$conf)	{
		$this->conf=$conf;
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();
		
		$this->internal['currentRow'] = $this->pi_getRecord($this->internal['currentTable'],$this->piVars['showUid']);
	
		// This sets the title of the page for use in indexed search results:
		if ($this->internal['currentRow']['title'])	$GLOBALS['TSFE']->indexedDocTitle=$this->internal['currentRow']['title'];

		// Link for back to list view
		$label = $this->pi_getLL('back','Back');  // the link text
		$overrulePIvars = '';
		$clearAnyway=1;    // the current values of piVars will NOT be preserved
		$altPageId=$this->piVars['backUid'];      // ID of the view page
		$backlink = $this->pi_linkTP_keepPIvars($label, $overrulePIvars, $cache, $clearAnyway, $altPageId);
		
		$content='<div'.$this->pi_classParam('singleView').'>
			<H2>'.$this->getFieldContent('name').'</H2>
			<table>
				<tr>
					<td nowrap valign="top"'.$this->pi_classParam('singleView-HCell').'><p>'.$this->getFieldHeader('teaser').':</p></td>
					<td valign="top"><p>'.$this->getFieldContent('teaser').'</p></td>
				</tr>
				<tr>
					<td nowrap valign="top"'.$this->pi_classParam('singleView-HCell').'><p>'.$this->getFieldHeader('speaker').':</p></td>
					<td valign="top"><p>'.$this->getFieldContent('speaker').'</p></td>
				</tr>
				<tr>
					<td nowrap valign="top"'.$this->pi_classParam('singleView-HCell').'><p>'.$this->getFieldHeader('timeslots').':</p></td>
					<td valign="top"><p>'.$this->getFieldContent('timeslots').'</p></td>
				</tr>
				<tr>
					<td nowrap valign="top"'.$this->pi_classParam('singleView-HCell').'><p>'.$this->getFieldHeader('description').':</p></td>
					<td valign="top"><p>'.$this->getFieldContent('description').'</p></td>
				</tr>
			</table>
		<p>'.$backlink.'</p></div>'.
		$this->pi_getEditPanel();
	
		return $content;
	}

	/**
	 * Display the details of a single speaker
	 */
	function singleSpeakerView($content,$conf)	{
		$this->conf=$conf;
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();
		
		$this->internal['currentRow'] = $this->pi_getRecord($this->internal['currentTable'],$this->piVars['showUid']);
	
		// This sets the title of the page for use in indexed search results:
		if ($this->internal['currentRow']['title'])	$GLOBALS['TSFE']->indexedDocTitle=$this->internal['currentRow']['title'];

		// Link for back to list view
		$label = $this->pi_getLL('back','Back');  // the link text
		$overrulePIvars = '';
		$clearAnyway=1;    // the current values of piVars will NOT be preserved
		$altPageId=$this->piVars['backUid'];      // ID of the view page
		$backlink = $this->pi_linkTP_keepPIvars($label, $overrulePIvars, $cache, $clearAnyway, $altPageId);
		
		$content='<div'.$this->pi_classParam('singleView').'>
			<H2>'.$this->getFieldContent('name').'</H2>
			<table>
				<tr>
					<td nowrap valign="top"'.$this->pi_classParam('singleView-HCell').'><p>'.$this->getFieldHeader('email').':</p></td>
					<td valign="top"><p>'.$this->getFieldContent('email').'</p></td>
				</tr>
				<tr>
					<td nowrap valign="top"'.$this->pi_classParam('singleView-HCell').'><p>'.$this->getFieldHeader('info').':</p></td>
					<td valign="top"><p>'.$this->getFieldContent('info').'</p></td>
				</tr>
			</table>
		<p>'.$backlink.'</p></div>'.
		$this->pi_getEditPanel();
	
		return $content;
	}

	/**
	 * Display one record of session data
	 */
	function pi_list_row($c)	{
		$editPanel = $this->pi_getEditPanel();
		if ($editPanel)	$editPanel='<TD>'.$editPanel.'</TD>';

		if (isset($this->conf['singleSession'])) {
		    $label = $this->getFieldContent('name');  // the link text
		    $overrulePIvars = '';//array('session' => $this->getFieldContent('uid'));
		    $overrulePIvars = array('showUid' => $this->internal['currentRow']['uid'], 'backUid' => $GLOBALS['TSFE']->id);
		    $clearAnyway=1;    // the current values of piVars will NOT be preserved
		    $altPageId=$this->conf['singleSession'];      // ID of the target page, if not on the same page
		    $sessionname = $this->pi_linkTP_keepPIvars($label, $overrulePIvars, $cache, $clearAnyway, $altPageId);
		} else {
			$sessionname = $this->getFieldContent('name');
		}
		$thisrow ='<tr'.($c%2 ? $this->pi_classParam('listrow-odd') : '').'>';
		foreach(explode(',',$this->conf['fieldList']) as $rowfield){
			switch ((string)$rowfield) {
				case 'number': 
					$thisrow .= '<td valign="top"><p>'.$this->getFieldContent('number').'</p></td>';
				break;
				case 'name': 
					$thisrow .= '<td valign="top" title="'.$this->getFieldContent('teaser').'"><p>'.$sessionname.'</p></td>';
				break;
				case 'speaker': 
					$thisrow .= '<td valign="top"><p>'.$this->getFieldContent('speaker').'</p></td>';
				break;
				case 'room': 
					$thisrow .= '<td valign="top"><p>'.$this->getFieldContent('room').'</p></td>';
				break;
				case 'timeslots': 
					$thisrow .= '<td valign="top"><p>'.$this->getFieldContent('timeslots').'</p></td>';
				break;
			}
		}
		$thisrow .= '</tr>';
		return $thisrow;
	}

	/**
	 * Display header for session list
	 */
	function pi_list_header()	{
		$thisrow ='<tr'.$this->pi_classParam('listrow-header').'>';
		foreach(explode(',',$this->conf['fieldList']) as $rowfield){
			switch ((string)$rowfield) {
				case 'number': 
					$thisrow .= '<td nowrap><p>'.$this->getFieldHeader('number').'</p></td>';
				break;
				case 'name': 
					$thisrow .= '<td nowrap><p>'.$this->getFieldHeader('name').'</p></td>';
				break;
				case 'speaker': 
					$thisrow .= '<td nowrap><p>'.$this->getFieldHeader('speaker').'</p></td>';
				break;
				case 'room': 
					$thisrow .= '<td nowrap><p>'.$this->getFieldHeader('room').'</p></td>';
				break;
				case 'timeslots': 
					$thisrow .= '<td nowrap><p>'.$this->getFieldHeader('timeslots').'</p></td>';
				break;
			}
		}
		$thisrow .= '</tr>';
		return $thisrow;
	}

	/**
	 * Get content of one field
	 */
	function getFieldContent($fN)	{
		switch($fN) {
			case 'uid':
				return $this->pi_list_linkSingle($this->internal['currentRow'][$fN],$this->internal['currentRow']['uid'],1);	// The "1" means that the display of single items is CACHED! Set to zero to disable caching.
			break;
			
			case 'number':
				$datacat = $this->pi_getRecord('tx_wseevents_categories',$this->internal['currentRow']['categorie']);
				$datanum = $this->internal['currentRow'][$fN];
				return $datacat['shortkey'].sprintf ('%02d', $datanum);
			break;

			case 'room':
				$data = $this->pi_getRecord('tx_wseevents_rooms',$this->internal['currentRow'][$fN]);
				return $data['name'];
			break;

			case 'speaker':
				foreach(explode(',',$this->internal['currentRow'][$fN]) as $k){
					$data = $this->pi_getRecord('tx_wseevents_speakers',$k);
					if (isset($this->conf['singleSpeaker'])) {
					    $label = $data['name'];  // the link text
					    $overrulePIvars = '';//array('session' => $this->getFieldContent('uid'));
					    $overrulePIvars = array('showUid' => $data['uid'], 'backUid' => $GLOBALS['TSFE']->id);
					    $clearAnyway=1;    // the current values of piVars will NOT be preserved
					    $altPageId=$this->conf['singleSpeaker'];      // ID of the target page, if not on the same page
					    $speakername = $this->pi_linkTP_keepPIvars($label, $overrulePIvars, $cache, $clearAnyway, $altPageId);
					} else {
					    $speakername = $data['name'];
					}
					if (isset($content)) {
						$content .= '<br />'.$speakername;
					} else {
						$content = $speakername;
					}
				}
				return $content;
			break;

			case 'timeslots':
				foreach(explode(',',$this->internal['currentRow'][$fN]) as $k){
					$data = $this->pi_getRecord('tx_wseevents_timeslots',$k);
				    $timeslotname = $data['name'];
					if (isset($content)) {
						$content .= '<br />'.$timeslotname;
					} else {
						$content = $timeslotname;
					}
				}
				if (empty($content)) {
					$content = $this->pi_getLL('tx_wseevents_sessions.notimeslots','[not yet sheduled]');
				}
				return $content;
			break;

			default:
				return $this->internal['currentRow'][$fN];
			break;
		}
	}

	/**
	 * Get label of one field
	 */
	function getFieldHeader($fN)	{
		switch($fN) {
			
			default:
				return $this->pi_getLL($this->internal['currentTable'].'.listFieldHeader_'.$fN,'['.$fN.']');
			break;
		}
	}
	
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wse_events/pi1/class.tx_wseevents_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wse_events/pi1/class.tx_wseevents_pi1.php']);
}

?>