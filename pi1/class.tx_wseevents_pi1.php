<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007 Michael Oehlhof <typo3@oehlhof.de>
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
 * @author	Michael Oehlhof <typo3@oehlhof.de>
 */

/**
 * To temporary show some debug output on live web site
 * it can be easyly switched on via a TypoScript setting.
 * plugin.tx_wseevents_pi1.listTimeslotView.debug = 1
 */


require_once(PATH_tslib.'class.tslib_pibase.php');

/*
 * Include Static Info Tables for country selection
 */
require_once(t3lib_extMgm::extPath('static_info_tables').'pi1/class.tx_staticinfotables_pi1.php');

/*
 * Include timeslot class for function to format time slot name
 */
require_once(t3lib_extMgm::extPath('wse_events').'class.tx_wseevents_timeslots.php');


define('TAB', chr(9));
define('LF', chr(10));

class tx_wseevents_pi1 extends tslib_pibase {
	var $prefixId = 'tx_wseevents_pi1';		// Same as class name
	var $scriptRelPath = 'pi1/class.tx_wseevents_pi1.php';	// Path to this script relative to the extension dir.
	var $extKey = 'wse_events';	// The extension key.
	var $pi_checkCHash = TRUE;

	/**
	 * Main function, decides in which form the data is displayed
	 *
	 * @param	[type]		$content: ...
	 * @param	[type]		$conf: ...
	 * @return	[type]		...
	 */
	function main($content,$conf)	{
		$this->pi_initPIflexform(); // Init and get the flexform data of the plugin
		$piFlexForm = $this->cObj->data['pi_flexform'];
		$index = $GLOBALS['TSFE']->sys_language_uid;

		# Get FlexForm data
		$sDef = current($piFlexForm['data']);
		$lDef = array_keys($sDef);

		# Initialize Static Info
		$this->staticInfo = t3lib_div::makeInstance('tx_staticinfotables_pi1');
		$this->staticInfo->init();


		# Read TypoScript settings
		
		# Check if delimiter for speaker is set, if not use the default value
		if (!isset($conf['speakerdelimiter'])) {
			$this->internal['speakerdelimiter'] = '<br />';
		} else {
			$this->internal['speakerdelimiter'] = $conf['speakerdelimiter'];
		}
		# Check if delimiter for slots is set, if not use the default value
		if (!isset($conf['slotdelimiter'])) {
			$this->internal['slotdelimiter'] = '<br />';
		} else {
			$this->internal['slotdelimiter'] = $conf['slotdelimiter'];
		}
		# Check if delimiter for slots is set, if not use the default value
		if (!isset($conf['sessiondelimiter'])) {
			$this->internal['sessiondelimiter'] = '<br />';
		} else {
			$this->internal['sessiondelimiter'] = $conf['sessiondelimiter'];
		}

		$flexFormValuesArray['dynListType'] = $this->pi_getFFvalue($piFlexForm, 'dynListType', 'display', $lDef[0]);
		$conf['pidListEvents'] = $this->pi_getFFvalue($piFlexForm, 'pages', 'sDEF');
		$conf['pidListCommon'] = $this->pi_getFFvalue($piFlexForm, 'commonpages', 'sDEF');
		$conf['recursive'] = $this->cObj->data['recursive'];
		$conf['singleSession'] = $this->pi_getFFvalue($piFlexForm, 'singleSession', 'display');
		$conf['singleSpeaker'] = $this->pi_getFFvalue($piFlexForm, 'singleSpeaker', 'display');
		$conf['lastnameFirst'] = $this->pi_getFFvalue($piFlexForm, 'lastnameFirst', 'display');
//			return t3lib_div::view_array($conf);

		# Show input page depend on selected tab
		switch((string)$flexFormValuesArray['dynListType'])	{
			case 'sessionlist':
				$conf['pidList'] = $conf['pidListEvents'];
				return $this->pi_wrapInBaseClass($this->listSessionView($content,$conf));
			break;
			case 'sessiondetail':
				// Set table to session table
				$this->internal['currentTable'] = 'tx_wseevents_sessions';
				$this->internal['currentRow']=$this->piVars['showSessionUid'];
				return $this->pi_wrapInBaseClass($this->singleSessionView($content,$conf));
			break;
			case 'speakerlist':
				$conf['pidList'] = $conf['pidListCommon'];
				return $this->pi_wrapInBaseClass($this->listSpeakerView($content,$conf));
			break;
			case 'speakerdetail':
				$this->internal['currentTable'] = 'tx_wseevents_speakers';
				$this->internal['currentRow']=$this->piVars['showSpeakerUid'];
				return $this->pi_wrapInBaseClass($this->singleSpeakerView($content,$conf));
			break;
			case 'timeslots':
				return $this->pi_wrapInBaseClass($this->listTimeslotView($content,$conf));
			break;
			default:
				return $this->pi_wrapInBaseClass('Not implemented: ['.(string)$flexFormValuesArray['dynListType'].']<br>Index=['.$index.']<br>');
			break;
		}
	}






