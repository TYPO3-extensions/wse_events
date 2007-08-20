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
	 * [Put your description here]
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
				return $this->pi_wrapInBaseClass($this->listSessionView($content,$conf));
			break;
			case 'sessiondetail':
				// Set table to session table
				$this->internal['currentTable'] = 'tx_wseevents_sessions';
				$this->internal['currentRow']=$this->piVars['showUid'];
				return $this->pi_wrapInBaseClass($this->singleSessionView($content,$conf));
			break;
			case 'speakerlist':
				return $this->pi_wrapInBaseClass('Speaker list');
			break;
			case 'timeslots':
				return $this->pi_wrapInBaseClass('Time slot list');
			break;
			default:
				return $this->pi_wrapInBaseClass('Not implemented: '.(string)$flexFormValuesArray['dynListType']);
			break;
		}
	}
	
	/**
	 * [Put your description here]
	 */
	function main_old($content,$conf)	{
		switch((string)$conf['CMD'])	{
			case 'singleView':
				list($t) = explode(':',$this->cObj->currentRecord);
				$this->internal['currentTable']=$t;
				$this->internal['currentRow']=$this->cObj->data;
				return $this->pi_wrapInBaseClass($this->singleView($content,$conf));
			break;
			default:
				if (strstr($this->cObj->currentRecord,'tt_content'))	{
					$conf['pidList'] = $this->cObj->data['pages'];
					$conf['recursive'] = $this->cObj->data['recursive'];
				}
				return $this->pi_wrapInBaseClass($this->listView($content,$conf));
			break;
		}
	}
	
	/**
	 * [Put your description here]
	 */
	function listSessionView($content,$conf)	{
		$this->conf=$conf;		// Setting the TypoScript passed to this function in $this->conf
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();		// Loading the LOCAL_LANG values
		
		$lConf = $this->conf['listView.'];	// Local settings for the listView function
	
		// Set table to session table
		$this->internal['currentTable'] = 'tx_wseevents_sessions';
		
		if ($this->piVars['showUid'])	{	// If a single element should be displayed:
			$this->internal['currentRow'] = $this->pi_getRecord($this->internal['currentTable'],$this->piVars['showUid']);
	
			$content = $this->singleView($content,$conf);
			return $content;
		} else {
			if (!isset($this->piVars['pointer']))	$this->piVars['pointer']=0;
			if (!isset($this->piVars['mode']))	$this->piVars['mode']=1;
	
				// Initializing the query parameters:
			list($this->internal['orderBy'],$this->internal['descFlag']) = explode(':',$this->piVars['sort']);
			$this->internal['results_at_a_time']=t3lib_div::intInRange($lConf['results_at_a_time'],0,1000,3);		// Number of results to show in a listing.
			$this->internal['maxPages']=t3lib_div::intInRange($lConf['maxPages'],0,1000,2);;		// The maximum number of "pages" in the browse-box: "Page 1", "Page 2", etc.
			$this->internal['searchFieldList']='uid,name,categorie,number,speaker,room,timeslots,teaser';
			$this->internal['orderByList']='name';
	
				// Get number of records:
//			$res = $GLOBALS["TYPO3_DB"]->exec_SELECTquery("*",$this->internal['currentTable'],"deleted = 0 AND hidden = 0");
			$res = $this->pi_exec_query($this->internal['currentTable'],1);
			list($this->internal['res_count']) = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);
	
				// Make listing query, pass query to SQL database:
			$res = $this->pi_exec_query($this->internal['currentTable']);
	
				// Put the whole list together:
			$fullTable='';	// Clear var;
		#	$fullTable.=t3lib_div::view_array($this->piVars);	// DEBUG: Output the content of $this->piVars for debug purposes. REMEMBER to comment out the IP-lock in the debug() function in t3lib/config_default.php if nothing happens when you un-comment this line!
	
//			$fullTable.='<hr>';
//			$fullTable.=t3lib_div::view_array($this->internal);
//			$fullTable.=t3lib_div::view_array($this->conf);
//			$fullTable.='<hr>';
			
				// Adds the whole list table
			$fullTable.=$this->pi_list_makelist($res);
	
			// Adds the search box:
//			$fullTable.=$this->pi_list_searchBox();
	
				// Adds the result browser:
//			$fullTable.=$this->pi_list_browseresults();
	
				// Returns the content from the plugin.
			return $fullTable;
		}
	}
	/**
	 * [Put your description here]
	 */
	function singleSessionView($content,$conf)	{
		$this->conf=$conf;
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();
		
	
		// This sets the title of the page for use in indexed search results:
		if ($this->internal['currentRow']['title'])	$GLOBALS['TSFE']->indexedDocTitle=$this->internal['currentRow']['title'];
	
		$content='<div'.$this->pi_classParam('singleView').'>
			<H2>Record "'.$this->internal['currentRow']['uid'].'" from table "'.$this->internal['currentTable'].'":</H2>
			<table>
				<tr>
					<td nowrap valign="top"'.$this->pi_classParam('singleView-HCell').'><p>'.$this->getFieldHeader('name').'</p></td>
					<td valign="top"><p>'.$this->getFieldContent('name').'</p></td>
				</tr>
				<tr>
					<td nowrap valign="top"'.$this->pi_classParam('singleView-HCell').'><p>'.$this->getFieldHeader('location').'</p></td>
					<td valign="top"><p>'.$this->getFieldContent('location').'</p></td>
				</tr>
				<tr>
					<td nowrap'.$this->pi_classParam('singleView-HCell').'><p>Last updated:</p></td>
					<td valign="top"><p>'.date('d-m-Y H:i',$this->internal['currentRow']['tstamp']).'</p></td>
				</tr>
				<tr>
					<td nowrap'.$this->pi_classParam('singleView-HCell').'><p>Created:</p></td>
					<td valign="top"><p>'.date('d-m-Y H:i',$this->internal['currentRow']['crdate']).'</p></td>
				</tr>
			</table>
		<p>'.$this->pi_list_linkSingle($this->pi_getLL('back','Back'),0).'</p></div>'.
		$this->pi_getEditPanel();
	
		return $content;
	}
	/**
	 * [Put your description here]
	 */
	function pi_list_row($c)	{
		$editPanel = $this->pi_getEditPanel();
		if ($editPanel)	$editPanel='<TD>'.$editPanel.'</TD>';

		if (isset($this->conf['singleSession'])) {
		    $label = $this->getFieldContent('name');  // the link text
		    $overrulePIvars = array('session' => $this->getFieldContent('uid'));
		    $clearAnyway=1;    // the current values of piVars will NOT be preserved
		    $altPageId=$this->conf['singleSession'];      // ID of the target page, if not on the same page
		    $sessionname = $this->pi_linkTP_keepPIvars($label, $overrulePIvars, $cache, $clearAnyway, $altPageId);
		} else {
			$sessionname = $this->getFieldContent('name');
		}
		
		return '<tr'.($c%2 ? $this->pi_classParam('listrow-odd') : '').'>
				<td valign="top"><p>'.$this->getFieldContent('number').'</p></td>
				<td valign="top" title="'.$this->getFieldContent('teaser').'"><p>'.$sessionname.'</p></td>
				<td valign="top"><p>'.$this->getFieldContent('speaker').'</p></td>
				<td valign="top"><p>'.$this->getFieldContent('room').'</p></td>
			</tr>';
	}
	/**
	 * [Put your description here]
	 */
	function pi_list_header()	{
		return '<tr'.$this->pi_classParam('listrow-header').'>
				<td nowrap><p>'.$this->getFieldHeader('number').'</p></td>
				<td nowrap><p>'.$this->getFieldHeader('name').'</p></td>
				<td nowrap><p>'.$this->getFieldHeader('speaker').'</p></td>
				<td nowrap><p>'.$this->getFieldHeader('room').'</p></td>
			</tr>';
	}
	/**
	 * [Put your description here]
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
				foreach(explode(",",$this->internal['currentRow'][$fN]) as $k){
					$data = $this->pi_getRecord('tx_wseevents_speakers',$k);
					if (isset($content)) {
						$content .= '<br />'.$data['name'];
					} else {
						$content = $data['name'];
					}
				}
				return $content;
			break;

			default:
				return $this->internal['currentRow'][$fN];
			break;
		}
	}
	/**
	 * [Put your description here]
	 */
	function getFieldHeader($fN)	{
		switch($fN) {
			
			default:
				return $this->pi_getLL('listFieldHeader_'.$fN,'['.$fN.']');
			break;
		}
	}
	
	/**
	 * [Put your description here]
	 */
	function getFieldHeader_sortLink($fN)	{
		return $this->pi_linkTP_keepPIvars($this->getFieldHeader($fN),array('sort'=>$fN.':'.($this->internal['descFlag']?0:1)));
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wse_events/pi1/class.tx_wseevents_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wse_events/pi1/class.tx_wseevents_pi1.php']);
}

?>