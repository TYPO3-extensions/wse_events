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

//		$flexFormValuesArray['dynListType'] = $this->pi_getFFvalue($piFlexForm, 'dynListType', 'display', $lDef[$index]);	
		$flexFormValuesArray['dynListType'] = $this->pi_getFFvalue($piFlexForm, 'dynListType', 'display', $lDef[0]);
		$conf['pidList'] = $this->pi_getFFvalue($piFlexForm, 'pages', 'sDEF');
		switch((string)$flexFormValuesArray['dynListType'])	{
			case 'sessionlist':
				$conf['recursive'] = $this->cObj->data['recursive'];
				$conf['singleSession'] = $this->pi_getFFvalue($piFlexForm, 'singleSession', 'display');
				$conf['singleSpeaker'] = $this->pi_getFFvalue($piFlexForm, 'singleSpeaker', 'display');
				$conf['sorting'] = $this->pi_getFFvalue($piFlexForm, 'sorting', 'display');
				if ($conf['sorting']=='') { $conf['sorting'] = 'name:0'; }
				$conf['lastnameFirst'] = $this->pi_getFFvalue($piFlexForm, 'lastnameFirst', 'display');
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
				return $this->pi_wrapInBaseClass('Not implemented: ['.(string)$flexFormValuesArray['dynListType'].']<br>Index=['.$index.']<br>');
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

//	    $where = ' AND '.$this->internal['currentTable'].'.sys_language_uid = '.$index;
	    $where = ' AND '.$this->internal['currentTable'].'.sys_language_uid = 0';

		// Get number of records:
		$res = $this->pi_exec_query($this->internal['currentTable'],1,$where);
		list($this->internal['res_count']) = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);

		// Make listing query, pass query to SQL database:
		$res = $this->pi_exec_query($this->internal['currentTable'],0,$where);

		# Check if template file is set, if not use the default template
		if (!isset($conf['templateFile'])) {
			$templateFile = 'EXT:wse_events/template.html';
		} else {
			$templateFile = $conf['templateFile'];
		}
		# Get the template
		$this->templateCode = $this->cObj->fileResource($templateFile);
		
		# Get the parts out of the template
		$template['total'] = $this->cObj->getSubpart($this->templateCode,'###SESSIONLIST###');
		$template['singlerow'] = $this->cObj->getSubpart($template['total'],'###SINGLEROW###');
		$template['header'] = $this->cObj->getSubpart($template['singlerow'],'###HEADER###');
		$template['row'] = $this->cObj->getSubpart($template['singlerow'],'###ITEM###');
		$template['row_alt'] = $this->cObj->getSubpart($template['singlerow'],'###ITEM_ALT###');	

		// Put the whole list together:
		$content_item = '';	// Clear var;

		# Get the column names
		$markerArray['###NUMBER###'] = $this->getFieldHeader('number');
		$markerArray['###NAME###'] = $this->getFieldHeader('name');
		$markerArray['###SPEAKER###'] = $this->getFieldHeader('speaker');
		$markerArray['###ROOM###'] = $this->getFieldHeader('room');
		$markerArray['###TIMESLOTS###'] = $this->getFieldHeader('timeslots');
		$content_item .= $this->cObj->substituteMarkerArrayCached($template['header'], $markerArray);

		$switch_row = 0;
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$this->internal['currentRow'] = $row;
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

			# Build content from template + array
			$markerArray['###NUMBER###'] = $this->getFieldContent('number');
			$markerArray['###TEASER###'] = $this->getFieldContent('teaser');
			$markerArray['###NAME###'] = $sessionname;
			$markerArray['###SPEAKER###'] = $this->getFieldContent('speaker');
			$markerArray['###ROOM###'] = $this->getFieldContent('room');
			$markerArray['###TIMESLOTS###'] = $this->getFieldContent('timeslots');

			$switch_row = $switch_row ^ 1;
			if($switch_row) {
				$content_item .= $this->cObj->substituteMarkerArrayCached($template['row'], $markerArray);
			} else {
				$content_item .= $this->cObj->substituteMarkerArrayCached($template['row_alt'], $markerArray);
			}
		}   
		$subpartArray['###SINGLEROW###'] = $content_item; 

		$content = $this->cObj->substituteMarkerArrayCached($template['total'], array(), $subpartArray);
		return $content;
	}

	/**
	 * Display the details of a single session
	 */
	function singleSessionView($content,$conf)	{
		$this->conf=$conf;
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();
		
		$this->internal['currentRow'] = $this->pi_getRecord($this->internal['currentTable'],$this->piVars['showUid']);

		# Check if template file is set, if not use the default template
		if (!isset($conf['templateFile'])) {
			$templateFile = 'EXT:wse_events/template.html';
		} else {
			$templateFile = $conf['templateFile'];
		}
		# Get the template
		$this->templateCode = $this->cObj->fileResource($templateFile);
		
		# Get the parts out of the template
		$template['total'] = $this->cObj->getSubpart($this->templateCode,'###SESSIONVIEW###');
		
		// This sets the title of the page for use in indexed search results:
		if ($this->internal['currentRow']['title'])	$GLOBALS['TSFE']->indexedDocTitle=$this->internal['currentRow']['title'];

		// Link for back to list view
		$label = $this->pi_getLL('back','Back');  // the link text
		$overrulePIvars = '';
		$clearAnyway=1;    // the current values of piVars will NOT be preserved
		$altPageId=$this->piVars['backUid'];      // ID of the view page
		$backlink = $this->pi_linkTP_keepPIvars($label, $overrulePIvars, $cache, $clearAnyway, $altPageId);
		
		$markerArray['###TITLE###'] = $this->getFieldContent('name');
		$markerArray['###TEASERNAME###'] = $this->getFieldHeader('teaser');
		$markerArray['###TEASERDATA###'] = $this->getFieldContent('teaser');
		$markerArray['###SPEAKERNAME###'] = $this->getFieldHeader('speaker');
		$markerArray['###SPEAKERDATA###'] = $this->getFieldContent('speaker');
		$markerArray['###TIMESLOTSNAME###'] = $this->getFieldHeader('timeslots');
		$markerArray['###TIMESLOTSDATA###'] = $this->getFieldContent('timeslots');
		$markerArray['###DESCRIPTIONNAME###'] = $this->getFieldHeader('description');
		$markerArray['###DESCRIPTIONDATA###'] = $this->getFieldContent('description');
		$markerArray['###BACKLINK###'] = $backlink;
		
#		$this->pi_getEditPanel();
	
		return $this->cObj->substituteMarkerArrayCached($template['total'], $markerArray);;
	}

	/**
	 * Display the details of a single speaker
	 */
	function singleSpeakerView($content,$conf)	{
		$this->conf=$conf;
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();
		
		$this->internal['currentRow'] = $this->pi_getRecord($this->internal['currentTable'],$this->piVars['showUid']);
	
		# Check if template file is set, if not use the default template
		if (!isset($conf['templateFile'])) {
			$templateFile = 'EXT:wse_events/template.html';
		} else {
			$templateFile = $conf['templateFile'];
		}
		# Get the template
		$this->templateCode = $this->cObj->fileResource($templateFile);
		
		# Get the parts out of the template
		$template['total'] = $this->cObj->getSubpart($this->templateCode,'###SPEAKERVIEW###');

		// This sets the title of the page for use in indexed search results:
		if ($this->internal['currentRow']['title'])	$GLOBALS['TSFE']->indexedDocTitle=$this->internal['currentRow']['title'];

		// Link for back to list view
		$label = $this->pi_getLL('back','Back');  // the link text
		$overrulePIvars = '';
		$clearAnyway=1;    // the current values of piVars will NOT be preserved
		$altPageId=$this->piVars['backUid'];      // ID of the view page
		$backlink = $this->pi_linkTP_keepPIvars($label, $overrulePIvars, $cache, $clearAnyway, $altPageId);
		
		$markerArray['###NAME###'] = $this->getFieldContent('name');
		$markerArray['###EMAILNAME###'] = $this->getFieldHeader('email');
		$markerArray['###EMAILDATA###'] = $this->getFieldContent('email');
		$markerArray['###INFONAME###'] = $this->getFieldHeader('info');
		$markerArray['###INFODATA###'] = $this->getFieldContent('info');
		$markerArray['###BACKLINK###'] = $backlink;
		
#		$this->pi_getEditPanel();
	
		return $this->cObj->substituteMarkerArrayCached($template['total'], $markerArray);;
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

			case 'name':
				switch ($this->internal['currentTable']) {
					case 'tx_wseevents_sessions':
						return $this->getTranslatedField('tx_wseevents_sessions', 11, 'name');
					break;
					case 'tx_wseevents_speakers':
						if (isset($this->internal['currentRow']['firstname'])) {
							if (isset($this->conf['lastnameFirst'])) {
								return $this->internal['currentRow']['name'].', '.$this->internal['currentRow']['firstname'];
							} else {
								return $this->internal['currentRow']['firstname'].' '.$this->internal['currentRow']['name'];
							}
						} else {
							return $this->internal['currentRow']['name'];
						}
					break;
					default:
						return $this->internal['currentRow']['name'];
					break;
				}
			break;

			case 'teaser':
				switch ($this->internal['currentTable']) {
					case 'tx_wseevents_sessions':
						return $this->getTranslatedField('tx_wseevents_sessions', 17, $fN);
					break;
					default:
						return $this->internal['currentRow'][$fN];
					break;
				}
			break;

			case 'description':
				switch ($this->internal['currentTable']) {
					case 'tx_wseevents_sessions':
						$data = $this->getTranslatedField('tx_wseevents_sessions', 18, $fN);
						return $this->pi_RTEcssText($data);
					break;
					default:
						return $this->internal['currentRow'][$fN];
					break;
				}
			break;

			case 'room':
				$data = $this->pi_getRecord('tx_wseevents_rooms',$this->internal['currentRow'][$fN]);
				return $data['name'];
			break;

			case 'speaker':
				foreach(explode(',',$this->internal['currentRow'][$fN]) as $k){
					$data = $this->pi_getRecord('tx_wseevents_speakers',$k);

					// Get the name and firstname
					if (isset($data['firstname'])) {
						if (isset($this->conf['lastnameFirst'])) {
							$label =  $data['name'].', '.$data['firstname'];
						} else {
							$label =  $data['firstname'].' '.$data['name'];
						}
					} else {
						$label =  $data['name'];
					}

					if (isset($this->conf['singleSpeaker'])) {
					    $overrulePIvars = '';//array('session' => $this->getFieldContent('uid'));
					    $overrulePIvars = array('showUid' => $data['uid'], 'backUid' => $GLOBALS['TSFE']->id);
					    $clearAnyway=1;    // the current values of piVars will NOT be preserved
					    $altPageId=$this->conf['singleSpeaker'];      // ID of the target page, if not on the same page
					    $speakername = $this->pi_linkTP_keepPIvars($label, $overrulePIvars, $cache, $clearAnyway, $altPageId);
						if (empty($label)) {
							$speakername = '';
						}
					} else {
					    $speakername = $label;
					}
					if (isset($content)) {
						$content .= '<br />'.$speakername;
					} else {
						$content = $speakername;
					}
				}
				if (empty($content)) {
					$content = $this->pi_getLL('tx_wseevents_sessions.nospeakers','[no speaker assigned]');
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

			case 'info':
				switch ($this->internal['currentTable']) {
					case 'tx_wseevents_speakers':
						$data = $this->getTranslatedField('tx_wseevents_speakers', 14, $fN);
						return $this->pi_RTEcssText($data);
					break;
					default:
						return $this->internal['currentRow'][$fN];
					break;
				}
			break;

			default:
				return $this->internal['currentRow'][$fN];
			break;
		}
	}

	/**
	*
	*/
	function getTranslatedField($dbname, $fieldid, $fN) {
		$index = $GLOBALS['TSFE']->sys_language_uid;
		if ($index<>0) {
			// for the name of a session, check if a translation is there
			$where = 'AND l18n_parent='.$this->internal['currentRow']['uid'].' AND sys_language_uid='.$index;
			$res = $this->pi_exec_query($dbname,1,$where);
			list($datacount) = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);
			if ($datacount==1) {
				// a translation is there, get the translated field content
				$res = $this->pi_exec_query($dbname,0,$where);
				$datacat = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);
//			return t3lib_div::view_array($datacat);
				return $datacat[$fieldid];
			} else {
				// no translation get the field content from the default record
				return $this->internal['currentRow'][$fN];
			}
		} else {
			//show default language
			return $this->internal['currentRow'][$fN];
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