	/**
	 * Display a list of sessions for the event that is set in the flex form settings
	 *
	 * @param	[type]		$content: ...
	 * @param	[type]		$conf: ...
	 * @return	[type]		...
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

		# Check if template file is set, if not use the default template
		if (!isset($conf['templateFile'])) {
			$templateFile = 'EXT:wse_events/wseevents.tmpl';
		} else {
			$templateFile = $conf['templateFile'];
		}
		# Get the template
		$this->templateCode = $this->cObj->fileResource($templateFile);

		# Get the parts out of the template
		$template['total'] = $this->cObj->getSubpart($this->templateCode,'###SESSIONLIST###');
		$template['catsection'] = $this->cObj->getSubpart($template['total'],'###CATEGORYSELECT###');
		$template['catselect'] = $this->cObj->getSubpart($template['catsection'],'###SELECT###');
		$template['catoption'] = $this->cObj->getSubpart($template['catselect'],'###OPTIONNOTSELECTED###');
		$template['catoptionsel'] = $this->cObj->getSubpart($template['catselect'],'###OPTIONSELECTED###');
		$template['evtsection'] = $this->cObj->getSubpart($template['total'],'###EVENTSELECT###');
		$template['evtselect'] = $this->cObj->getSubpart($template['evtsection'],'###SELECT###');
		$template['evtoption'] = $this->cObj->getSubpart($template['evtselect'],'###OPTIONNOTSELECTED###');
		$template['evtoptionsel'] = $this->cObj->getSubpart($template['evtselect'],'###OPTIONSELECTED###');
		$template['singlerow'] = $this->cObj->getSubpart($template['total'],'###SINGLEROW###');
		$template['header'] = $this->cObj->getSubpart($template['singlerow'],'###HEADER###');
		$template['row'] = $this->cObj->getSubpart($template['singlerow'],'###ITEM###');
		$template['row_alt'] = $this->cObj->getSubpart($template['singlerow'],'###ITEM_ALT###');

		# Initializing the query parameters:
		$sorting = $this->conf['sorting'];
//		list($this->internal['orderBy'],$this->internal['descFlag']) = explode(':',$sorting);
		$this->internal['results_at_a_time']=t3lib_div::intInRange($lConf['results_at_a_time'],0,1000,100);		// Number of results to show in a listing.
		$this->internal['maxPages']=t3lib_div::intInRange($lConf['maxPages'],0,1000,2);;		// The maximum number of "pages" in the browse-box: "Page 1", 'Page 2', etc.
		$this->internal['searchFieldList']='uid,name,category,number,speaker,room,timeslots,teaser';
		$this->internal['orderByList']='category,number,name';
	    $where = ' AND '.$this->internal['currentTable'].'.sys_language_uid = 0';

		# Check for catagory selection
		$showcat = $this->piVars['showCategory'];
		if (!empty($showcat)) {
			$where .= ' AND category='.$showcat;
		} else {
			$showcat = 0;
		}

		# Check for hidden catagories
		$hidecat = $conf['showSessionList.']['hideCategories'];
		if (empty($hidecat)) {
			$hidecat = 0;
		}


		# Check for event selection in URL
		$showevent = $this->piVars['showEvent'];
		if (empty($showevent)) {
			$showevent = 0;
		}

		# Check for amount of events
		$this->conf['pidList'] = $this->conf['pidListEvents'];
	    $where1 = ' AND sys_language_uid = 0';
		$res = $this->pi_exec_query('tx_wseevents_events',1,$where1,'','','name,uid');
		list($eventcount) = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);

		# Create template data for event combobox
		$event_item = '';	// Clear var;
		$markerArray = array();
		// Make listing query, pass query to SQL database:
		$res = $this->pi_exec_query('tx_wseevents_events',0,$where1);
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			# Take the first event as selected if no event is selected in the URL
			if ($showevent==0) {
				$showevent = $row['uid'];
			}
			$eventname = $this->getTranslatedCategory('tx_wseevents_events', $row['uid'], $row['name']);
			# Set one event option
			$markerArray['###VALUE###'] = $row['uid'];
			$markerArray['###OPTION###'] = $eventname;
			if ($showevent==$row['uid']) {
				$event_item .= $this->cObj->substituteMarkerArrayCached($template['evtoptionsel'], $markerArray);
			} else {
				$event_item .= $this->cObj->substituteMarkerArrayCached($template['evtoption'], $markerArray);
			}
		}
		# Show selection combo box if more than one event is found
		if ($eventcount>1) {
			# Set select options
			$subpartArray1['###SELECT###'] = $event_item;
			# Set label for selection box
			$markerArray1['###LABEL###'] = $this->pi_getLL('tx_wseevents_sessions.chooseeventday','[Choose event day]');
			//$markerArray1['###FORMACTION###'] = $this->pi_getPageLink($GLOBALS['TSFE']->page['uid']);
			$markerArray1['###FORMSELECT###'] = $this->prefixId.'[showEvent]';
			$markerArray1['###FORMSEND###'] = htmlspecialchars($this->pi_getLL('tx_wseevents_sessions.showselection','[Show selection]'));
			$subpartArray['###EVENTSELECT###'] = $this->cObj->substituteMarkerArrayCached($template['evtsection'], $markerArray1, $subpartArray1);
		} else {
			$subpartArray['###EVENTSELECT###'] = '';
		}
		# show only sessions of selected event
		if ($showevent>0) {
			$where .= ' AND event='.$showevent;
		}


		# Create template data for category combobox
		$select_item = '';	// Clear var;
		$markerArray = array();
		$markerArray['###VALUE###'] = 0;
		$markerArray['###OPTION###'] = $this->pi_getLL('tx_wseevents_sessions.chooseall','[-All-]');
		if ($showcat==0) {
			$select_item .= $this->cObj->substituteMarkerArrayCached($template['catoptionsel'], $markerArray);
		} else {
			$select_item .= $this->cObj->substituteMarkerArrayCached($template['catoption'], $markerArray);
		}
		// Get list of categories
		// Make query, pass query to SQL database:
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tx_wseevents_categories', 'deleted=0 AND hidden=0 AND sys_language_uid=0', '', 'shortkey');
		if ($res) {
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				if (!t3lib_div::inList($hidecat,$row['uid'])) {
					$catname = $this->getTranslatedCategory('tx_wseevents_categories', $row['uid'], $row['name']);
					# Set one category option
					$markerArray['###VALUE###'] = $row['uid'];
					$markerArray['###OPTION###'] = $row['shortkey'].' - '.$catname;
					if ($showcat==$row['uid']) {
						$select_item .= $this->cObj->substituteMarkerArrayCached($template['catoptionsel'], $markerArray);
					} else {
						$select_item .= $this->cObj->substituteMarkerArrayCached($template['catoption'], $markerArray);
					}
				}
			}
		}
		# Set select options
		$subpartArray1['###SELECT###'] = $select_item;
		# Set label for selection box
		$markerArray1['###LABEL###'] = $this->pi_getLL('tx_wseevents_sessions.choosecategory','[Choose category]');
		//$markerArray1['###FORMACTION###'] = $this->pi_getPageLink($GLOBALS['TSFE']->page['uid']);
		$markerArray1['###FORMSELECT###'] = $this->prefixId.'[showCategory]';
		$markerArray1['###FORMSEND###'] = htmlspecialchars($this->pi_getLL('tx_wseevents_sessions.showselection','[Show selection]'));
		$subpartArray['###CATEGORYSELECT###'] = $this->cObj->substituteMarkerArrayCached($template['catsection'], $markerArray1, $subpartArray1);

		# Get number of records:
		$this->conf['pidList'] = $this->conf['pidListEvents'];
		$res = $this->pi_exec_query($this->internal['currentTable'],1,$where,'','','category,number,name');
		list($this->internal['res_count']) = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);

		// Make listing query, pass query to SQL database:
		$res = $this->pi_exec_query($this->internal['currentTable'],0,$where,'','','category,number,name');

		# Get the column names
		$content_item = '';	// Clear var;
		$markerArray = array();
		$markerArray['###NUMBER###'] = $this->getFieldHeader('number');	// ToDo: To be removed from here before release
		$markerArray['###SESSIONNUMBER###'] = $this->getFieldHeader('number');
		$markerArray['###NAME###'] = $this->getFieldHeader('name');
		$markerArray['###SPEAKER###'] = $this->getFieldHeader('speaker');
#		$markerArray['###ROOM###'] = $this->getFieldHeader('room');
		$markerArray['###TIMESLOTS###'] = $this->getFieldHeader('timeslots');
		$content_item .= $this->cObj->substituteMarkerArrayCached($template['header'], $markerArray);

		$switch_row = 0;
		if ($res) {
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$this->internal['currentRow'] = $row;
				if (!t3lib_div::inList($hidecat,$row['category'])) {
					if (!empty($this->conf['singleSession'])) {
					    $label = $this->getFieldContent('name');  // the link text
					    $overrulePIvars = '';//array('session' => $this->getFieldContent('uid'));
					    $overrulePIvars = array('showSessionUid' => $this->internal['currentRow']['uid'], 'backUid' => $GLOBALS['TSFE']->id, 'back2list' => '1');
					    $clearAnyway=1;    // the current values of piVars will NOT be preserved
					    $altPageId=$this->conf['singleSession'];      // ID of the target page, if not on the same page
					    $sessionname = $this->pi_linkTP_keepPIvars($label, $overrulePIvars, $cache, $clearAnyway, $altPageId);
					} else {
						$sessionname = $this->getFieldContent('name');
					}

					# Build content from template + array
					$markerArray = array();
					$markerArray['###NUMBER###'] = $this->getFieldContent('number');	// ToDo: To be removed from here before release
					$markerArray['###TEASERNAME##'] = $this->getFieldHeader('teaser');
					$markerArray['###TEASERDATA###'] = $this->getFieldContent('teaser');
					$markerArray['###DESCRIPTIONNAME###'] = $this->getFieldHeader('description');
					$markerArray['###DESCRIPTIONDATA###'] = $this->getFieldContent('description');
					$markerArray['###NAME###'] = $sessionname;
					$markerArray['###SPEAKER###'] = $this->getFieldContent('speaker');
#					$markerArray['###ROOM###'] = $this->getFieldContent('room');
					$markerArray['###TIMESLOTS###'] = $this->getFieldContent('timeslots');

					$markerArray['###SESSIONNUMBER###'] = $this->getFieldContent('number');
					$datacat  = $this->pi_getRecord('tx_wseevents_categories',$this->getFieldContent('category'));
					$markerArray['###SESSIONCATEGORY###'] = $this->getFieldContent('category');
					$markerArray['###SESSIONCATEGORYKEY###'] = $datacat['shortkey'];
					$markerArray['###SESSIONCATEGORYCOLOR###'] = $datacat['color'];

					$switch_row = $switch_row ^ 1;
					if($switch_row) {
						$content_item .= $this->cObj->substituteMarkerArrayCached($template['row'], $markerArray);
					} else {
						$content_item .= $this->cObj->substituteMarkerArrayCached($template['row_alt'], $markerArray);
					}
				}
			}
		}
		$subpartArray['###SINGLEROW###'] = $content_item;

		$content .= $this->cObj->substituteMarkerArrayCached($template['total'], array(), $subpartArray);
		return $content;
	}










	/**
	 * Display a list of speakers for the event that is set in the flex form settings
	 *
	 * @param	[type]		$content: ...
	 * @param	[type]		$conf: ...
	 * @return	[type]		...
	 */
	function listSpeakerView($content,$conf)	{
		$this->conf=$conf;		// Setting the TypoScript passed to this function in $this->conf
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();		// Loading the LOCAL_LANG values
		$index = $GLOBALS['TSFE']->sys_language_uid;

		$lConf = $this->conf['listView.'];	// Local settings for the listView function

		// Set table to session table
		$this->internal['currentTable'] = 'tx_wseevents_speakers';

		if (!isset($this->piVars['pointer']))	$this->piVars['pointer']=0;
		if (!isset($this->piVars['mode']))	$this->piVars['mode']=1;

		// Initializing the query parameters:
		$sorting = $this->conf['sorting'];
//		list($this->internal['orderBy'],$this->internal['descFlag']) = explode(':',$sorting);
		$this->internal['results_at_a_time']=t3lib_div::intInRange($lConf['results_at_a_time'],0,1000,100);		// Number of results to show in a listing.
		$this->internal['maxPages']=t3lib_div::intInRange($lConf['maxPages'],0,1000,2);;		// The maximum number of "pages" in the browse-box: "Page 1", 'Page 2', etc.
		$this->internal['searchFieldList']='name,firstname,email,info,uid';
		$this->internal['orderByList']='name,firstname,email,info,uid';
		$this->internal['orderBy']='name,firstname';
		$this->internal['descFlag']=0;
		// Check for setting sort order via TypoScript
		if (isset($this->conf['sortSpeakerlist'])) {
			list($this->internal['orderBy'],$this->internal['descFlag']) = explode(':',$this->conf['sortSpeakerlist']);
		}

	    $where = ' AND '.$this->internal['currentTable'].'.sys_language_uid = 0';

		// Get number of records:
		$res = $this->pi_exec_query($this->internal['currentTable'],1,$where,'','','name');
		list($this->internal['res_count']) = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);

		// Make listing query, pass query to SQL database:
		$res = $this->pi_exec_query($this->internal['currentTable'],0,$where);

		# Check if upload directory is set, if not use the default directory
		if (!isset($conf['uploadDirectory'])) {
			$uploadDirectory = 'uploads/tx_wseevents';
		} else {
			$uploadDirectory = $conf['uploadDirectory'];
		}

		# Check if template file is set, if not use the default template
		if (!isset($conf['templateFile'])) {
			$templateFile = 'EXT:wse_events/wseevents.tmpl';
		} else {
			$templateFile = $conf['templateFile'];
		}
		# Get the template
		$this->templateCode = $this->cObj->fileResource($templateFile);

		# Get the parts out of the template
		$template['total']      = $this->cObj->getSubpart($this->templateCode,   '###SPEAKERLIST###');
		$template['header']     = $this->cObj->getSubpart($template['total'],    '###HEADER###');
		$template['singlerow']  = $this->cObj->getSubpart($template['total'],    '###SINGLEROW###');
		$template['row']        = $this->cObj->getSubpart($template['singlerow'],'###ITEM###');
		$template['row_alt']    = $this->cObj->getSubpart($template['singlerow'],'###ITEM_ALT###');
		$template['sessionrow'] = $this->cObj->getSubpart($template['singlerow'],'###SESSIONROW###');

		// Put the whole list together:
		$content_item = '';	// Clear var;

		# Get the column names
		$markerArray0 = Array();
		$markerArray0['###NAME###']         = $this->getFieldHeader('name');
		$markerArray0['###EMAILNAME###']    = $this->getFieldHeader('email');
		$markerArray0['###COUNTRYNAME###']  = $this->getFieldHeader('country');
		$markerArray0['###COMPANYNAME###']  = $this->getFieldHeader('company');
		$markerArray0['###INFONAME###']     = $this->getFieldHeader('info');
		$markerArray0['###IMAGENAME###']    = $this->getFieldHeader('image');
		$markerArray0['###SESSIONSNAME###'] = $this->getFieldHeader('speakersessions');
		$subpartArray['###HEADER###']       = $this->cObj->substituteMarkerArrayCached($template['header'], $markerArray0);

		$switch_row = 0;
		$content = '';
		if ($res) {
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$this->internal['currentRow'] = $row;
				# Check if the speaker has a session on this event
				$sessionids = $this->getSpeakerSessionList($this->internal['currentRow']['uid'],$this->conf['pidListEvents']);
#$content .= '<br>SessionIDs=['.$sessionids.']';

				# display only speaker with sessions
				if (!empty($sessionids)) {
					# Check if link to detail view is set
					if (!empty($this->conf['singleSpeaker'])) {
					    $label = $this->getFieldContent('name');  // the link text
					    $overrulePIvars = '';//array('session' => $this->getFieldContent('uid'));
					    $overrulePIvars = array('showSpeakerUid' => $this->internal['currentRow']['uid'], 'backUid' => $GLOBALS['TSFE']->id);
					    $clearAnyway=1;    // the current values of piVars will NOT be preserved
					    $altPageId=$this->conf['singleSpeaker'];      // ID of the target page, if not on the same page
					    $speakername = $this->pi_linkTP_keepPIvars($label, $overrulePIvars, $cache, $clearAnyway, $altPageId);
					} else {
						$speakername = $this->getFieldContent('name');
					}

					// remember sessionids for getFieldContent
					$this->internal['speakersessions'] = $sessionids;

					# Build content from template + array
					$markerArray = Array();
					$markerArray['###NAME###'] = $speakername;
					$markerArray['###IMAGENAME###'] = $this->getFieldContent('name');
					$markerArray['###EMAILNAME###'] = $this->getFieldHeader('email');
					$markerArray['###EMAILDATA###'] = $this->getFieldContent('email');
					$markerArray['###COUNTRYNAME###'] = $this->getFieldHeader('country');
					$markerArray['###COUNTRYDATA###'] = $this->getFieldContent('country');
					$markerArray['###COMPANYNAME###'] = $this->getFieldHeader('company');
					$markerArray['###COMPANYDATA###'] = $this->getFieldContent('company');
					$markerArray['###COMPANYLINK###'] = 'http://'.$this->getFieldContent('companylink');
					$markerArray['###SESSIONSNAME###'] = $this->getFieldHeader('speakersessions');
					$markerArray['###SESSIONS###'] = $this->getFieldContent('speakersessions');
					$markerArray['###INFONAME###'] = $this->getFieldHeader('info');
					$markerArray['###INFODATA###'] = $this->getFieldContent('info');
					$markerArray['###IMAGENAME###'] = $this->getFieldHeader('image');
					$image = trim($this->getFieldContent('image'));
					if (!empty($image)) {
						$img = $this->conf['image.'];
						if (empty($img)) {
						    $img['file'] = 'GIFBUILDER';
							$img['file.']['XY'] = '100,150';
							$img['file.']['5'] = 'IMAGE';
						}
						$img['file.']['5.']['file'] = $uploadDirectory.'/'.$image;
						$markerArray['###IMAGELINK###'] = $this->cObj->IMAGE($img);
						$markerArray['###IMAGEFILE###'] = $uploadDirectory.'/'.$image;
					} else {
						$markerArray['###IMAGELINK###'] = '';
						$markerArray['###IMAGEFILE###'] = '';
					}

					# For every session get information
					$sess_content_item = '';
					foreach(explode(',',$sessionids) as $k){
						$label = $this->getTranslatedField('tx_wseevents_sessions', 'name', $k);
						if (!empty($this->conf['singleSession'])) {
							$overrulePIvars = '';//array('session' => $this->getFieldContent('uid'));
							$overrulePIvars = array('showSessionUid' => $k, 'backUid' => $GLOBALS['TSFE']->id, 'back2list' => '1');
							$clearAnyway=1;    // the current values of piVars will NOT be preserved
							$altPageId=$this->conf['singleSession'];      // ID of the target page, if not on the same page
							$sessionname = $this->pi_linkTP_keepPIvars($label, $overrulePIvars, $cache, $clearAnyway, $altPageId);
						} else {
							$sessionname = $label;
						}

						# Build content from template + array
						$markerarray1 = array();
						$markerArray1['###SESSIONNAME###'] = $sessionname;
						$markerArray1['###SESSIONTEASER###'] = $this->getTranslatedField('tx_wseevents_sessions', 'teaser', $k);//$data['teaser'];
						$markerArray1['###SESSIONDESCRIPTION###'] = $this->getTranslatedField('tx_wseevents_sessions', 'description', $k);//$data['description'];
						$sessdata = $this->pi_getRecord('tx_wseevents_sessions', $k);
						$datacat  = $this->pi_getRecord('tx_wseevents_categories',$sessdata['category']);
						$markerArray1['###SESSIONNUMBER###'] = $datacat['shortkey'].sprintf('%02d', $sessdata['number']);
						$markerArray1['###SESSIONCATEGORY###'] = $sessdata['category'];
						$markerArray1['###SESSIONCATEGORYKEY###'] = $datacat['shortkey'];
						$markerArray1['###SESSIONCATEGORYCOLOR###'] = $datacat['color'];
						// Get time slot info
						$tscontent = '';
						foreach(explode(',',$sessdata['timeslots']) as $ts){
							$tsdata = $this->pi_getRecord('tx_wseevents_timeslots',$ts);
						    $timeslotname = tx_wseevents_timeslots::formatSlotName($tsdata);
							if (!empty($tscontent)) {
								$tscontent .= $this->internal['slotdelimiter'].$timeslotname;
							} else {
								$tscontent = $timeslotname;
							}
						}
						$markerArray1['###SESSIONSLOTS###'] = $tscontent;

						$sess_content_item .= $this->cObj->substituteMarkerArrayCached($template['sessionrow'], $markerArray1);
					}
					$subpartArraySession['###SESSIONROW###'] = $sess_content_item;
					if ($switch_row==0) {
						$content_item .= $this->cObj->substituteMarkerArrayCached($template['row'], $markerArray, $subpartArraySession);
					} else {
						$content_item .= $this->cObj->substituteMarkerArrayCached($template['row_alt'], $markerArray, $subpartArraySession);
					}
					if (!empty($template['row_alt'])) {
						$switch_row = $switch_row ^ 1;
					}
				}
			}
		}
		$subpartArray['###SINGLEROW###'] = $content_item;

		$content .= $this->cObj->substituteMarkerArrayCached($template['total'], $markerArray0, $subpartArray);
		return $content;
	}








	/**
	 * Display a list of time slots for the event that is set in the flex form settings
	 *
	 * @param	[type]		$content: ...
	 * @param	[type]		$conf: ...
	 * @return	[type]		...
	 */
	function listTimeslotView($content,$conf)	{
		$this->conf=$conf;		// Setting the TypoScript passed to this function in $this->conf
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();		// Loading the LOCAL_LANG values
		$index = $GLOBALS['TSFE']->sys_language_uid;

		$lConf = $this->conf['listView.'];	// Local settings for the listView function

		// Set table to session table
		$this->internal['currentTable'] = 'tx_wseevents_sessions';

		if (!isset($this->piVars['pointer']))	$this->piVars['pointer']=0;
		if (!isset($this->piVars['mode']))	$this->piVars['mode']=1;

		# Check for event day selection
		$showday = $this->piVars['showDay'];

		# Check for hidden catagory links
		$hidecat = $conf['listTimeslotView.']['hideCategoryLinks'];
		if (empty($hidecat)) {
			$hidecat = 0;
		}

		# Check for hidden display of "not assigned"
		$hidenotassigned = $conf['listTimeslotView.']['hideNotAssigned'];
		if (empty($hidenotassigned)) {
			$hidenotassigned = 0;
		}

		# Check for hidden display of "not defined"
		$hidenotdefined = $conf['listTimeslotView.']['hideNotDefined'];
		if (empty($hidenotdefined)) {
			$hidenotdefined = 0;
		}

		# Check for hidden display of "Time"
		$hidetime = $conf['listTimeslotView.']['hideShowTime'];
		if (empty($hidetime)) {
			$hidetime = 0;
		}

		# Check for compact display of begin and end of sessions
		$roomtime = $conf['listTimeslotView.']['showRoomTime'];
		if (empty($roomtime)) {
			$roomtime = 0;
		}

		# Check for not assigned time slot color
		$catcolor_notassigned = $conf['listTimeslotView.']['categoryColorNotAssigned'];
		if (empty($catcolor_notassigned)) {
			$catcolor_notassigned = '#FFFFFF';
		}
		# Check for not defined time slot color
		$catcolor_notdefined = $conf['listTimeslotView.']['categoryColorNotDefined'];
		if (empty($catcolor_notdefined)) {
			$catcolor_notdefined = '#FFFFFF';
		}

		# For debugging output used in development
		$timecolwidth = $conf['listTimeslotView.']['timeColWidth'];
		if (empty($timecolwidth)) {
			$timecolwidth = 0;
		}

		# For debugging output used in development
		$showdebug = $conf['listTimeslotView.']['debug'];
		if (empty($showdebug)) {
			$showdebug = 0;
		}

		# Check if template file is set, if not use the default template
		if (!isset($conf['templateFile'])) {
			$templateFile = 'EXT:wse_events/wseevents.tmpl';
		} else {
			$templateFile = $conf['templateFile'];
		}
		# Get the template
		$this->templateCode = $this->cObj->fileResource($templateFile);

		# Get the parts out of the template
		$template['total']     = $this->cObj->getSubpart($this->templateCode,'###SLOTSDAY###');
		if ((empty($template['total'])) or ($showday==0)) {
			$template['total']     = $this->cObj->getSubpart($this->templateCode,'###SLOTSALL###');
		}
		$template['titlerow']       = $this->cObj->getSubpart($template['total'],     '###TITLEROW###');
		$template['evtsection']     = $this->cObj->getSubpart($template['total'],     '###EVENTSELECT###');
		$template['evtselect']      = $this->cObj->getSubpart($template['evtsection'],'###SELECT###');
		$template['evtoption']      = $this->cObj->getSubpart($template['evtselect'], '###OPTIONNOTSELECTED###');
		$template['evtoptionsel']   = $this->cObj->getSubpart($template['evtselect'], '###OPTIONSELECTED###');
		$template['select']         = $this->cObj->getSubpart($template['titlerow'],  '###SELECT###');
		$template['option']         = $this->cObj->getSubpart($template['select'],    '###OPTIONNOTSELECTED###');
		$template['optionsel']      = $this->cObj->getSubpart($template['select'],    '###OPTIONSELECTED###');
		$template['titlecol']       = $this->cObj->getSubpart($template['titlerow'],  '###TITLECOLUMN###');
		$template['headerrow']      = $this->cObj->getSubpart($template['total'],     '###HEADERROW###');
		$template['headercol']      = $this->cObj->getSubpart($template['headerrow'], '###HEADERCOLUMN###');
		$template['slotrow']        = $this->cObj->getSubpart($template['total'],     '###SLOTROW###');
		$template['timecol']        = $this->cObj->getSubpart($template['slotrow'],   '###TIMECOLUMN###');
		$template['timecolfree']    = $this->cObj->getSubpart($template['slotrow'],   '###TIMECOLUMNEMPTY###');
		$template['slotcol']        = $this->cObj->getSubpart($template['slotrow'],   '###SLOTCOLUMN###');
		$template['slotcolempty']   = $this->cObj->getSubpart($template['slotrow'],   '###SLOTCOLUMNEMPTY###');

		# Check for event selection in URL
		$showevent = $this->piVars['showEvent'];
		if (empty($showevent)) {
			$showevent = 0;
		}

		# Check for amount of events
		$this->conf['pidList'] = $this->conf['pidListEvents'];
	    $where1 = ' AND sys_language_uid = 0';
		$res = $this->pi_exec_query('tx_wseevents_events',1,$where1,'','','name,uid');
		list($eventcount) = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);

		# Create template data for event combobox
		$event_item = '';	// Clear var;
		$markerArray = array();
		// Make listing query, pass query to SQL database:
		$res = $this->pi_exec_query('tx_wseevents_events',0,$where1);
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			# Take the first event as selected if no event is selected in the URL
			if ($showevent==0) {
				$showevent = $row['uid'];
			}
			$eventname = $this->getTranslatedCategory('tx_wseevents_events', $row['uid'], $row['name']);
			# Set one event option
			$markerArray['###VALUE###'] = $row['uid'];
			$markerArray['###OPTION###'] = $eventname;
			if ($showevent==$row['uid']) {
				$event_item .= $this->cObj->substituteMarkerArrayCached($template['evtoptionsel'], $markerArray);
			} else {
				$event_item .= $this->cObj->substituteMarkerArrayCached($template['evtoption'], $markerArray);
			}
		}
		# Show selection combo box if more than one event is found
		if ($eventcount>1) {
			# Set select options
			$subpartArray1['###SELECT###'] = $event_item;
			# Set label for selection box
			$markerArray1['###LABEL###'] = $this->pi_getLL('tx_wseevents_sessions.chooseeventday','[Choose event day]');
			//$markerArray1['###FORMACTION###'] = $this->pi_getPageLink($GLOBALS['TSFE']->page['uid']);
			$markerArray1['###FORMSELECT###'] = $this->prefixId.'[showEvent]';
			$markerArray1['###FORMSEND###'] = htmlspecialchars($this->pi_getLL('tx_wseevents_sessions.showselection','[Show selection]'));
			$subpartArray['###EVENTSELECT###'] = $this->cObj->substituteMarkerArrayCached($template['evtsection'], $markerArray1, $subpartArray1);
		} else {
			$subpartArray['###EVENTSELECT###'] = '';
		}
		# show only sessions of selected event
		if ($showevent>0) {
			$where .= ' AND event='.$showevent;
		}
		# Get event info
		$event = $this->getEventInfo($showevent);

		# Create template data for eventday combobox
		$content_select = '';	// Clear var;
		$markerArray['###VALUE###'] = 0;
		$markerArray['###OPTION###'] = $this->pi_getLL('tx_wseevents_sessions.choosealldays','[-All-]');
		if ($showday==0) {
			$content_select .= $this->cObj->substituteMarkerArrayCached($template['optionsel'], $markerArray);
		} else {
			$content_select .= $this->cObj->substituteMarkerArrayCached($template['option'], $markerArray);
		}

		# Get date format for selected language
#$content .= t3lib_div::view_array($conf);
#$content .= 'index='.$index.'<br>';
#$content .= 'conf fmtDate='.$conf[$index.'.']['fmtDate'].'<br>';
		if (!$conf[$index.'.']['fmtDate']){
			$conf['strftime'] = '%d.%m.%Y';
		} else {
			$conf['strftime'] = $conf[$index.'.']['fmtDate'];
		}
#$content .= 'conf strftime='.$conf['strftime'].'<br>';
		# Get count of days and name of days
		$secofday = 60*60*24;
		$daycount = $event['length'];
		for ( $d = 1; $d <= $daycount; $d++ ) {
			$thisday = $event['begin']+($d-1)*$secofday;
#ToDo: Mit TYPO3 den Wochentag ermitteln und das Datum formatieren
#			setlocale(LC_TIME, 'de_DE');

			$dayname[$d] = strftime($conf['strftime'], $thisday);
			$weekdays[$d] = strftime('%A', $thisday);

			# Set one event day  option
			$markerArray['###VALUE###'] = $d;
			$markerArray['###OPTION###'] = $weekdays[$d].' - '.$dayname[$d];
			if ($showday==$d) {
				$content_select .= $this->cObj->substituteMarkerArrayCached($template['optionsel'], $markerArray);
			} else {
				$content_select .= $this->cObj->substituteMarkerArrayCached($template['option'], $markerArray);
			}
		}

		# Get count of rooms and name of rooms
		$rooms = $this->getRoomInfo($event['location']);
		$roomcount = count($rooms);
		$roomids = '';
		for ( $r = 1; $r <= $roomcount; $r++ ) {
			$roomname[$r] = $rooms[$r]['name'];
			if (empty($roomids)) {
				$roomids = $rooms[$r]['uid'];
			} else {
				$roomids .= ','.$rooms[$r]['uid'];
			}
		}
#$content .= t3lib_div::view_array($GLOBALS['TSFE']->config['config']);
#$content .= t3lib_div::view_array($GLOBALS['TSFE']);
		# Create a list with the times of slot begins
		$timeoffsetGMT = date('O');
		$timeoffset = date('Z');
		# Get begin of slots
		$timebegin = $event['timebegin'];
		list($t_hr,$t_min) = explode(':',$timebegin);
#		$t_start = ($t_hr*60 +$t_min)*60;
		$t_start = strtotime($timebegin);
		# Get end of slots
		$timeend   = $event['timeend'];
		list($t_hr,$t_min) = explode(':',$timeend);
#		$t_end = ($t_hr*60 +$t_min)*60;
		$t_end = strtotime($timeend);
		# Get count of slots
		$slotlen = $event['slotsize']*60;
		$slotcount = ($t_end - $t_start)/$slotlen;
		for ( $s = 1; $s <= $slotcount+1; $s++ ) {
			$slotname[$s] = 'Slot '.$s;
			$slotbegin[$s] = date('H:i', (($s-1)*$slotlen+$t_start));
			// %H:%M
		}

#$content .= '<br>Zeitzone'.date('T Z O').'<br>';
#$content .= 'Start='.$timebegin.'<br>Sekunden='.$t_start.'<br>';
#$content .= 'Ende='.$timeend.'<br>Sekunden='.$t_end.'<br>';
#$content .= 'Slotlen='.$slotlen.'<br>Slotcount='.$slotcount.'<br>';

		// Calculate column width if enabled
		if ($timecolwidth>0) {
			if ($showday==0) {
				$columncount = $daycount * $roomcount;
			} else {
				$columncount = $roomcount;
			}
			$slotcolwidth = (100 - $timecolwidth) / $columncount;
		}

		# Here the output begins
		$content_title = '';
		$content_header = '';
		$content_slot = '';
		# Loop over all days
		for ( $d = 1; $d <= $daycount; $d++ ) {
			if (($showday==$d) or ($showday==0)) {
				$markerArray = array();
				$markerArray['###ROOMCOUNT###'] = $roomcount;
				$markerArray['###TITLEDAY###'] = $dayname[$d];
				$markerArray['###TITLEWEEKDAY###'] = $weekdays[$d];
				// Add column width if enabled
				if ($timecolwidth>0) {
					$markerArray['###COLUMNWIDTH###']  = ($slotcolwidth * $roomcount).'%';
				}
				$content_title .= $this->cObj->substituteMarkerArrayCached($template['titlecol'], $markerArray);

				# Loop over all rooms
				for ( $r = 1; $r <= $roomcount; $r++ ) {
					$markerArray = array();
					$markerArray['###HEADERROOM###'] = $roomname[$r];
					// Add column width if enabled
					if ($timecolwidth>0) {
						$markerArray['###COLUMNWIDTH###']  = $slotcolwidth.'%';
					}
					$content_header .= $this->cObj->substituteMarkerArrayCached($template['headercol'], $markerArray);
				}
			}
		}

		# Loop over all slots of a day
		for ( $s = 1; $s <= $slotcount; $s++ ) {
			$content_slotrow = '';
			# Loop over all days
			for ( $d = 1; $d <= $daycount; $d++ ) {
				if (($showday==$d) or ($showday==0)) {
					# Loop over all rooms
					$allrooms = false;
					for ( $r = 1; $r <= $roomcount; $r++ ) {
						if ($showdebug>0) {
							$content_slotrow .= LF.'<!-- s='.$s.' d='.$d.' r='.$r.' -->';
						}
						$slot_id = $this->getSlot($showevent, $d, $rooms[$r]['uid'], $s);
						if (empty($slot_id) && !$allrooms) {
							// Check if a slot is assigned for all rooms
							$slot_id = $this->getSlot($showevent, $d, 0, $s);
							$allrooms = true;
						}
						if (!empty($slot_id)) {
							$slot_len = $this->getSlotLength($slot_id);
							$sessiondata = $this->getSlotSession($slot_id);
							if (!empty($sessiondata)) {
							    $label = $sessiondata['catnum'];  // the link text
							    $overrulePIvars = '';//array('session' => $this->getFieldContent('uid'));
							    $overrulePIvars = array('showSessionUid' => $sessiondata['uid'], 'backUid' => $GLOBALS['TSFE']->id, 'back2list' => '1');
							    $clearAnyway=1;    // the current values of piVars will NOT be preserved
							    $altPageId=$this->conf['singleSession'];      // ID of the target page, if not on the same page
								if (!t3lib_div::inList($hidecat,$sessiondata['catkey'])) {
									$sessionlink = $this->pi_linkTP_keepPIvars($label, $overrulePIvars, $cache, $clearAnyway, $altPageId);
								} else {
									$sessionlink = '';
								}
							    $label = $sessiondata['name'];  // the link text
							    $sessionlinkname = $this->pi_linkTP_keepPIvars($label, $overrulePIvars, $cache, $clearAnyway, $altPageId);
								$markerArray = array();
								$markerArray['###SLOTNAME###'] = $sessiondata['name'];
								$markerArray['###SLOTCATEGORY###'] = $sessiondata['category'];
								$markerArray['###SLOTCATEGORYKEY###'] = $sessiondata['catkey'];
								$markerArray['###SLOTCATEGORYCOLOR###'] = $sessiondata['catcolor'];
								$markerArray['###SLOTLINK###'] = $sessionlink;
								$markerArray['###SLOTLINKNAME###'] = $sessionlinkname;
								$markerArray['###SLOTSESSION###'] = $sessiondata['catnum'];
								$markerArray['###SLOTTEASER###'] = $sessiondata['teaser'];
							} else {
								$markerArray = array();
								if ($hidenotassigned==0) {
									$markerArray['###SLOTNAME###'] = $this->pi_getLL('tx_wseevents_sessions.slot_notassigned');
									$markerArray['###SLOTSESSION###'] = $this->pi_getLL('tx_wseevents_sessions.slot_notassigned');
									$markerArray['###SLOTTEASER###'] = $this->pi_getLL('tx_wseevents_sessions.slot_notassigned');
								} else {
									$markerArray['###SLOTNAME###'] = '';
									$markerArray['###SLOTSESSION###'] = '';
									$markerArray['###SLOTTEASER###'] = '';
								}
								$markerArray['###SLOTCATEGORY###'] = 0;
								$markerArray['###SLOTCATEGORYKEY###'] = $this->pi_getLL('tx_wseevents_sessions.slot_notassigned_catkey');
								$markerArray['###SLOTCATEGORYCOLOR###'] = $catcolor_notassigned;
								$markerArray['###SLOTLINK###'] = '';
								$markerArray['###SLOTLINKNAME###'] = '';
							}
							$markerArray['###SLOTDAY###'] = $d;
							$markerArray['###SLOTROOM###'] = $r;
							$markerArray['###SLOTNUM###'] = $s;
							$markerArray['###SLOTBEGIN###'] = $slotbegin[$s];
							$markerArray['###SLOTEND###'] = $slotbegin[$s+$slot_len];
							$markerArray['###SLOTSIZE###'] = $slot_len;
							if ($allrooms) {
								$slotwidth = $roomcount;
							} else {
								$slotwidth = 1;
							}
							$markerArray['###SLOTWIDTH###'] = $slotwidth;
							// Add column width if enabled
							if ($timecolwidth>0) {
								$markerArray['###COLUMNWIDTH###']  = ($slotcolwidth * $slotwidth).'%';
							}
							$content_slotrow .= $this->cObj->substituteMarkerArrayCached($template['slotcol'], $markerArray);
							for ( $x = $s; $x < $s+$slot_len; $x++) {
								if ($allrooms) {
									for ( $r1 = 1; $r1 <= $roomcount; $r1++ ) {
										$used[$x][$d][$r1] = ($x==$s)?$slot_len:(-$slot_len);
									}
								} else {
									$used[$x][$d][$r] = ($x==$s)?$slot_len:(-$slot_len);
								}
							}
						} else {
							if (empty($used[$s][$d][$r])) {
								$markerArray = array();
								$markerArray['###SLOTDAY###'] = $d;
								$markerArray['###SLOTROOM###'] = $r;
								$markerArray['###SLOTNUM###'] = $s;
								$markerArray['###SLOTBEGIN###'] = $slotbegin[$s];
								$markerArray['###SLOTEND###'] = $slotbegin[$s+1];
								$markerArray['###SLOTSIZE###'] = 1;
								$markerArray['###SLOTWIDTH###'] = 1;
								if ($hidenotdefined==0) {
									$markerArray['###SLOTNAME###'] = $this->pi_getLL('tx_wseevents_sessions.slot_notdefined');
									$markerArray['###SLOTSESSION###'] = $this->pi_getLL('tx_wseevents_sessions.slot_notdefined');
									$markerArray['###SLOTTEASER###'] = $this->pi_getLL('tx_wseevents_sessions.slot_notdefined');
								} else {
									$markerArray['###SLOTNAME###'] = '';
									$markerArray['###SLOTSESSION###'] = '';
									$markerArray['###SLOTTEASER###'] = '';
								}
								$markerArray['###SLOTCATEGORY###'] = 0;
								$markerArray['###SLOTCATEGORYKEY###'] = $this->pi_getLL('tx_wseevents_sessions.slot_notdefined_catkey');
								$markerArray['###SLOTCATEGORYCOLOR###'] = $catcolor_notdefined;
								$markerArray['###SLOTLINK###'] = '';
								// Add column width if enabled
								if ($timecolwidth>0) {
									$markerArray['###COLUMNWIDTH###']  = $slotcolwidth.'%';
								}
								$content_slotrow .= $this->cObj->substituteMarkerArrayCached($template['slotcolempty'], $markerArray);
							}
						}
					}
				}
			}
			$subpartArray1['###SLOTCOLUMN###'] = $content_slotrow;
			$subpartArray1['###SLOTCOLUMNEMPTY###'] = '';
			// Column with Start and end time
			$markerArray = array();
			$content_timecol = '';
			$content_timecolfree = '';
			if ($roomtime==0) {
				$markerArray['###SLOTBEGIN###'] = $slotbegin[$s];
				$markerArray['###SLOTEND###']   = $slotbegin[$s+1];
				$markerArray['###SLOTSIZE###']  = 1;
				// Add column width if enabled
				if ($timecolwidth>0) {
					$markerArray['###COLUMNWIDTH###']  = $timecolwidth.'%';
				}
				$content_timecol = $this->cObj->substituteMarkerArrayCached($template['timecol'], $markerArray);
			} else {
				if ($showday>0) {
					$timeday = $showday;
				} else {
					for ( $i=1; $i<=$daycount; $i++ ) {
						if (!empty($used[$s][$i][$roomtime])) {
							$timeday = $i;
						}
					}
				}
				if (!empty($used[$s][$timeday][$roomtime])) {
					$slot_len = $used[$s][$timeday][$roomtime];
					if ($slot_len>0) {
						$markerArray['###SLOTBEGIN###'] = $slotbegin[$s];
						$markerArray['###SLOTEND###']   = $slotbegin[$s+$slot_len];
						$markerArray['###SLOTSIZE###']  = $slot_len;
						// Add column width if enabled
						if ($timecolwidth>0) {
							$markerArray['###COLUMNWIDTH###']  = $timecolwidth.'%';
						}
						$content_timecol = $this->cObj->substituteMarkerArrayCached($template['timecol'], $markerArray);
					}
				} else {
					$markerArray['###SLOTBEGIN###'] = $slotbegin[$s];
					$markerArray['###SLOTEND###']   = $slotbegin[$s+1];
					$markerArray['###SLOTSIZE###']  = 1;
					// Add column width if enabled
					if ($timecolwidth>0) {
						$markerArray['###COLUMNWIDTH###']  = $timecolwidth.'%';
					}
					$content_timecolfree = $this->cObj->substituteMarkerArrayCached($template['timecolfree'], $markerArray);
				}
			}
			// Add debug output if enabled
			if ($showdebug>0) {
				$content_timecol = LF.'<!-- s='.$s.' d='.$d.' timecol -->'.$content_timecol;
				$content_timecolfree = LF.'<!-- s='.$s.' d='.$d.' timecolfree -->'.$content_timecolfree;
			}
			$subpartArray1['###TIMECOLUMN###'] = $content_timecol;
			$subpartArray1['###TIMECOLUMNEMPTY###'] = $content_timecolfree;
			
			$content_slot .= $this->cObj->substituteMarkerArrayCached($template['slotrow'], $markerArray, $subpartArray1);
		}

		$subpartArray['###SLOTROW###']  = $content_slot;



		$subpartArray1['###HEADERCOLUMN###'] = $content_header;
		$markerArray = array();
		if ($hidetime==0) {
			$markerArray['###HEADERBEGIN###'] = $this->pi_getLL('tx_wseevents_sessions.slot_titlebegin','Time');
		} else {
			$markerArray['###HEADERBEGIN###'] = '';
		}
		// Add column width if enabled
		if ($timecolwidth>0) {
			$markerArray['###COLUMNWIDTH###']  = $timecolwidth.'%';
		}
		$subpartArray['###HEADERROW###']  = $this->cObj->substituteMarkerArrayCached($template['headerrow'], $markerArray, $subpartArray1);

		$subpartArray1['###TITLECOLUMN###'] = $content_title;
		$subpartArray1['###SELECT###'] = $content_select;
		$markerArray = array();
		$markerArray['###TITLEBEGIN###'] = '';
		// Add column width if enabled
		if ($timecolwidth>0) {
			$markerArray['###COLUMNWIDTH###']  = $timecolwidth.'%';
		}
		$markerArray['###LABEL###'] = $this->pi_getLL('tx_wseevents_sessions.chooseeventday','[Choose event day]');
		$markerArray['###FORMACTION###'] = $this->pi_getPageLink($GLOBALS['TSFE']->page['uid']);
		$markerArray['###FORMSELECT###'] = $this->prefixId.'[showDay]';
		$markerArray['###FORMSEND###'] = htmlspecialchars($this->pi_getLL('tx_wseevents_sessions.showselection','[Show selection]'));
		$subpartArray['###TITLEROW###']  = $this->cObj->substituteMarkerArrayCached($template['titlerow'], $markerArray, $subpartArray1);

#ToDo: Hier muss die Combobox ins Template gepackt werden

		$content .= $this->cObj->substituteMarkerArrayCached($template['total'], array(), $subpartArray);
		return $content;
	}









	/**
	 * Display the details of a single session
	 *
	 * @param	[type]		$content: ...
	 * @param	[type]		$conf: ...
	 * @return	[type]		...
	 */
	function singleSessionView($content,$conf)	{
		$this->conf=$conf;
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();

		$this->internal['currentRow'] = $this->pi_getRecord($this->internal['currentTable'],$this->piVars['showSessionUid']);

		# Check if template file is set, if not use the default template
		if (!isset($conf['templateFile'])) {
			$templateFile = 'EXT:wse_events/wseevents.tmpl';
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
		if ($this->piVars['back2list']<>1) {
			# Back to single view
			$overrulePIvars = array ('showSessionUid' => $this->piVars['showSessionUid'],
									'showSpeakerUid' => $this->piVars['showSpeakerUid']);
		}
		$clearAnyway=1;    // the current values of piVars will NOT be preserved
		$altPageId=$this->piVars['backUid'];      // ID of the view page
		$backlink = $this->pi_linkTP_keepPIvars($label, $overrulePIvars, $cache, $clearAnyway, $altPageId);

		$markerArray['###TITLE###'] = $this->getFieldContent('name');
		$markerArray['###SESSIONNUMBER###'] = $this->getFieldContent('number');
		
		$datacat  = $this->pi_getRecord('tx_wseevents_categories',$this->internal['currentRow']['category']);
		$markerArray['###SESSIONCATEGORY###'] = $this->internal['currentRow']['category'];
		$markerArray['###SESSIONCATEGORYKEY###'] = $datacat['shortkey'];
		$markerArray['###SESSIONCATEGORYCOLOR###'] = $datacat['color'];
		
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
	 *
	 * @param	[type]		$content: ...
	 * @param	[type]		$conf: ...
	 * @return	[type]		...
	 */
	function singleSpeakerView($content,$conf)	{
		$this->conf=$conf;
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();

		$this->internal['currentRow'] = $this->pi_getRecord($this->internal['currentTable'],$this->piVars['showSpeakerUid']);

		# Check if upload directory is set, if not use the default directory
		if (!isset($conf['uploadDirectory'])) {
			$uploadDirectory = 'uploads/tx_wseevents';
		} else {
			$uploadDirectory = $conf['uploadDirectory'];
		}

		# Check if template file is set, if not use the default template
		if (!isset($conf['templateFile'])) {
			$templateFile = 'EXT:wse_events/wseevents.tmpl';
		} else {
			$templateFile = $conf['templateFile'];
		}
		# Get the template
		$this->templateCode = $this->cObj->fileResource($templateFile);

		# Get the parts out of the template
		$template['total'] = $this->cObj->getSubpart($this->templateCode,'###SPEAKERVIEW###');
		$template['sessionrow'] = $this->cObj->getSubpart($template['total'],'###SESSIONROW###');

		// This sets the title of the page for use in indexed search results:
		if ($this->internal['currentRow']['title'])	$GLOBALS['TSFE']->indexedDocTitle=$this->internal['currentRow']['title'];

		// Link for back to list view
		$label = $this->pi_getLL('back','Back');  // the link text
		if ($this->piVars['back2list']<>1) {
			# Back to single view
			$overrulePIvars = array ('showSessionUid' => $this->piVars['showSessionUid'],
									'showSpeakerUid' => $this->piVars['showSpeakerUid']);
		}
		$clearAnyway=1;    // the current values of piVars will NOT be preserved
		$altPageId=$this->piVars['backUid'];      // ID of the view page
		$backlink = $this->pi_linkTP_keepPIvars($label, $overrulePIvars, $cache, $clearAnyway, $altPageId);

		# Check if the speaker has a session on this event
		$sessionids = $this->getSpeakerSessionList($this->piVars['showSpeakerUid'],$this->conf['pidListEvents']);

		$markerArray['###NAME###'] = $this->getFieldContent('name');
		$markerArray['###EMAILNAME###'] = $this->getFieldHeader('email');
		$markerArray['###EMAILDATA###'] = $this->getFieldContent('email');
		$markerArray['###COUNTRYNAME###'] = $this->getFieldHeader('country');
		$markerArray['###COUNTRYDATA###'] = $this->getFieldContent('country');
		$markerArray['###COMPANYNAME###'] = $this->getFieldHeader('company');
		$markerArray['###COMPANYDATA###'] = $this->getFieldContent('company');
		$markerArray['###COMPANYLINK###'] = 'http://'.$this->getFieldContent('companylink');
		$markerArray['###INFONAME###'] = $this->getFieldHeader('info');
		$markerArray['###INFODATA###'] = $this->getFieldContent('info');
		$markerArray['###IMAGENAME###'] = $this->getFieldHeader('image');
		$image = trim($this->getFieldContent('image'));
		if (!empty($image)) {
#					$markerArray['###IMAGEFILE###'] = $uploadDirectory.'/'.$image;
			$img = $this->conf['image.'];
			if (empty($img)) {
			    $img['file'] = 'GIFBUILDER';
				$img['file.']['XY'] = '100,150';
				$img['file.']['5'] = 'IMAGE';
			}
			$img['file.']['5.']['file'] = $uploadDirectory.'/'.$image;
			$markerArray['###IMAGELINK###'] = $this->cObj->IMAGE($img);
			$markerArray['###IMAGEFILE###'] = $uploadDirectory.'/'.$image;
		} else {
			$markerArray['###IMAGELINK###'] = '';
			$markerArray['###IMAGEFILE###'] = '';
		}
		$markerArray['###SESSIONSNAME###'] = $this->getFieldHeader('speakersessions');
		$this->internal['speakersessions'] = $sessionids;
		$markerArray['###SESSIONS###'] = $this->getFieldContent('speakersessions');
		$markerArray['###BACKLINK###'] = $backlink;

		# For every session get information
		foreach(explode(',',$sessionids) as $k){
			$label = $this->getTranslatedField('tx_wseevents_sessions', 'name', $k);
			if (!empty($this->conf['singleSession'])) {
				$overrulePIvars = '';//array('session' => $this->getFieldContent('uid'));
				$overrulePIvars = array('showSessionUid' => $k, 'backUid' => $GLOBALS['TSFE']->id, 'showSpeakerUid' => $this->piVars['showSpeakerUid']);
				$clearAnyway=1;    // the current values of piVars will NOT be preserved
				$altPageId=$this->conf['singleSession'];      // ID of the target page, if not on the same page
				$sessionname = $this->pi_linkTP_keepPIvars($label, $overrulePIvars, $cache, $clearAnyway, $altPageId);
			} else {
				$sessionname = $label;
			}

			# Build content from template + array
			$markerarray1 = array();
			$markerArray1['###SESSIONNAME###'] = $sessionname;
			$markerArray1['###SESSIONTEASER###'] = $this->getTranslatedField('tx_wseevents_sessions', 'teaser', $k);//$data['teaser'];
			$markerArray1['###SESSIONDESCRIPTION###'] = $this->getTranslatedField('tx_wseevents_sessions', 'description', $k);//$data['description'];
			$sessdata = $this->pi_getRecord('tx_wseevents_sessions', $k);
			$datacat  = $this->pi_getRecord('tx_wseevents_categories',$sessdata['category']);
			$markerArray1['###SESSIONNUMBER###'] = $datacat['shortkey'].sprintf('%02d', $sessdata['number']);
			$markerArray1['###SESSIONCATEGORY###'] = $sessdata['category'];
			$markerArray1['###SESSIONCATEGORYKEY###'] = $datacat['shortkey'];
			$markerArray1['###SESSIONCATEGORYCOLOR###'] = $datacat['color'];
			// Get time slot info
			$tscontent = '';
			foreach(explode(',',$sessdata['timeslots']) as $ts){
				$tsdata = $this->pi_getRecord('tx_wseevents_timeslots',$ts);
				$timeslotname = tx_wseevents_timeslots::formatSlotName($tsdata);
				if (!empty($tscontent)) {
					$tscontent .= $this->internal['slotdelimiter'].$timeslotname;
				} else {
					$tscontent = $timeslotname;
				}
			}
			$markerArray1['###SESSIONSLOTS###'] = $tscontent;

			$content_item .= $this->cObj->substituteMarkerArrayCached($template['sessionrow'], $markerArray1);
		}

#		$this->pi_getEditPanel();
		$subpartArray['###SESSIONROW###'] = $content_item;

		return $this->cObj->substituteMarkerArrayCached($template['total'], $markerArray, $subpartArray);
	}








	/**
	 * Get content of one field
	 *
	 * @param	[type]		$fN: ...
	 * @return	[type]		...
	 */
	function getFieldContent($fN)	{
		switch($fN) {
			case 'uid':
				return $this->pi_list_linkSingle($this->internal['currentRow'][$fN],$this->internal['currentRow']['uid'],1);	// The "1" means that the display of single items is CACHED! Set to zero to disable caching.
			break;

			case 'number':
				$datacat = $this->pi_getRecord('tx_wseevents_categories',$this->internal['currentRow']['category']);
				$datanum = $this->internal['currentRow'][$fN];
				return $datacat['shortkey'].sprintf ('%02d', $datanum);
			break;

			case 'name':
				switch ($this->internal['currentTable']) {
					case 'tx_wseevents_sessions':
						return $this->getTranslatedField('tx_wseevents_sessions', 'name');
					break;
					case 'tx_wseevents_speakers':
						if (!empty($this->internal['currentRow']['firstname'])) {
							if ((isset($this->conf['lastnameFirst'])) && ($this->conf['lastnameFirst']==1)) {
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
						return $this->getTranslatedField('tx_wseevents_sessions', $fN);
					break;
					default:
						return $this->internal['currentRow'][$fN];
					break;
				}
			break;

			case 'description':
				switch ($this->internal['currentTable']) {
					case 'tx_wseevents_sessions':
						$data = $this->getTranslatedField('tx_wseevents_sessions', $fN);
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
					if (!empty($data['firstname'])) {
						if (((isset($this->conf['lastnameFirst']))) && ($this->conf['lastnameFirst']==1)) {
							$label =  $data['name'].', '.$data['firstname'];
						} else {
							$label =  $data['firstname'].' '.$data['name'];
						}
					} else {
						$label =  $data['name'];
					}

					if (!empty($this->conf['singleSpeaker'])) {
#					    $overrulePIvars = '';//array('session' => $this->getFieldContent('uid'));
					    $overrulePIvars = array('showSpeakerUid' => $data['uid'], 'backUid' => $GLOBALS['TSFE']->id, 'showSessionUid' => $this->internal['currentRow']['uid']);
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
						$content .= $this->internal['speakerdelimiter'].$speakername;
					} else {
						$content = $speakername;
					}
				}
				if (empty($content)) {
					$content = $this->pi_getLL('tx_wseevents_sessions.nospeakers','[no speaker assigned]');
				}
				return $content;
			break;

			case 'speakersessions':
				foreach(explode(',',$this->internal['speakersessions']) as $k){
					$data = $this->pi_getRecord('tx_wseevents_sessions',$k);

					$label = $data['name'];
					if (!empty($this->conf['singleSession'])) {
					    $overrulePIvars = '';//array('session' => $this->getFieldContent('uid'));
					    $overrulePIvars = array('showSessionUid' => $data['uid'], 'backUid' => $GLOBALS['TSFE']->id);
					    $clearAnyway=1;    // the current values of piVars will NOT be preserved
					    $altPageId=$this->conf['singleSession'];      // ID of the target page, if not on the same page
					    $sessionname = $this->pi_linkTP_keepPIvars($label, $overrulePIvars, $cache, $clearAnyway, $altPageId);
					} else {
					    $sessionname = $label;
					}
					if (!empty($content)) {
						$content .= $this->internal['sessiondelimiter'].$sessionname;
					} else {
						$content = $sessionname;
					}
					if (!empty($this->conf['singleSessionSlot'])) {
						// ToDo: Here the timeslots must be read and added to the content
					}
				}
				return $content;
			break;

			case 'timeslots':
				foreach(explode(',',$this->internal['currentRow'][$fN]) as $k){
					$data = $this->pi_getRecord('tx_wseevents_timeslots',$k);
				    $timeslotname = tx_wseevents_timeslots::formatSlotName($data);
					if (isset($content)) {
						$content .= $this->internal['slotdelimiter'].$timeslotname;
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
						$data = $this->getTranslatedField('tx_wseevents_speakers', $fN);
						return $this->pi_RTEcssText($data);
					break;
					default:
						return $this->internal['currentRow'][$fN];
					break;
				}
			break;

			case 'country':
				$data = $this->pi_getRecord('static_countries',$this->internal['currentRow'][$fN]);
				$iso = $data['cn_iso_3'];
				return $this->staticInfo->getStaticInfoName('COUNTRIES', $iso);
			break;

			default:
				return $this->internal['currentRow'][$fN];
			break;
		}
	}








	/**
	 * Get the translated content of a field
	 * Returns english content if no translation is found
	 *
	 * @param	[type]		$dbname: ...
	 * @param	[type]		$fN: ...
	 * @param	[type]		$fUid: ...
	 * @return	[type]		...
	 */
	function getTranslatedField($dbname, $fN, $fUid=-1) {
		$index = $GLOBALS['TSFE']->sys_language_uid;
		if ($fUid<0) {
			$fUid = $this->internal['currentRow']['uid'];
		}
		if ($index<>0) {
			// for the name of a session, check if a translation is there
			$where = 'deleted=0 AND hidden=0 AND l18n_parent='.$fUid.' AND sys_language_uid='.$index;
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fN, $dbname, $where);
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			$datacount = $row[$fN];
			if (!empty($datacount)) {
				return $datacount;
			} else {
				// no translation get the field content from the default record
				$where = 'deleted=0 AND hidden=0 AND uid='.$fUid;
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fN, $dbname, $where);
				$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
				$datacount = $row[$fN];
				return $datacount;
			}
		} else {
			//show default language
//			return $this->internal['currentRow'][$fN];
			$where = 'deleted=0 AND hidden=0 AND uid='.$fUid;
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fN, $dbname, $where);
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			$datacount = $row[$fN];
			return $datacount;
		}
	}








	/**
	 * Get the translated name of a category
	 * Returns english name if no translation is found
	 *
	 * @param	[type]		$dbname: ...
	 * @param	[type]		$rowuid: ...
	 * @param	[type]		$fN: ...
	 * @return	[type]		...
	 */
	function getTranslatedCategory($dbname, $rowuid, $fN) {
		$index = $GLOBALS['TSFE']->sys_language_uid;
		if ($index<>0) {
			// for the name of a session, check if a translation is there
			$where = 'deleted=0 AND hidden=0 AND l18n_parent='.$rowuid.' AND sys_language_uid='.$index;
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('name', 'tx_wseevents_categories', $where);
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			$datacount = $row['name'];
			if (!empty($datacount)) {
				return $datacount;
			} else {
				return $fN;
			}
		} else {
			//show default language
			return $fN;
		}
	}







	/**
	 * Get list of session UIDs for the speaker
	 *
	 * @param	[type]		$speakerid: ...
	 * @param	[type]		$eventPid: ...
	 * @return	[type]		...
	 */
	function getSpeakerSessionList($speakerid,$eventPid) {
		$where = 'deleted=0 AND hidden=0 AND sys_language_uid=0';
		$this->conf['pidList'] = $eventPid;
#		$res = $this->pi_exec_query('tx_wseevents_sessions',0,$where);
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('speaker, uid', 'tx_wseevents_sessions', $where);
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			foreach(explode(',',$row['speaker']) as $k){
				if ($k==$speakerid) {
					if (empty($sessions)) {
						$sessions = $row['uid'];
					} else {
						$sessions .= ','.$row['uid'];
					}
				}
			}
		}
		return $sessions;
	}






	/**
	 * Get label of one field
	 *
	 * @param	[type]		$fN: ...
	 * @return	[type]		...
	 */
	function getFieldHeader($fN)	{
		switch($fN) {

			default:
				return $this->pi_getLL($this->internal['currentTable'].'.listFieldHeader_'.$fN,'['.$fN.']');
			break;
		}
	}




	/**
	 * Get info about an event
	 *
	 * @param	[type]		$event: ...
	 * @return	[type]		...
	 */
	function getEventInfo($event) {
		$where = 'deleted=0 AND hidden=0 AND sys_language_uid=0 AND uid='.$event;
		$this->conf['pidList'] = $eventPid;
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('name,location,begin,length,timebegin,timeend,slotsize,maxslot,defslotcount', 'tx_wseevents_events', $where);
		$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
		return $row;
	}


	/**
	 * Get info about a location
	 *
	 * @param	[type]		$loc_id: ...
	 * @return	[type]		...
	 */
	function getLocationInfo($loc_id) {
		$where = 'deleted=0 AND hidden=0 AND sys_language_uid=0 AND uid='.$loc_id;
		$this->conf['pidList'] = $eventPid;
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('name,website,comment', 'tx_wseevents_locations', $where);
		$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
		return $row;
	}


	/**
	 * Get info about rooms of an location
	 *
	 * @param	[type]		$loc_id: ...
	 * @return	[type]		...
	 */
	function getRoomInfo($loc_id) {
		$where = 'deleted=0 AND hidden=0 AND sys_language_uid=0 AND location='.$loc_id;
		$this->conf['pidList'] = $eventPid;
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid,name,comment,seats', 'tx_wseevents_rooms', $where);
		$id = 1;
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$rows[$id] = $row;
			$id++;
		}
		return $rows;
	}


	/**
	 * Get id of record from time slot for given event, day, room and slot
	 *
	 * @param	[type]		$event: ...
	 * @param	[type]		$day: ...
	 * @param	[type]		$room: ...
	 * @param	[type]		$slot: ...
	 * @return	[type]		...
	 */
	function getSlot($event, $day, $room, $slot) {
		$where = 'deleted=0 AND hidden=0 AND sys_language_uid=0 AND event='.$event.' AND eventday='.$day.' AND room='.$room.' AND begin='.$slot;
		$this->conf['pidList'] = $eventPid;
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'tx_wseevents_timeslots', $where);
		$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
		return $row['uid'];
	}


	/**
	 * Get length of time slot for given uid
	 *
	 * @param	[type]		$slot_id: ...
	 * @return	[type]		...
	 */
	function getSlotLength($slot_id) {
		$where = 'deleted=0 AND hidden=0 AND sys_language_uid=0 AND uid='.$slot_id;
		$this->conf['pidList'] = $eventPid;
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('length', 'tx_wseevents_timeslots', $where);
		$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
		return $row['length'];
	}



	/**
	 * Get session data for given slot
	 *
	 * @param	[type]		$slot_id: ...
	 * @return	[type]		...
	 */
	function getSlotSession($slot_id) {
		$where = 'deleted=0 AND hidden=0 AND sys_language_uid=0';
		$this->conf['pidList'] = $eventPid;
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid,name,category,number,teaser,timeslots', 'tx_wseevents_sessions', $where);
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
//			foreach(explode(',',$row['timeslots']) as $k){
//				if ($k==$slot_id) {
			if (t3lib_div::inList($row['timeslots'],$slot_id)) {
					$session = $row;
					$datacat = $this->pi_getRecord('tx_wseevents_categories',$row['category']);
					$session['catnum'] = $datacat['shortkey'].sprintf ('%02d', $row['number']);
					$session['catkey'] = $datacat['shortkey'];
					$session['catcolor'] = $datacat['color'];
					$session['name'] = $this->getTranslatedField('tx_wseevents_sessions', 'name', $row['uid']);
					$session['teaser'] = $this->getTranslatedField('tx_wseevents_sessions', 'teaser', $row['uid']);
//				}
			}
		}
		return $session;
	}

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wse_events/pi1/class.tx_wseevents_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wse_events/pi1/class.tx_wseevents_pi1.php']);
}

?>