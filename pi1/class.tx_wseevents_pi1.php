<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2009 Michael Oehlhof <typo3@oehlhof.de>
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
 * FE Plugin 'WSE Events' for the 'wse_events' extension.
 * Displays session data as list and detail view
 * Displays speaker data as list and detail view
 * Displays time slot view
 *
 * @author	Michael Oehlhof <typo3@oehlhof.de>
 */

/**
 * To temporary show some debug output on live web site
 * it can be easyly switched on via a TypoScript setting.
 * plugin.tx_wseevents_pi1.listTimeslotView.debug = 1
 */

/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   93: class tx_wseevents_pi1 extends tslib_pibase
 *  121:     function main($content, $conf)
 *  221:     function listSessionView($content, $conf)
 *  503:     function listSpeakerView($content, $conf)
 *  721:     function listTimeslotView($content, $conf)
 * 1321:     function singleSessionView($content, $conf)
 * 1411:     function singleSpeakerView($content, $conf)
 * 1567:     function getFieldContent($fN)
 * 1809:     function getSpeakerSessionList($speakerid, $eventPid)
 * 1835:     function getFieldHeader($fN)
 * 1852:     function getEventInfo($event)
 * 1867:     function getRoomInfo($loc_id)
 * 1891:     function getSlot($event, $day, $room, $slot, $showdbgsql)
 * 1913:     function getSlotLength($slot_id)
 * 1928:     function getSlotSession($slot_id)
 * 1963:     function getSpeakerNames($speakerlist)
 * 1997:     function setCache()
 *
 * TOTAL FUNCTIONS: 16
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

require_once(PATH_tslib . 'class.tslib_pibase.php');

/*
 * Include Static Info Tables for country selection
 */
require_once(t3lib_extMgm::extPath('static_info_tables')
	. 'pi1/class.tx_staticinfotables_pi1.php');

/*
 * Include timeslot class for function to format time slot name
 */
require_once(t3lib_extMgm::extPath('wse_events')
	. 'class.tx_wseevents_timeslots.php');


define('TAB', chr(9));
define('LF', chr(10));

/**
 * Class 'tx_wseevents_pi1' for the 'wse_events' extension.
 *
 * @package		TYPO3
 * @subpackage	wse_events
 * @author		Michael Oehlhof <typo3@oehlhof.de>
 */
class tx_wseevents_pi1 extends tslib_pibase {
	// Same as class name
	var $prefixId = 'tx_wseevents_pi1';

	// Path to this script relative to the extension dir.
	var $scriptRelPath = 'pi1/class.tx_wseevents_pi1.php';

	// The extension key.
	var $extKey = 'wse_events';

	var $pi_checkCHash = TRUE;

	// Flag for using the cache
	var $use_cache = 1;

	// Flag for displaying lists, used for Backlink creation
	var $listview = 1;

	// Internal configuration
	var $internal = array();

	/**
	 * Main function, decides in which form the data is displayed
	 *
	 * @param	string		$content: default content string, ignore
	 * @param	array		$conf: TypoScript configuration for the plugin
	 * @return	string		Content for output on the web site
	 */
	function main($content, $conf)	{
		// Init and get the flexform data of the plugin
		$this->pi_initPIflexform();
		$piFlexForm = $this->cObj->data['pi_flexform'];
		$index = $GLOBALS['TSFE']->sys_language_uid;

		# Get FlexForm data
		$sDef = current($piFlexForm['data']);
		$lDef = array_keys($sDef);

		# Initialize Static Info
		$this->staticInfo = t3lib_div::makeInstance('tx_staticinfotables_pi1');
		$this->staticInfo->init();

		# Read TypoScript settings and initialize internal variables

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
		# Check for hiding the time slots
		if (!isset($conf['hideTimeslots'])) {
			$this->internal['hideTimeslots'] = 0;
		} else {
			$this->internal['hideTimeslots'] = intval($conf['hideTimeslots']);
		}
		# Check if caching should be disabled
		if (isset($conf['no_cache']) && (1 == $conf['no_cache'])) {
			$this->use_cache = 0;
		}

		$flexFormValuesArray['dynListType'] = $this->pi_getFFvalue($piFlexForm, 'dynListType', 'display', $lDef[0]);
		$conf['pidListEvents'] = $this->pi_getFFvalue($piFlexForm, 'pages', 'sDEF');
		$conf['pidListCommon'] = $this->pi_getFFvalue($piFlexForm, 'commonpages', 'sDEF');
		$conf['singleSession'] = $this->pi_getFFvalue($piFlexForm, 'singleSession', 'display');
		$conf['singleSpeaker'] = $this->pi_getFFvalue($piFlexForm, 'singleSpeaker', 'display');
		$conf['lastnameFirst'] = $this->pi_getFFvalue($piFlexForm, 'lastnameFirst', 'display');
		$conf['recursive'] = $this->cObj->data['recursive'];

//			return t3lib_div::view_array($conf);

		# Show input page depend on selected tab
		switch((string)$flexFormValuesArray['dynListType'])	{
			case 'sessionlist':
				$conf['pidList'] = $conf['pidListEvents'];
				return $this->pi_wrapInBaseClass($this->listSessionView($content, $conf));
			break;
			case 'sessiondetail':
				$this->listview = 0;
				// Set table to session table
				$this->internal['currentTable'] = 'tx_wseevents_sessions';
				$this->internal['currentRow'] = $this->piVars['showSessionUid'];
				return $this->pi_wrapInBaseClass($this->singleSessionView($content, $conf));
			break;
			case 'speakerlist':
				$conf['pidList'] = $conf['pidListCommon'];
				return $this->pi_wrapInBaseClass($this->listSpeakerView($content, $conf));
			break;
			case 'speakerdetail':
				$this->listview = 0;
				$this->internal['currentTable'] = 'tx_wseevents_speakers';
				$this->internal['currentRow'] = $this->piVars['showSpeakerUid'];
				return $this->pi_wrapInBaseClass($this->singleSpeakerView($content, $conf));
			break;
			case 'timeslots':
				return $this->pi_wrapInBaseClass($this->listTimeslotView($content, $conf));
			break;
			default:
				return $this->pi_wrapInBaseClass('Not implemented: ['
				  . (string)$flexFormValuesArray['dynListType'] . ']<br>Index=[' . $index . ']<br>');
			break;
		}
	}






	/**
	 * Display a list of sessions for the event that is set in the flex form settings
	 *
	 * @param	string		$content: default content string, ignore
	 * @param	array		$conf: TypoScript configuration for the plugin
	 * @return	string		Content for output on the web site
	 */
	function listSessionView($content, $conf)	{
		global $TCA;

		$this->conf=$conf;		// Setting the TypoScript passed to this function in $this->conf
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();		// Loading the LOCAL_LANG values
		$index = $GLOBALS['TSFE']->sys_language_uid;

		$lConf = $this->conf['listView.'];	// Local settings for the listView function

		# Set table to session table
		$this->internal['currentTable'] = 'tx_wseevents_sessions';

		# Loading all TCA details for this table:
		t3lib_div::loadTCA($this->internal['currentTable']);

		if (!isset($this->piVars['pointer'])) $this->piVars['pointer']=0;
		if (!isset($this->piVars['mode'])) $this->piVars['mode']=1;

		# Check if template file is set, if not use the default template
		if (!isset($conf['templateFile'])) {
			$templateFile = 'EXT:wse_events/wseevents.tmpl';
		} else {
			$templateFile = $conf['templateFile'];
		}
		# Get the template
		$this->templateCode = $this->cObj->fileResource($templateFile);

		# Get the parts out of the template
		$template['total'] = $this->cObj->getSubpart($this->templateCode, '###SESSIONLIST###');
		$template['catsection'] = $this->cObj->getSubpart($template['total'], '###CATEGORYSELECT###');
		$template['catselect'] = $this->cObj->getSubpart($template['catsection'], '###SELECT###');
		$template['catoption'] = $this->cObj->getSubpart($template['catselect'], '###OPTIONNOTSELECTED###');
		$template['catoptionsel'] = $this->cObj->getSubpart($template['catselect'], '###OPTIONSELECTED###');
		$template['evtsection'] = $this->cObj->getSubpart($template['total'], '###EVENTSELECT###');
		$template['evtselect'] = $this->cObj->getSubpart($template['evtsection'], '###SELECT###');
		$template['evtoption'] = $this->cObj->getSubpart($template['evtselect'], '###OPTIONNOTSELECTED###');
		$template['evtoptionsel'] = $this->cObj->getSubpart($template['evtselect'], '###OPTIONSELECTED###');
		$template['singlerow'] = $this->cObj->getSubpart($template['total'], '###SINGLEROW###');
		$template['header'] = $this->cObj->getSubpart($template['singlerow'], '###HEADER###');
		$template['row'] = $this->cObj->getSubpart($template['singlerow'], '###ITEM###');
		$template['row_alt'] = $this->cObj->getSubpart($template['singlerow'], '###ITEM_ALT###');

		# Check if target for documents link is set, if not use the default target
		if (!isset($conf['documentsTarget'])) {
			$this->documentsTarget = 'target="_blank"';
		} else {
			$this->documentsTarget = $conf['documentsTarget'];
		}
		# Check for delimiter between the documents
		if (!isset($conf['documentsdelimiter'])) {
			$this->internal['documentsdelimiter'] = '<br />';
		} else {
			$this->internal['documentsdelimiter'] = $conf['documentsdelimiter'];
		}

		# Initializing the query parameters:
		$sorting = $this->conf['sorting'];
		# Number of results to show in a listing.
		$this->internal['results_at_a_time'] = t3lib_div::intInRange($lConf['results_at_a_time'], 0, 1000, 100);
		# The maximum number of "pages" in the browse-box: "Page 1", 'Page 2', etc.
		$this->internal['maxPages'] = t3lib_div::intInRange($lConf['maxPages'], 0, 1000, 2);
		$this->internal['searchFieldList'] = 'uid, name, category, number, speaker, room, timeslots, teaser';
		$this->internal['orderByList'] = 'category, number, name';
	    $where = ' AND ' . $this->internal['currentTable'] . '.'
			. $TCA[$this->internal['currentTable']]['ctrl']['languageField'] . '=0';

		# Check for catagory selection
		$showcat = $this->piVars['showCategory'];
		if (!empty($showcat)) {
			$where .= ' AND category=' . $showcat;
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
		$res = $this->pi_exec_query('tx_wseevents_events', 1, $where1, '', '', 'name, uid');
		list($eventcount) = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);

		# Create template data for event combobox
		$event_item = '';	# Clear var;
		$markerArray = array();
		# Make listing query, pass query to SQL database:
		$res = $this->pi_exec_query('tx_wseevents_events', 0, $where1);
		if ($res) {
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				# Get overload language record
				if ($GLOBALS['TSFE']->sys_language_content) {
					$row = $GLOBALS['TSFE']->sys_page->getRecordOverlay('tx_wseevents_events',
						$row, $GLOBALS['TSFE']->sys_language_content,
						$GLOBALS['TSFE']->sys_language_contentOL, '');
				}

				# Take the first event as selected if no event is selected in the URL
				if (0 == $showevent) {
					$showevent = $row['uid'];
				}
				$eventname = $row['name'];

				# Set one event option
				$markerArray['###VALUE###'] = $row['uid'];
				$markerArray['###OPTION###'] = $eventname;
				if ($showevent == $row['uid']) {
					$event_item .= $this->cObj->substituteMarkerArrayCached($template['evtoptionsel'], $markerArray);
				} else {
					$event_item .= $this->cObj->substituteMarkerArrayCached($template['evtoption'], $markerArray);
				}
			}
		}
		# Show selection combo box if more than one event is found
		if (1 < $eventcount) {
			# Set select options
			$subpartArray1['###SELECT###'] = $event_item;
			# Set label for selection box
			$markerArray1['###LABEL###'] = $this->pi_getLL('tx_wseevents_sessions.chooseeventday', '[Choose event day]');
			//$markerArray1['###FORMACTION###'] = $this->pi_getPageLink($GLOBALS['TSFE']->page['uid']);
			$markerArray1['###FORMSELECT###'] = $this->prefixId . '[showEvent]';
			$markerArray1['###FORMSEND###'] = htmlspecialchars($this->pi_getLL('tx_wseevents_sessions.showselection', '[Show selection]'));
			$subpartArray['###EVENTSELECT###'] = $this->cObj->substituteMarkerArrayCached($template['evtsection'], $markerArray1, $subpartArray1);
		} else {
			$subpartArray['###EVENTSELECT###'] = '';
		}

		# Get date of event
		$this->eventrecord = $this->pi_getRecord('tx_wseevents_events', $showevent);

		# Create template data for category combobox
		$select_item = '';	// Clear var;
		$markerArray = array();
		$markerArray['###VALUE###'] = 0;
		$markerArray['###OPTION###'] = $this->pi_getLL('tx_wseevents_sessions.chooseall', '[-All-]');
		if (0 == $showcat) {
			$select_item .= $this->cObj->substituteMarkerArrayCached($template['catoptionsel'], $markerArray);
		} else {
			$select_item .= $this->cObj->substituteMarkerArrayCached($template['catoption'], $markerArray);
		}

		# Get list of categories
		# Make query, pass query to SQL database:
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tx_wseevents_categories', 'sys_language_uid=0'
			. $this->cObj->enableFields('tx_wseevents_categories'), '', 'shortkey');
		if ($res) {
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				if (!t3lib_div::inList($hidecat, $row['uid'])) {
					# Get overload language record
					if ($GLOBALS['TSFE']->sys_language_content) {
						$row = $GLOBALS['TSFE']->sys_page->getRecordOverlay('tx_wseevents_categories',
							$row, $GLOBALS['TSFE']->sys_language_content,
							$GLOBALS['TSFE']->sys_language_contentOL, '');
					}
					$catname = $row['name'];
					# Set one category option
					$markerArray['###VALUE###'] = $row['uid'];
					$markerArray['###OPTION###'] = $row['shortkey'] . ' - ' . $catname;
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
		$markerArray1['###LABEL###'] = $this->pi_getLL('tx_wseevents_sessions.choosecategory', '[Choose category]');
		$markerArray1['###FORMSELECT###'] = $this->prefixId . '[showCategory]';
		$markerArray1['###FORMSEND###'] = htmlspecialchars($this->pi_getLL('tx_wseevents_sessions.showselection', '[Show selection]'));
		$subpartArray['###CATEGORYSELECT###'] = $this->cObj->substituteMarkerArrayCached($template['catsection'], $markerArray1, $subpartArray1);

		# Get number of records:
		$this->conf['pidList'] = $this->conf['pidListEvents'];
		$res = $this->pi_exec_query($this->internal['currentTable'], 1, $where, '', '', 'category, number, name');
		list($this->internal['res_count']) = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);

		# Make listing query, pass query to SQL database:
		$res = $this->pi_exec_query($this->internal['currentTable'], 0, $where, '', '', 'category, number, name');

		# Get the column names
		$content_item = '';	# Clear var;
		$markerArray = array();
		$markerArray['###SESSIONNUMBER###'] = $this->getFieldHeader('number');
		$markerArray['###SESSIONNAME###'] = $this->getFieldHeader('name');
		$markerArray['###SPEAKER###'] = $this->getFieldHeader('speaker');
		$markerArray['###TIMESLOTS###'] = $this->getFieldHeader('timeslots');
		$markerArray['###SESSIONDOCUMENTSNAME###'] = $this->getFieldHeader('documents');

		$content_item .= $this->cObj->substituteMarkerArrayCached($template['header'], $markerArray);

		$switch_row = 0;
		if ($res) {
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				# Get overload workspace record
				$GLOBALS['TSFE']->sys_page->versionOL($this->internal['currentTable'], &$row);
				# fix pid for record from workspace
				$GLOBALS['TSFE']->sys_page->fixVersioningPid($this->internal['currentTable'], &$row);
				# Get overload language record
				if ($GLOBALS['TSFE']->sys_language_content) {
					$row = $GLOBALS['TSFE']->sys_page->getRecordOverlay($this->internal['currentTable'],
						$row, $GLOBALS['TSFE']->sys_language_content,
						$GLOBALS['TSFE']->sys_language_contentOL, '');
				}
				# show only sessions of selected event
				if (0 < $showevent) {
					if ($showevent <> $row['event']) {
						unset ($row);
					}
				}
				if (is_array($row)) {
					$this->internal['currentRow'] = $row;
					if (!t3lib_div::inList($hidecat, $row['category'])) {
						if (!empty($this->conf['singleSession'])) {
							$label = $this->getFieldContent('name');  # the link text
							$overrulePIvars = array('showSessionUid' => $this->internal['currentRow']['uid'],
								'backUid' => $GLOBALS['TSFE']->id);
							$clearAnyway = 1;    # the current values of piVars will NOT be preserved
							$altPageId = $this->conf['singleSession'];      # ID of the target page, if not on the same page
							$this->setCache();
							$sessionname = $this->pi_linkTP_keepPIvars($label, $overrulePIvars,
								$this->use_cache, $clearAnyway, $altPageId);
						} else {
							$sessionname = $this->getFieldContent('name');
						}

						# Build content from template + array
						$markerArray = array();
						$markerArray['###SESSIONTEASERNAME###'] = $this->getFieldHeader('teaser');
						$markerArray['###SESSIONTEASER###'] = $this->getFieldContent('teaser');
						$markerArray['###SESSIONDESCRIPTIONNAME###'] = $this->getFieldHeader('description');
						$markerArray['###SESSIONDESCRIPTION###'] = $this->cObj->stdWrap($this->getFieldContent('description'),
							$this->conf['sessiondescription_stdWrap.']);

						$markerArray['###SESSIONDOCUMENTSNAME###'] = $this->getFieldHeader('documents');
						$markerArray['###SESSIONDOCUMENTS###'] = $this->getFieldContent('documents');
						$markerArray['###SESSIONNAME###'] = $sessionname;
						$markerArray['###SPEAKER###'] = $this->getFieldContent('speaker');
						$markerArray['###TIMESLOTS###'] = $this->getFieldContent('timeslots');

						$markerArray['###SESSIONNUMBER###'] = $this->getFieldContent('number');
						# Get the data for the category of the session
						$datacat  = $this->pi_getRecord('tx_wseevents_categories', $this->getFieldContent('category'));
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
		}
		$subpartArray['###SINGLEROW###'] = $content_item;

		$content .= $this->cObj->substituteMarkerArrayCached($template['total'], array(), $subpartArray);
		return $content;
	}










	/**
	 * Display a list of speakers for the event that is set in the flex form settings
	 *
	 * @param	string		$content: default content string, ignore
	 * @param	array		$conf: TypoScript configuration for the plugin
	 * @return	string		Content for output on the web site
	 */
	function listSpeakerView($content, $conf)	{
		$this->conf=$conf;		# Setting the TypoScript passed to this function in $this->conf
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();		# Loading the LOCAL_LANG values
		$index = $GLOBALS['TSFE']->sys_language_uid;

		$lConf = $this->conf['listView.'];	# Local settings for the listView function

		# Set table to session table
		$this->internal['currentTable'] = 'tx_wseevents_speakers';

		if (!isset($this->piVars['pointer']))	$this->piVars['pointer'] = 0;
		if (!isset($this->piVars['mode']))	$this->piVars['mode'] = 1;

		# Initializing the query parameters:
		$sorting = $this->conf['sorting'];
		# Number of results to show in a listing.
		$this->internal['results_at_a_time'] = t3lib_div::intInRange($lConf['results_at_a_time'], 0, 1000, 100);
		# The maximum number of "pages" in the browse-box: "Page 1", 'Page 2', etc.
		$this->internal['maxPages'] = t3lib_div::intInRange($lConf['maxPages'], 0, 1000, 2);
		$this->internal['searchFieldList'] = 'name, firstname, email, info, uid';
		$this->internal['orderByList'] = 'name, firstname, email, info, uid';
		$this->internal['orderBy'] = 'name, firstname';
		$this->internal['descFlag'] = 0;
		# Check for setting sort order via TypoScript
		if (isset($this->conf['sortSpeakerlist'])) {
			list($this->internal['orderBy'], $this->internal['descFlag']) = explode(':', $this->conf['sortSpeakerlist']);
		}

	    $where = ' AND ' . $this->internal['currentTable'] . '.sys_language_uid = 0';

		# Get number of records:
		$res = $this->pi_exec_query($this->internal['currentTable'], 1, $where, '', '', 'name');
		list($this->internal['res_count']) = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);

		# Make listing query, pass query to SQL database:
		$res = $this->pi_exec_query($this->internal['currentTable'], 0, $where);

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
		$template['total']      = $this->cObj->getSubpart($this->templateCode,    '###SPEAKERLIST###');
		$template['header']     = $this->cObj->getSubpart($template['total'],     '###HEADER###');
		$template['singlerow']  = $this->cObj->getSubpart($template['total'],     '###SINGLEROW###');
		$template['row']        = $this->cObj->getSubpart($template['singlerow'], '###ITEM###');
		$template['row_alt']    = $this->cObj->getSubpart($template['singlerow'], '###ITEM_ALT###');
		$template['sessionrow'] = $this->cObj->getSubpart($template['singlerow'], '###SESSIONROW###');

		# Put the whole list together:
		$content_item = '';	# Clear var;

		# Get the column names
		$markerArray0 = Array();
		$markerArray0['###SPEAKERNAME###']  = $this->getFieldHeader('name');
		$markerArray0['###EMAILNAME###']    = $this->getFieldHeader('email');
		$markerArray0['###COUNTRYNAME###']  = $this->getFieldHeader('country');
		$markerArray0['###COMPANYNAME###']  = $this->getFieldHeader('company');
		$markerArray0['###INFONAME###']     = $this->getFieldHeader('info');
		$markerArray0['###IMAGENAME###']    = $this->getFieldHeader('image');
		$markerArray0['###SESSIONSNAME###'] = $this->getFieldHeader('speakersessions');

		$subpartArray['###HEADER###']       = $this->cObj->substituteMarkerArrayCached($template['header'], $markerArray0);

		$switch_row = 0;
		if ($res) {
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				# Get overload workspace record
				$GLOBALS['TSFE']->sys_page->versionOL($this->internal['currentTable'], &$row);
				# fix pid for record from workspace
				$GLOBALS['TSFE']->sys_page->fixVersioningPid($this->internal['currentTable'], &$row);
				# Get overload language record
				if ($GLOBALS['TSFE']->sys_language_content) {
					$row = $GLOBALS['TSFE']->sys_page->getRecordOverlay($this->internal['currentTable'],
						$row, $GLOBALS['TSFE']->sys_language_content,
						$GLOBALS['TSFE']->sys_language_contentOL, '');
				}
				$this->internal['currentRow'] = $row;
				# Check if the speaker has a session on this event
				$sessionids = $this->getSpeakerSessionList($this->internal['currentRow']['uid'], $this->conf['pidListEvents']);

				# display only speaker with sessions
				if (!empty($sessionids)) {
					# Check if link to detail view is set
					if (!empty($this->conf['singleSpeaker'])) {
					    $label = $this->getFieldContent('name');  # the link text
					    $overrulePIvars = '';//array('session' => $this->getFieldContent('uid'));
					    $overrulePIvars = array('showSpeakerUid' => $this->internal['currentRow']['uid'], 'backUid' => $GLOBALS['TSFE']->id);
					    $clearAnyway = 1;    # the current values of piVars will NOT be preserved
					    $altPageId = $this->conf['singleSpeaker'];      # ID of the target page, if not on the same page
						$this->setCache();
					    $speakername = $this->pi_linkTP_keepPIvars($label, $overrulePIvars, $this->use_cache, $clearAnyway, $altPageId);
					} else {
						$speakername = $this->getFieldContent('name');
					}

					# remember sessionids for getFieldContent
					$this->internal['speakersessions'] = $sessionids;

					# Build content from template + array
					$markerArray = Array();
					$markerArray['###SPEAKERNAME###'] = $speakername;
					$markerArray['###IMAGENAME###'] = $this->getFieldContent('name');
					$markerArray['###EMAILNAME###'] = $this->getFieldHeader('email');
					$markerArray['###EMAILDATA###'] = $this->getFieldContent('email');
					$markerArray['###COUNTRYNAME###'] = $this->getFieldHeader('country');
					$markerArray['###COUNTRYDATA###'] = $this->getFieldContent('country');
					$markerArray['###COMPANYNAME###'] = $this->getFieldHeader('company');
					$markerArray['###COMPANYDATA###'] = $this->getFieldContent('company');
					$markerArray['###COMPANYLINK###'] = 'http://' . $this->getFieldContent('companylink');
					$markerArray['###SESSIONSNAME###'] = $this->getFieldHeader('speakersessions');
					$markerArray['###SESSIONS###'] = $this->getFieldContent('speakersessions');
					$markerArray['###INFONAME###'] = $this->getFieldHeader('info');
					$markerArray['###INFODATA###'] = $this->cObj->stdWrap($this->getFieldContent('info'),
						$this->conf['infodata_stdWrap.']);
					$markerArray['###IMAGENAME###'] = $this->getFieldHeader('image');

					$image = trim($this->getFieldContent('image'));
					if (!empty($image)) {
						$img = $this->conf['image.'];
						if (empty($img)) {
						    $img['file'] = 'GIFBUILDER';
							$img['file.']['XY'] = '100, 150';
							$img['file.']['5'] = 'IMAGE';
						}
						$img['file.']['5.']['file'] = $uploadDirectory . '/' . $image;
						$markerArray['###IMAGELINK###'] = $this->cObj->IMAGE($img);
						$markerArray['###IMAGEFILE###'] = $uploadDirectory . '/' . $image;
					} else {
						$markerArray['###IMAGELINK###'] = '';
						$markerArray['###IMAGEFILE###'] = '';
					}

					# For every session get information
					$sess_content_item = '';
					foreach (explode(',', $sessionids) as $k){
						# Get session data record
						$sessdata = $this->pi_getRecord('tx_wseevents_sessions', $k);
						# Get overload language record
						if ($GLOBALS['TSFE']->sys_language_content) {
							$sessdata = $GLOBALS['TSFE']->sys_page->getRecordOverlay('tx_wseevents_sessions',
								$sessdata, $GLOBALS['TSFE']->sys_language_content,
								$GLOBALS['TSFE']->sys_language_contentOL, '');
						}

						$label = $sessdata['name'];
						if (!empty($this->conf['singleSession'])) {
							$overrulePIvars = '';//array('session' => $this->getFieldContent('uid'));
							$overrulePIvars = array('showSessionUid' => $k, 'backUid' => $GLOBALS['TSFE']->id);
							$clearAnyway = 1;    # the current values of piVars will NOT be preserved
							$altPageId = $this->conf['singleSession'];      # ID of the target page, if not on the same page
							$this->setCache();
							$sessionname = $this->pi_linkTP_keepPIvars($label, $overrulePIvars,
								$this->use_cache, $clearAnyway, $altPageId);
						} else {
							$sessionname = $label;
						}

						# Build content from template + array
						$markerarray1 = array();
						$markerArray1['###SESSIONNAME###'] = $sessionname;
						$markerArray1['###SESSIONTEASER###'] = $sessdata['teaser'];
						$markerArray1['###SESSIONDESCRIPTION###'] = $this->cObj->stdWrap($sessdata['description'],
							$this->conf['sessiondescription_stdWrap.']);
						$datacat  = $this->pi_getRecord('tx_wseevents_categories', $sessdata['category']);
						$markerArray1['###SESSIONNUMBER###'] = $datacat['shortkey'] . sprintf('%02d', $sessdata['number']);
						$markerArray1['###SESSIONCATEGORY###'] = $sessdata['category'];
						$markerArray1['###SESSIONCATEGORYKEY###'] = $datacat['shortkey'];
						$markerArray1['###SESSIONCATEGORYCOLOR###'] = $datacat['color'];
						# Get time slot info
						$tscontent = '';
						if (0 == $this->internal['hideTimeslots']) {
							foreach (explode(',', $sessdata['timeslots']) as $ts){
								$tsdata = $this->pi_getRecord('tx_wseevents_timeslots', $ts);
								$timeslotname = tx_wseevents_timeslots::formatSlotName($tsdata);
								if (!empty($tscontent)) {
									$tscontent .= $this->internal['slotdelimiter'] . $timeslotname;
								} else {
									$tscontent = $timeslotname;
								}
							}
						}
						$markerArray1['###SESSIONSLOTS###'] = $tscontent;

						$sess_content_item .= $this->cObj->substituteMarkerArrayCached($template['sessionrow'], $markerArray1);
					}
					$subpartArraySession['###SESSIONROW###'] = $sess_content_item;
					if (0 == $switch_row) {
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
	 * @param	string		$content: default content string, ignore
	 * @param	array		$conf: TypoScript configuration for the plugin
	 * @return	string		Content for output on the web site
	 */
	function listTimeslotView($content, $conf)	{
		$this->conf=$conf;		# Setting the TypoScript passed to this function in $this->conf
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();		# Loading the LOCAL_LANG values
		$index = $GLOBALS['TSFE']->sys_language_uid;

		$lConf = $this->conf['listView.'];	# Local settings for the listView function

		# Set table to session table
		$this->internal['currentTable'] = 'tx_wseevents_sessions';

		if (!isset($this->piVars['pointer'])) $this->piVars['pointer']=0;
		if (!isset($this->piVars['mode'])) $this->piVars['mode']=1;

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

		# Check for given width of time column
		$timecolwidth = $conf['listTimeslotView.']['timeColWidth'];
		if (empty($timecolwidth)) {
			$timecolwidth = 0;
		}

		# Check for given width of column between days in "All days" view
		if (0 == $showday) {
			$daydelimwidth = $conf['listTimeslotView.']['dayDelimWidth'];
		}
		if (empty($daydelimwidth)) {
			$daydelimwidth = 0;
		}

		# Check for given width of event titles
		$teaserwidth = $conf['listTimeslotView.']['teaserWidth'];
		if (empty($teaserwidth)) {
			$teaserwidth = 0;
		}

		# For debugging output used in development
		$showdebug = $conf['listTimeslotView.']['debug'];
		if (empty($showdebug)) {
			$showdebug = 0;
		}

		# For debugging SQL output used in development
		$showdebugsql = $conf['listTimeslotView.']['debugsql'];
		if (empty($showdebugsql)) {
			$showdebugsql = 0;
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
		$template['total']     = $this->cObj->getSubpart($this->templateCode, '###SLOTSDAY###');
		if ((empty($template['total'])) or (0 == $showday)) {
			$template['total']     = $this->cObj->getSubpart($this->templateCode, '###SLOTSALL###');
		}
		$template['titlerow']       = $this->cObj->getSubpart($template['total'],      '###TITLEROW###');
		$template['select']         = $this->cObj->getSubpart($template['titlerow'],   '###SELECT###');
		$template['titlecol']       = $this->cObj->getSubpart($template['titlerow'],   '###TITLECOLUMN###');
		$template['evtsection']     = $this->cObj->getSubpart($template['total'],      '###EVENTSELECT###');
		$template['evtselect']      = $this->cObj->getSubpart($template['evtsection'], '###SELECT###');
		$template['evtoption']      = $this->cObj->getSubpart($template['evtselect'],  '###OPTIONNOTSELECTED###');
		$template['evtoptionsel']   = $this->cObj->getSubpart($template['evtselect'],  '###OPTIONSELECTED###');
		$template['option']         = $this->cObj->getSubpart($template['select'],     '###OPTIONNOTSELECTED###');
		$template['optionsel']      = $this->cObj->getSubpart($template['select'],     '###OPTIONSELECTED###');
		$template['headerrow']      = $this->cObj->getSubpart($template['total'],      '###HEADERROW###');
		$template['headercol']      = $this->cObj->getSubpart($template['headerrow'],  '###HEADERCOLUMN###');
		$template['headercolempty'] = $this->cObj->getSubpart($template['headerrow'],  '###HEADERCOLUMNEMPTY###');
		$template['slotrow']        = $this->cObj->getSubpart($template['total'],      '###SLOTROW###');
		$template['timecol']        = $this->cObj->getSubpart($template['slotrow'],    '###TIMECOLUMN###');
		$template['timecolfree']    = $this->cObj->getSubpart($template['slotrow'],    '###TIMECOLUMNEMPTY###');
		$template['slotcol']        = $this->cObj->getSubpart($template['slotrow'],    '###SLOTCOLUMN###');
		$template['slotcolempty']   = $this->cObj->getSubpart($template['slotrow'],    '###SLOTCOLUMNEMPTY###');

		# Check for event selection in URL
		$showevent = $this->piVars['showEvent'];
		if (empty($showevent)) {
			$showevent = 0;
		}

		# Check for amount of events
		$this->conf['pidList'] = $this->conf['pidListEvents'];
	    $where1 = ' AND sys_language_uid = 0';
		if (1 == $showdebugsql) { echo 'SQL1:' . $where1 . '<br>'; };
		$res = $this->pi_exec_query('tx_wseevents_events', 1, $where1, '', '', 'name, uid');
		list($eventcount) = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);

		# Create template data for event combobox
		$event_item = '';	# Clear var;
		$markerArray = array();
		# Make listing query, pass query to SQL database:
		if (1 == $showdebugsql) { echo 'SQL2:' . $where1 . '<br>'; };
		$res = $this->pi_exec_query('tx_wseevents_events', 0, $where1);
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			# Get overload workspace record
			$GLOBALS['TSFE']->sys_page->versionOL('tx_wseevents_events', &$row);
			# fix pid for record from workspace
			$GLOBALS['TSFE']->sys_page->fixVersioningPid('tx_wseevents_events', &$row);
			# Get overload language record
			if ($GLOBALS['TSFE']->sys_language_content) {
				$row = $GLOBALS['TSFE']->sys_page->getRecordOverlay('tx_wseevents_events',
					$row, $GLOBALS['TSFE']->sys_language_content,
					$GLOBALS['TSFE']->sys_language_contentOL, '');
			}
			# Take the first event as selected if no event is selected in the URL
			if (0 == $showevent) {
				$showevent = $row['uid'];
			}
			$eventname = $row['name'];
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
		if (1 < $eventcount) {
			# Set select options
			$subpartArray1['###SELECT###'] = $event_item;
			# Set label for selection box
			$markerArray1['###LABEL###'] = $this->pi_getLL('tx_wseevents_sessions.chooseeventday', '[Choose event day]');
			//$markerArray1['###FORMACTION###'] = $this->pi_getPageLink($GLOBALS['TSFE']->page['uid']);
			$markerArray1['###FORMSELECT###'] = $this->prefixId . '[showEvent]';
			$markerArray1['###FORMSEND###'] = htmlspecialchars($this->pi_getLL('tx_wseevents_sessions.showselection', '[Show selection]'));
			$subpartArray['###EVENTSELECT###'] = $this->cObj->substituteMarkerArrayCached($template['evtsection'], $markerArray1, $subpartArray1);
		} else {
			$subpartArray['###EVENTSELECT###'] = '';
		}
		# show only sessions of selected event
		if (0 < $showevent) {
			$where .= ' AND event=' . $showevent;
		}
		# Get event info
		$event = $this->getEventInfo($showevent);

		# Create template data for eventday combobox
		$content_select = '';	# Clear var;
		$markerArray['###VALUE###'] = 0;
		$markerArray['###OPTION###'] = $this->pi_getLL('tx_wseevents_sessions.choosealldays', '[-All-]');
		if (0 == $showday) {
			$content_select .= $this->cObj->substituteMarkerArrayCached($template['optionsel'], $markerArray);
		} else {
			$content_select .= $this->cObj->substituteMarkerArrayCached($template['option'], $markerArray);
		}

		# Get date format for selected language
#$content .= t3lib_div::view_array($conf);
#$content .= 'index=' . $index . '<br>';
#$content .= 'conf fmtDate=' . $conf[$index . '.']['fmtDate'] . '<br>';
		if (!$conf[$index . '.']['fmtDate']){
			$conf['strftime'] = '%d.%m.%Y';
		} else {
			$conf['strftime'] = $conf[$index . '.']['fmtDate'];
		}
#$content .= 'conf strftime=' . $conf['strftime'] . '<br>';
		# Get count of days and name of days
		$secofday = 60*60*24;
		$daycount = $event['length'];
		for ( $d = 1; $d <= $daycount; $d++ ) {
			$thisday = $event['begin']+($d-1)*$secofday;
#ToDo: Determine the weekday and format the Date with TYPO3 functions
#			setlocale(LC_TIME, 'de_DE');

			$dayname[$d] = strftime($conf['strftime'], $thisday);
			$weekdays[$d] = strftime('%A', $thisday);

			# Set one event day  option
			$markerArray['###VALUE###'] = $d;
			$markerArray['###OPTION###'] = $weekdays[$d] . ' - ' . $dayname[$d];
			if ($showday==$d) {
				$content_select .= $this->cObj->substituteMarkerArrayCached($template['optionsel'], $markerArray);
			} else {
				$content_select .= $this->cObj->substituteMarkerArrayCached($template['option'], $markerArray);
			}
		}

		# Get count of rooms and name of rooms
		if (1 == $showdebugsql) { echo 'getRoomInfo:' . $event['location'] . '<br>'; };
		$rooms = $this->getRoomInfo($event['location']);
		$roomcount = count($rooms);
		$roomids = '';
		for ( $r = 1; $r <= $roomcount; $r++ ) {
			$roomname[$r] = $rooms[$r]['name'];
			if (empty($roomids)) {
				$roomids = $rooms[$r]['uid'];
			} else {
				$roomids .= ',' . $rooms[$r]['uid'];
			}
		}
#$content .= t3lib_div::view_array($GLOBALS['TSFE']->config['config']);
#$content .= t3lib_div::view_array($GLOBALS['TSFE']);
		# Create a list with the times of slot begins
		$timeoffsetGMT = date('O');
		$timeoffset = date('Z');
		# Get begin of slots
		$timebegin = $event['timebegin'];
		list($t_hr, $t_min) = explode(':', $timebegin);
#		$t_start = ($t_hr*60 +$t_min)*60;
		$t_start = strtotime($timebegin);
		# Get end of slots
		$timeend   = $event['timeend'];
		list($t_hr, $t_min) = explode(':', $timeend);
#		$t_end = ($t_hr*60 +$t_min)*60;
		$t_end = strtotime($timeend);
		# Get count of slots
		$slotlen = $event['slotsize']*60;
		$slotcount = ($t_end - $t_start)/$slotlen;
		for ( $s = 1; $s <= $slotcount+1; $s++ ) {
			$slotname[$s] = 'Slot ' . $s;
			$slotbegin[$s] = date('H:i', (($s-1)*$slotlen+$t_start));
			// %H:%M
		}

#$content .= '<br>Zeitzone' . date('T Z O') . '<br>';
#$content .= 'Start=' . $timebegin . '<br>Sekunden=' . $t_start . '<br>';
#$content .= 'Ende=' . $timeend . '<br>Sekunden=' . $t_end . '<br>';
#$content .= 'Slotlen=' . $slotlen . '<br>Slotcount=' . $slotcount . '<br>';

		// Calculate column width if enabled
		if (0 < $timecolwidth) {
			if (0 == $showday) {
				$columncount = $daycount * $roomcount;
			} else {
				$columncount = $roomcount;
			}
			if (0 == $columncount) {
				$columncount = 1;
			}
			$slotcolwidth = (100 - $timecolwidth - (($daycount-1) * $daydelimwidth)) / $columncount;
		}

		# Here the output begins
		$content_title = '';
		$content_header = '';
		$content_slot = '';
		# Loop over all days
		for ( $d = 1; $d <= $daycount; $d++ ) {
			if (($showday == $d) or (0 == $showday)) {
				$markerArray = array();
				$markerArray['###ROOMCOUNT###'] = $roomcount;
				$markerArray['###TITLEDAY###'] = $dayname[$d];
				$markerArray['###TITLEWEEKDAY###'] = $weekdays[$d];
				# Add column width if enabled
				if (0 < $timecolwidth) {
					$markerArray['###COLUMNWIDTH###']  = ($slotcolwidth * $roomcount) . '%';
				}
				$content_title .= $this->cObj->substituteMarkerArrayCached($template['titlecol'], $markerArray);

				# Loop over all rooms
				for ( $r = 1; $r <= $roomcount; $r++ ) {
					$markerArray = array();
					$markerArray['###HEADERROOM###'] = $roomname[$r];
					# Add column width if enabled
					if ($timecolwidth>0) {
						$markerArray['###COLUMNWIDTH###']  = $slotcolwidth . '%';
					}
					$content_header .= $this->cObj->substituteMarkerArrayCached($template['headercol'], $markerArray);
				}

				# Insert space between days if defined
				if ((0 == $showday) and ($d<$daycount)) {
					if (0 < $daydelimwidth) {
						$markerArray = array();
						$markerArray['###COLUMNWIDTH###']  = $daydelimwidth . '%';
						$content_title .= $this->cObj->substituteMarkerArrayCached($template['headercolempty'], $markerArray);
						$content_header .= $this->cObj->substituteMarkerArrayCached($template['headercolempty'], $markerArray);
					}
				}
			}
		}

		# Loop over all slots of a day
		for ( $s = 1; $s <= $slotcount; $s++ ) {
			$content_slotrow = '';
			# Loop over all days
			for ( $d = 1; $d <= $daycount; $d++ ) {
				if (($showday==$d) or (0 == $showday)) {
					# Loop over all rooms
					$allrooms = false;
					for ( $r = 1; $r <= $roomcount; $r++ ) {
						if (0 < $showdebug) {
							$content_slotrow .= LF . '<!-- s=' . $s . ' d=' . $d . ' r=' . $r . ' -->';
						}
						if (1 == $showdebugsql) { echo '<br>getSlot:' . $showevent . ', ' . $d . ', ' . $rooms[$r]['uid'] . ', ' . $s . '<br>'; };
						$slot_id = $this->getSlot($showevent, $d, $rooms[$r]['uid'], $s, $showdebugsql);
						if (1 == $r && empty($slot_id) && !$allrooms) {
							# Check if a slot is assigned for all rooms
							if (1 == $showdebugsql) { echo 'getSlot:' . $showevent . ', ' . $d . ', 0, ' . $s . '<br>'; };
							$slot_id = $this->getSlot($showevent, $d, 0, $s, $showdebugsql);
							$allrooms = true;
						}
						if (!empty($slot_id)) {
							if (1 == $showdebugsql) { echo 'getSlotLength:' . $slot_id . '<br>'; };
							$slot_len = $this->getSlotLength($slot_id);
							if (1 == $showdebugsql) { echo 'slot_len:' . $slot_len . '<br>getSlotSession:' . $slot_id . '<br>'; };
							$sessiondata = $this->getSlotSession($slot_id);
							if (1 == $showdebugsql) { echo 'sessiondata:' . $sessiondata . '<br>'; };
							if (!empty($sessiondata)) {
							    $label = $sessiondata['catnum'];  # the link text
							    $overrulePIvars = '';//array('session' => $this->getFieldContent('uid'));
							    $overrulePIvars = array('showSessionUid' => $sessiondata['uid'], 'backUid' => $GLOBALS['TSFE']->id);
							    $clearAnyway = 1;    # the current values of piVars will NOT be preserved
							    $altPageId = $this->conf['singleSession'];      # ID of the target page, if not on the same page
								$this->setCache();
								if (!t3lib_div::inList($hidecat, $sessiondata['catkey'])) {
									$sessionlink = $this->pi_linkTP_keepPIvars($label, $overrulePIvars,
										$this->use_cache, $clearAnyway, $altPageId);
								} else {
									$sessionlink = '';
								}
							    $label = $sessiondata['name'];  # the link text
							    $sessionlinkname = $this->pi_linkTP_keepPIvars($label, $overrulePIvars,
									$this->use_cache, $clearAnyway, $altPageId);
								$markerArray = array();
								$markerArray['###SLOTNAME###'] = $sessiondata['name'];
								$markerArray['###SLOTCATEGORY###'] = $sessiondata['category'];
								$markerArray['###SLOTCATEGORYKEY###'] = $sessiondata['catkey'];
								$markerArray['###SLOTCATEGORYCOLOR###'] = $sessiondata['catcolor'];
								$markerArray['###SLOTLINK###'] = $sessionlink;
								$markerArray['###SLOTLINKNAME###'] = $sessionlinkname;
								$markerArray['###SLOTSESSION###'] = $sessiondata['catnum'];
								# Cut teaser if longer than max teaser width
								if (0 < $teaserwidth) {
//									$markerArray['###SLOTTEASER###'] = substr($sessiondata['teaser'], 0, $teaserwidth) . '...';
									$markerArray['###SLOTTEASER###'] = $GLOBALS['TSFE']->csConvObj->crop(
																		$GLOBALS['TSFE']->renderCharset,
																		$sessiondata['teaser'],
																		$teaserwidth, '...');
								} else {
									$markerArray['###SLOTTEASER###'] = $sessiondata['teaser'];
								}
								# ToDo: Ticket #11
								# Get speaker list of session
								$markerArray['###SLOTSPEAKER###'] = $this->getSpeakerNames($sessiondata['speaker']);
							} else {
								$markerArray = array();
								if (0 == $hidenotassigned) {
									$markerArray['###SLOTNAME###'] = $this->pi_getLL('tx_wseevents_sessions.slot_notassigned');
									$markerArray['###SLOTSESSION###'] = $this->pi_getLL('tx_wseevents_sessions.slot_notassigned');
									$markerArray['###SLOTTEASER###'] = $this->pi_getLL('tx_wseevents_sessions.slot_notassigned');
									$markerArray['###SLOTSPEAKER###'] = '';
								} else {
									$markerArray['###SLOTNAME###'] = '';
									$markerArray['###SLOTSESSION###'] = '';
									$markerArray['###SLOTTEASER###'] = '';
									$markerArray['###SLOTSPEAKER###'] = '';
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
							# Add column width if enabled
							if (0 < $timecolwidth) {
								$markerArray['###COLUMNWIDTH###']  = ($slotcolwidth * $slotwidth) . '%';
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
								if (0 == $hidenotdefined) {
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
								# Add column width if enabled
								if ($timecolwidth>0) {
									$markerArray['###COLUMNWIDTH###']  = $slotcolwidth . '%';
								}
								$content_slotrow .= $this->cObj->substituteMarkerArrayCached($template['slotcolempty'], $markerArray);
							}
						}
					}
					if ((0 == $showday) and ($d < $daycount)) {
						if (0 < $daydelimwidth) {
							$markerArray = array();
/*
							$markerArray['###SLOTDAY###'] = '';
							$markerArray['###SLOTROOM###'] = '';
							$markerArray['###SLOTNUM###'] = '';
							$markerArray['###SLOTBEGIN###'] = '';
							$markerArray['###SLOTEND###'] = '';
							$markerArray['###SLOTNAME###'] = '';
							$markerArray['###SLOTSESSION###'] = '';
							$markerArray['###SLOTTEASER###'] = '';
							$markerArray['###SLOTCATEGORYKEY###'] = '';
							$markerArray['###SLOTCATEGORYCOLOR###'] = $catcolor_notdefined;
							$markerArray['###SLOTLINK###'] = '';
*/
							$markerArray['###SLOTCATEGORY###'] = 0;
							$markerArray['###SLOTSIZE###'] = 1;
							$markerArray['###SLOTWIDTH###'] = 1;
							$markerArray['###COLUMNWIDTH###']  = $daydelimwidth . '%';
							$content_slotrow .= $this->cObj->substituteMarkerArrayCached($template['slotcolempty'], $markerArray);
						}
					}
				}
			}
			$subpartArray1['###SLOTCOLUMN###'] = $content_slotrow;
			$subpartArray1['###SLOTCOLUMNEMPTY###'] = '';
			# Column with Start and end time
			$markerArray = array();
			$content_timecol = '';
			$content_timecolfree = '';
			if (0 == $roomtime) {
				$markerArray['###SLOTBEGIN###'] = $slotbegin[$s];
				$markerArray['###SLOTEND###']   = $slotbegin[$s+1];
				$markerArray['###SLOTSIZE###']  = 1;
				# Add column width if enabled
				if (0 < $timecolwidth) {
					$markerArray['###COLUMNWIDTH###']  = $timecolwidth . '%';
				}
				$content_timecol = $this->cObj->substituteMarkerArrayCached($template['timecol'], $markerArray);
			} else {
				if (0 < $showday) {
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
					if (0 < $slot_len) {
						$markerArray['###SLOTBEGIN###'] = $slotbegin[$s];
						$markerArray['###SLOTEND###']   = $slotbegin[$s+$slot_len];
						$markerArray['###SLOTSIZE###']  = $slot_len;
						# Add column width if enabled
						if (0 < $timecolwidth) {
							$markerArray['###COLUMNWIDTH###']  = $timecolwidth . '%';
						}
						$content_timecol = $this->cObj->substituteMarkerArrayCached($template['timecol'], $markerArray);
					}
				} else {
					$markerArray['###SLOTBEGIN###'] = $slotbegin[$s];
					$markerArray['###SLOTEND###']   = $slotbegin[$s+1];
					$markerArray['###SLOTSIZE###']  = 1;
					# Add column width if enabled
					if (0 < $timecolwidth) {
						$markerArray['###COLUMNWIDTH###']  = $timecolwidth . '%';
					}
					$content_timecolfree = $this->cObj->substituteMarkerArrayCached($template['timecolfree'], $markerArray);
				}
			}
			# Add debug output if enabled
			if (0 < $showdebug) {
				$content_timecol = LF . '<!-- s=' . $s . ' d=' . $d . ' timecol -->' . $content_timecol;
				$content_timecolfree = LF . '<!-- s=' . $s . ' d=' . $d . ' timecolfree -->' . $content_timecolfree;
			}
			$subpartArray1['###TIMECOLUMN###'] = $content_timecol;
			$subpartArray1['###TIMECOLUMNEMPTY###'] = $content_timecolfree;

			$content_slot .= $this->cObj->substituteMarkerArrayCached($template['slotrow'], $markerArray, $subpartArray1);
		}

		$subpartArray['###SLOTROW###']  = $content_slot;



		$subpartArray1['###HEADERCOLUMN###'] = $content_header;
		$subpartArray1['###HEADERCOLUMNEMPTY###'] = '';
		$markerArray = array();
		if (0 == $hidetime) {
			$markerArray['###HEADERBEGIN###'] = $this->pi_getLL('tx_wseevents_sessions.slot_titlebegin', 'Time');
		} else {
			$markerArray['###HEADERBEGIN###'] = '';
		}
		# Add column width if enabled
		if (0 < $timecolwidth) {
			$markerArray['###COLUMNWIDTH###']  = $timecolwidth . '%';
		}
		$subpartArray['###HEADERROW###']  = $this->cObj->substituteMarkerArrayCached($template['headerrow'], $markerArray, $subpartArray1);

		$subpartArray1['###TITLECOLUMN###'] = $content_title;
		$subpartArray1['###SELECT###'] = $content_select;
		$markerArray = array();
		$markerArray['###TITLEBEGIN###'] = '';
		# Add column width if enabled
		if (0 < $timecolwidth) {
			$markerArray['###COLUMNWIDTH###']  = $timecolwidth . '%';
		}
		$markerArray['###LABEL###'] = $this->pi_getLL('tx_wseevents_sessions.chooseeventday', '[Choose event day]');
		$markerArray['###FORMACTION###'] = $this->pi_getPageLink($GLOBALS['TSFE']->page['uid']);
		$markerArray['###FORMSELECT###'] = $this->prefixId . '[showDay]';
		$markerArray['###FORMSEND###'] = htmlspecialchars($this->pi_getLL('tx_wseevents_sessions.showselection', '[Show selection]'));
		$subpartArray['###TITLEROW###']  = $this->cObj->substituteMarkerArrayCached($template['titlerow'], $markerArray, $subpartArray1);

#ToDo: At this point the selection (combo) box must be put into the template.

		$content .= $this->cObj->substituteMarkerArrayCached($template['total'], array(), $subpartArray);
		return $content;
	}









	/**
	 * Display the details of a single session
	 *
	 * @param	string		$content: default content string, ignore
	 * @param	array		$conf: TypoScript configuration for the plugin
	 * @return	string		Content for output on the web site
	 */
	function singleSessionView($content, $conf)	{
		$this->conf=$conf;
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();

		if (isset($this->piVars['showSessionUid'])) {
			$this->internal['currentRow'] = $this->pi_getRecord($this->internal['currentTable'],
				$this->piVars['showSessionUid']);
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
		$template['total'] = $this->cObj->getSubpart($this->templateCode, '###SESSIONVIEW###');

		# This sets the title of the page for use in indexed search results:
		if ($this->internal['currentRow']['title'])	$GLOBALS['TSFE']->indexedDocTitle=$this->internal['currentRow']['title'];

		# Check if target for documents link is set, if not use the default target
		if (!isset($conf['documentsTarget'])) {
			$this->documentsTarget = 'target="_blank"';
		} else {
			$this->documentsTarget = $conf['documentsTarget'];
		}
		# Check for delimiter between the documents
		if (!isset($conf['documentsdelimiter'])) {
			$this->internal['documentsdelimiter'] = '<br />';
		} else {
			$this->internal['documentsdelimiter'] = $conf['documentsdelimiter'];
		}

		# Link for back to list view
		$label = $this->pi_getLL('back', 'Back');  # the link text
		$overrulePIvars = array ();
		$clearAnyway = 1;    # the current values of piVars will NOT be preserved
		$altPageId = $this->piVars['backUid'];      # ID of the view page
		$this->setCache();
		if (0 < $altPageId) {
			$backlink = $this->pi_linkTP_keepPIvars($label, $overrulePIvars, $this->use_cache, $clearAnyway, $altPageId);
		} else {
			$backlink = '';
		}

		$markerArray['###SESSIONNAME###'] = $this->getFieldContent('name');
		$markerArray['###SESSIONNUMBER###'] = $this->getFieldContent('number');

		$datacat = $this->pi_getRecord('tx_wseevents_categories', $this->internal['currentRow']['category']);
		$markerArray['###SESSIONCATEGORY###'] = $this->internal['currentRow']['category'];
		$markerArray['###SESSIONCATEGORYKEY###'] = $datacat['shortkey'];
		$markerArray['###SESSIONCATEGORYCOLOR###'] = $datacat['color'];

		$markerArray['###SESSIONTEASERNAME###'] = $this->getFieldHeader('teaser');
		$markerArray['###SESSIONTEASER###'] = $this->getFieldContent('teaser');
		$markerArray['###SPEAKERNAME###'] = $this->getFieldHeader('speaker');
		$markerArray['###SPEAKERDATA###'] = $this->getFieldContent('speaker');
		$markerArray['###TIMESLOTSNAME###'] = $this->getFieldHeader('timeslots');
		$markerArray['###TIMESLOTSDATA###'] = $this->getFieldContent('timeslots');
		$markerArray['###SESSIONDESCRIPTIONNAME###'] = $this->getFieldHeader('description');
		$markerArray['###SESSIONDESCRIPTION###'] = $this->getFieldContent('description');
		$markerArray['###SESSIONDOCUMENTSNAME###'] = $this->getFieldHeader('documents');
		$markerArray['###SESSIONDOCUMENTS###'] = $this->getFieldContent('documents');
		$markerArray['###BACKLINK###'] = $backlink;

//		$this->pi_getEditPanel();

		return $this->cObj->substituteMarkerArrayCached($template['total'], $markerArray);;
	}









	/**
	 * Display the details of a single speaker
	 *
	 * @param	string		$content: default content string, ignore
	 * @param	array		$conf: TypoScript configuration for the plugin
	 * @return	string		Content for output on the web site
	 */
	function singleSpeakerView($content, $conf)	{
		$this->conf=$conf;
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();

		if (isset($this->piVars['showSpeakerUid'])) {
			$this->internal['currentRow'] = $this->pi_getRecord($this->internal['currentTable'],
				$this->piVars['showSpeakerUid']);
			#ToDo: t3lib_pageSelect::getRecordOverlay
		}

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
		$template['total'] = $this->cObj->getSubpart($this->templateCode, '###SPEAKERVIEW###');
		$template['sessionrow'] = $this->cObj->getSubpart($template['total'], '###SESSIONROW###');

		# This sets the title of the page for use in indexed search results:
		if ($this->internal['currentRow']['title'])	$GLOBALS['TSFE']->indexedDocTitle=$this->internal['currentRow']['title'];

		# Link for back to list view
		$label = $this->pi_getLL('back', 'Back');  # the link text
		$overrulePIvars = array ();
		$clearAnyway = 1;    # the current values of piVars will NOT be preserved
		$altPageId = $this->piVars['backUid'];      # ID of the view page
		$this->setCache();
		if (0 < $altPageId) {
			$backlink = $this->pi_linkTP_keepPIvars($label, $overrulePIvars, $this->use_cache, $clearAnyway, $altPageId);
		} else {
			$backlink = '';
		}

		# Check if the speaker has a session on this event
		if (isset($this->piVars['showSpeakerUid'])) {
			$sessionids = $this->getSpeakerSessionList($this->piVars['showSpeakerUid'], $this->conf['pidListEvents']);
		}

		$markerArray['###SPEAKERNAME###'] = $this->getFieldContent('name');
		$markerArray['###EMAILNAME###']   = $this->getFieldHeader('email');
		$markerArray['###EMAILDATA###']   = $this->getFieldContent('email');
		$markerArray['###COUNTRYNAME###'] = $this->getFieldHeader('country');
		$markerArray['###COUNTRYDATA###'] = $this->getFieldContent('country');
		$markerArray['###COMPANYNAME###'] = $this->getFieldHeader('company');
		$markerArray['###COMPANYDATA###'] = $this->getFieldContent('company');
		$markerArray['###COMPANYLINK###'] = 'http://' . $this->getFieldContent('companylink');
		$markerArray['###INFONAME###']    = $this->getFieldHeader('info');
		$markerArray['###INFODATA###']    = $this->getFieldContent('info');
		$markerArray['###IMAGENAME###']   = $this->getFieldHeader('image');

		$image = trim($this->getFieldContent('image'));
		if (!empty($image)) {
			$img = $this->conf['image.'];
			if (empty($img)) {
			    $img['file'] = 'GIFBUILDER';
				$img['file.']['XY'] = '100, 150';
				$img['file.']['5'] = 'IMAGE';
			}
			$img['file.']['5.']['file'] = $uploadDirectory . '/' . $image;
			$markerArray['###IMAGELINK###'] = $this->cObj->IMAGE($img);
			$markerArray['###IMAGEFILE###'] = $uploadDirectory . '/' . $image;
		} else {
			$markerArray['###IMAGELINK###'] = '';
			$markerArray['###IMAGEFILE###'] = '';
		}
		$markerArray['###SESSIONSNAME###'] = $this->getFieldHeader('speakersessions');
		$this->internal['speakersessions'] = $sessionids;
		$markerArray['###SESSIONS###'] = $this->getFieldContent('speakersessions');
		$markerArray['###BACKLINK###'] = $backlink;

		# For every session get information
		if ($sessionids) {
			foreach (explode(',', $sessionids) as $k){
				$sessdata = $this->pi_getRecord('tx_wseevents_sessions', $k);
				# Get overload language record
				if ($GLOBALS['TSFE']->sys_language_content) {
					$sessdata = $GLOBALS['TSFE']->sys_page->getRecordOverlay('tx_wseevents_sessions',
						$sessdata, $GLOBALS['TSFE']->sys_language_content,
						$GLOBALS['TSFE']->sys_language_contentOL, '');
				}
				$label = $sessdata['name'];
				if (!empty($this->conf['singleSession'])) {
					if (1 == $this->listview) {
						$overrulePIvars = array('showSessionUid' => $k, 'backUid' => $GLOBALS['TSFE']->id);
					} else {
						$overrulePIvars = array('showSessionUid' => $k);
					}
					$clearAnyway = 1;    # the current values of piVars will NOT be preserved
					$altPageId = $this->conf['singleSession'];      # ID of the target page, if not on the same page
					$this->setCache();
					$sessionname = $this->pi_linkTP_keepPIvars($label, $overrulePIvars,
						$this->use_cache, $clearAnyway, $altPageId);
				} else {
					$sessionname = $label;
				}

				# Build content from template + array
				$markerarray1 = array();
				$markerArray1['###SESSIONNAME###'] = $sessionname;
				$markerArray1['###SESSIONTEASER###'] = $sessdata['teaser'];
				$markerArray1['###SESSIONDESCRIPTION###'] = $this->cObj->stdWrap($sessdata['description'],
					$this->conf['sessiondescription_stdWrap.']);
				$sessdata = $this->pi_getRecord('tx_wseevents_sessions', $k);
				$datacat  = $this->pi_getRecord('tx_wseevents_categories', $sessdata['category']);
				$markerArray1['###SESSIONNUMBER###'] = $datacat['shortkey'] . sprintf('%02d', $sessdata['number']);
				$markerArray1['###SESSIONCATEGORY###'] = $sessdata['category'];
				$markerArray1['###SESSIONCATEGORYKEY###'] = $datacat['shortkey'];
				$markerArray1['###SESSIONCATEGORYCOLOR###'] = $datacat['color'];
				# Get time slot info
				$tscontent = '';
				foreach (explode(',', $sessdata['timeslots']) as $ts){
					$tsdata = $this->pi_getRecord('tx_wseevents_timeslots', $ts);
					$timeslotname = tx_wseevents_timeslots::formatSlotName($tsdata);
					if (!empty($tscontent)) {
						$tscontent .= $this->internal['slotdelimiter'] . $timeslotname;
					} else {
						$tscontent = $timeslotname;
					}
				}
				$markerArray1['###SESSIONSLOTS###'] = $tscontent;

				$content_item .= $this->cObj->substituteMarkerArrayCached($template['sessionrow'], $markerArray1);
			}
		}

//		$this->pi_getEditPanel();
		$subpartArray['###SESSIONROW###'] = $content_item;

		return $this->cObj->substituteMarkerArrayCached($template['total'], $markerArray, $subpartArray);
	}








	/**
	 * Get content of one field
	 *
	 * @param	string		$fN: field name
	 * @return	string		field content
	 */
	function getFieldContent($fN)	{
		if (0 >= intval($this->internal['currentRow']['uid'])) {
			return $this->internal['currentRow']['uid']; //'';
		}
		# get language overlay record for session table
		if ($this->internal['currentTable'] == 'tx_wseevents_sessions') {
			$sessdata = $this->internal['currentRow'];
			if ($GLOBALS['TSFE']->sys_language_content) {
				$sessdata = $GLOBALS['TSFE']->sys_page->getRecordOverlay('tx_wseevents_sessions',
					$sessdata, $GLOBALS['TSFE']->sys_language_content,
					$GLOBALS['TSFE']->sys_language_contentOL, '');
			}
		}
		switch($fN) {
			case 'uid':
				return $this->pi_list_linkSingle($this->internal['currentRow'][$fN],
					$this->internal['currentRow']['uid'], $this->use_cache);
			break;

			case 'number':
				$datacat = $this->pi_getRecord('tx_wseevents_categories', $this->internal['currentRow']['category']);
				$datanum = $this->internal['currentRow'][$fN];
				return $datacat['shortkey'] . sprintf ('%02d', $datanum);
			break;

			case 'name':
				switch ($this->internal['currentTable']) {
					case 'tx_wseevents_sessions':
						return $sessdata['name'];
					break;
					case 'tx_wseevents_speakers':
						if (!empty($this->internal['currentRow']['firstname'])) {
							if ((isset($this->conf['lastnameFirst'])) && (1 == $this->conf['lastnameFirst'])) {
								return $this->internal['currentRow']['name'] . ', '
									. $this->internal['currentRow']['firstname'];
							} else {
								return $this->internal['currentRow']['firstname'] . ' '
									. $this->internal['currentRow']['name'];
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
						return $sessdata['teaser'];
					break;
					default:
						return $this->internal['currentRow'][$fN];
					break;
				}
			break;

			case 'description':
				switch ($this->internal['currentTable']) {
					case 'tx_wseevents_sessions':
						$data = $sessdata['description'];
						return $this->pi_RTEcssText($data);
					break;
					default:
						return $this->internal['currentRow'][$fN];
					break;
				}
			break;

			case 'room':
				$data = $this->pi_getRecord('tx_wseevents_rooms', $this->internal['currentRow'][$fN]);
				return $data['name'];
			break;

			case 'speaker':
				foreach (explode(',', $this->internal['currentRow'][$fN]) as $k){
					$data = $this->pi_getRecord('tx_wseevents_speakers', $k);
					# Get the name and firstname
					if (!empty($data['firstname'])) {
						if (((isset($this->conf['lastnameFirst']))) && (1 == $this->conf['lastnameFirst'])) {
							$label =  $data['name'] . ', ' . $data['firstname'];
						} else {
							$label =  $data['firstname'] . ' ' . $data['name'];
						}
					} else {
						$label =  $data['name'];
					}

					if (!empty($this->conf['singleSpeaker'])) {
						if (1 == $this->listview) {
							$overrulePIvars = array('showSpeakerUid' => $data['uid'], 'backUid' => $GLOBALS['TSFE']->id);
						} else {
							$overrulePIvars = array('showSpeakerUid' => $data['uid']);
						}

					    $clearAnyway = 1;    # the current values of piVars will NOT be preserved
					    $altPageId = $this->conf['singleSpeaker'];      # ID of the target page, if not on the same page
						$this->setCache();
					    $speakername = $this->pi_linkTP_keepPIvars($label, $overrulePIvars,
							$this->use_cache, $clearAnyway, $altPageId);
						if (empty($label)) {
							$speakername = '';
						}
					} else {
					    $speakername = $label;
					}
					if (isset($content)) {
						$content .= $this->internal['speakerdelimiter'] . $speakername;
					} else {
						$content = $speakername;
					}
				}
				if (empty($content)) {
					$content = $this->pi_getLL('tx_wseevents_sessions.nospeakers', '[no speaker assigned]');
				}
				return $content;
			break;

			case 'speakersessions':
				foreach (explode(',', $this->internal['speakersessions']) as $k){
					$data = $this->pi_getRecord('tx_wseevents_sessions', $k);
					# Get overload language record
					if ($GLOBALS['TSFE']->sys_language_content) {
						$data = $GLOBALS['TSFE']->sys_page->getRecordOverlay('tx_wseevents_sessions',
							$data, $GLOBALS['TSFE']->sys_language_content,
							$GLOBALS['TSFE']->sys_language_contentOL, '');
					}

					$label = $data['name'];
					if (!empty($this->conf['singleSession'])) {
						if (1 == $this->listview) {
//							$overrulePIvars = '';//array('session' => $this->getFieldContent('uid'));
							$overrulePIvars = array('showSessionUid' => $data['uid'], 'backUid' => $GLOBALS['TSFE']->id);
						} else {
							$overrulePIvars = array('showSessionUid' => $data['uid']);
						}
					    $clearAnyway = 1;    # the current values of piVars will NOT be preserved
					    $altPageId = $this->conf['singleSession'];      # ID of the target page, if not on the same page
						$this->setCache();
					    $sessionname = $this->pi_linkTP_keepPIvars($label, $overrulePIvars,
							$this->use_cache, $clearAnyway, $altPageId);
					} else {
					    $sessionname = $label;
					}
					if (!empty($content)) {
						$content .= $this->internal['sessiondelimiter'] . $sessionname;
					} else {
						$content = $sessionname;
					}
					if (!empty($this->conf['singleSessionSlot'])) {
						# ToDo: Here the timeslots must be read and added to the content
					}
				}
				return $content;
			break;

			case 'timeslots':
				if (0 == $this->internal['hideTimeslots']) {
					foreach (explode(',', $this->internal['currentRow'][$fN]) as $k){
						$data = $this->pi_getRecord('tx_wseevents_timeslots', $k);
						$timeslotname = tx_wseevents_timeslots::formatSlotName($data);
						if (isset($content)) {
							$content .= $this->internal['slotdelimiter'] . $timeslotname;
						} else {
							$content = $timeslotname;
						}
					}
				}
				if (empty($content)) {
					$content = $this->pi_getLL('tx_wseevents_sessions.notimeslots', '[not yet sheduled]');
				}
				return $content;
			break;

			case 'info':
				switch ($this->internal['currentTable']) {
					case 'tx_wseevents_speakers':
						$data = $this->internal['currentRow'];
						# Get overload language record
						if ($GLOBALS['TSFE']->sys_language_content) {
							$data = $GLOBALS['TSFE']->sys_page->getRecordOverlay('tx_wseevents_speakers',
								$data, $GLOBALS['TSFE']->sys_language_content,
								$GLOBALS['TSFE']->sys_language_contentOL, '');
						}
						$field = $data['info'];
						return $this->pi_RTEcssText($field);
					break;
					default:
						return $this->internal['currentRow'][$fN];
					break;
				}
			break;

			case 'country':
				$data = $this->pi_getRecord('static_countries', $this->internal['currentRow'][$fN]);
				$iso = $data['cn_iso_3'];
				return $this->staticInfo->getStaticInfoName('COUNTRIES', $iso);
					// . ':' . $iso . ':' . $this->staticInfo->getCurrentLanguage();
			break;

			case 'documents':
				foreach (explode(',', $this->internal['currentRow'][$fN]) as $k){
					# ToDo: Ticket #15, #17
				    $documentsname = '<a href="uploads/tx_wseevents/' . $k . '" '
						. $this->documentsTarget . '>' . $k . '</a>';
					if (isset($doccontent)) {
						$doccontent .= $this->internal['documentsdelimiter'] . $documentsname;
					} else {
						$doccontent = $documentsname;
					}
				}
				# Check if any presentation handouts are available
				if (empty($this->internal['currentRow'][$fN])) {
					# if not then check for the date and get back a message if event is in the past
					$eventdate = date('Ymd', $this->eventrecord['begin']);
					$thisdate = date('Ymd');
					if ($thisdate>=$eventdate) {
						$doccontent = $this->pi_getLL('tx_wseevents_sessions.nohandout');
					}
				}
				return $doccontent;
			break;

			default:
				return $this->internal['currentRow'][$fN];
			break;
		}
	}




	/**
	 * Get list of session UIDs of a speaker for an event
	 *
	 * @param	integer		$speakerid: speaker id
	 * @param	integer		$eventPid: id of system folder with event data
	 * @return	string		comma seperated list of sessions for the speaker
	 */
	function getSpeakerSessionList($speakerid, $eventPid) {
		$where = 'sys_language_uid=0' . $this->cObj->enableFields('tx_wseevents_sessions') . ' AND pid=' . $eventPid;
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('speaker, uid', 'tx_wseevents_sessions', $where);
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			foreach (explode(',', $row['speaker']) as $k){
				if ($k==$speakerid) {
					if (empty($sessions)) {
						$sessions = $row['uid'];
					} else {
						$sessions .= ',' . $row['uid'];
					}
				}
			}
		}
		return $sessions;
	}




	/**
	 * Get label of one field from language file
	 *
	 * @param	string		$fN: field name
	 * @return	string		header for the field
	 */
	function getFieldHeader($fN)	{
		switch($fN) {
			default:
				return $this->pi_getLL($this->internal['currentTable'] . '.listFieldHeader_' . $fN, '[' . $fN . ']');
			break;
		}
	}




	/**
	 * Get info about an event
	 *
	 * @param	integer		$event: id of event
	 * @return	array		record data of event
	 */
	function getEventInfo($event) {
		$where = 'sys_language_uid=0 AND uid=' . $event . $this->cObj->enableFields('tx_wseevents_events');
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('name, location, begin, length, timebegin,
			timeend, slotsize, maxslot, defslotcount', 'tx_wseevents_events', $where);
		$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
		return $row;
	}


	/**
	 * Get info about rooms of an location
	 *
	 * @param	integer		$loc_id: id of location
	 * @return	array		array with record data of all rooms of a location
	 */
	function getRoomInfo($loc_id) {
		$where = 'sys_language_uid=0 AND location=' . $loc_id . $this->cObj->enableFields('tx_wseevents_rooms');
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid, name, comment, seats, number',
			'tx_wseevents_rooms', $where, 'number');
		$id = 1;
		$rows = array();
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$rows[$id] = $row;
			$id++;
		}
		return $rows;
	}


	/**
	 * Get id of record from time slot for given event, day, room and slot
	 *
	 * @param	integer		$event: id of event
	 * @param	integer		$day: number of the event day
	 * @param	integer		$room: number of the event location room
	 * @param	integer		$slot: number of time slot
	 * @param	integer		$showdbgsql: flag to show debug output of SQL query
	 * @return	integer		id of slot if a slot is found
	 */
	function getSlot($event, $day, $room, $slot, $showdbgsql) {
		$where = 'event=' . $event . ' AND eventday=' . $day . ' AND room=' . $room
			. ' AND begin=' . $slot . $this->cObj->enableFields('tx_wseevents_timeslots');
		if (1 == $showdbgsql) { echo 'getSlot where:' . $where . '<br>'; };
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'tx_wseevents_timeslots', $where);
		if ($res) {
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			if (1 == $showdbgsql) { echo 'getSlot return:' . $row['uid'] . '<br>'; };
			return $row['uid'];
		} else {
			if (1 == $showdbgsql) { echo 'getSlot return:0<br>'; };
			return 0;
		}
	}


	/**
	 * Get length of time slot for given uid
	 *
	 * @param	integer		$slot_id: id of a tme slot
	 * @return	integer		length of a time slot
	 */
	function getSlotLength($slot_id) {
		$where = 'uid=' . $slot_id . $this->cObj->enableFields('tx_wseevents_timeslots');
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('length', 'tx_wseevents_timeslots', $where);
		$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
		return $row['length'];
	}



	/**
	 * Get session data for given slot
	 *
	 * @param	integer		$slot_id: id of a time slot
	 * @return	array		array with record of session data
	 */
	function getSlotSession($slot_id) {
		$where = 'sys_language_uid=0';  // . $this->cObj->enableFields('tx_wseevents_sessions');
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid, name, category, number, teaser,
			timeslots, speaker', 'tx_wseevents_sessions', $where);
		# We must iterate thru all sessions to find the appropriate time slot
		# because the time slots are stored as a list in a blob field
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			# Get overload workspace record
			$GLOBALS['TSFE']->sys_page->versionOL('tx_wseevents_sessions', &$row);
			# fix pid for record from workspace
			$GLOBALS['TSFE']->sys_page->fixVersioningPid('tx_wseevents_sessions', &$row);
			# Get overload language record
			if ($GLOBALS['TSFE']->sys_language_content) {
				$row = $GLOBALS['TSFE']->sys_page->getRecordOverlay('tx_wseevents_sessions',
					$row, $GLOBALS['TSFE']->sys_language_content,
					$GLOBALS['TSFE']->sys_language_contentOL, '');
			}
			# Check for enabled fields
			$ctrl = $GLOBALS['TCA']['tx_wseevents_sessions']['ctrl'];
			if (is_array($ctrl)) {
				if (($ctrl['delete']) AND (1 == $row[$ctrl['delete']])) {
					unset ($row);
				}
				if (is_array($ctrl['enablecolumns'])) {
					if (($ctrl['enablecolumns']['disabled']) AND (1 == $row[$ctrl['enablecolumns']['disabled']])) {
						unset ($row);
					}
				}
			}
			if (t3lib_div::inList($row['timeslots'], $slot_id)) {
				$session = $row;
				# Get overload language record
				if ($GLOBALS['TSFE']->sys_language_content) {
					$session = $GLOBALS['TSFE']->sys_page->getRecordOverlay('tx_wseevents_sessions',
						$session, $GLOBALS['TSFE']->sys_language_content,
						$GLOBALS['TSFE']->sys_language_contentOL, '');
				}
				$datacat = $this->pi_getRecord('tx_wseevents_categories', $row['category']);
				$session['catnum'] = $datacat['shortkey'] . sprintf ('%02d', $row['number']);
				$session['catkey'] = $datacat['shortkey'];
				$session['catcolor'] = $datacat['color'];
			}
		}
		return $session;
	}




	/**
	 * Get speaker names for a list of speaker id's
	 * Check TS setting 'lastnameFirst' for "lastname, firstname" order
	 * Concat speaker with TS setting 'speakerdelimiter'
	 *
	 * @param	string		$speakerlist: list of speaker id's, comma separated
	 * @return	string		string with list of speakers
	 */
	function getSpeakerNames($speakerlist) {
		foreach (explode(',', $speakerlist) as $k){
			$data = $this->pi_getRecord('tx_wseevents_speakers', $k);
			# Get the name and firstname, if firstname is available
			if (!empty($data['firstname'])) {
				# Check TS setting for lastname, firstname
				if (((isset($this->conf['lastnameFirst']))) && (1 == $this->conf['lastnameFirst'])) {
					$speakername =  $data['name'] . ', ' . $data['firstname'];
				} else {
					$speakername =  $data['firstname'] . ' ' . $data['name'];
				}
			} else {
				$speakername =  $data['name'];
			}
			if (isset($speaker_content)) {
				# Second and furter name(s)
				$speaker_content .= $this->internal['speakerdelimiter'] . $speakername;
			} else {
				# First name
				$speaker_content = $speakername;
			}
		}
		# Check if any speaker was in the list
		if (empty($speaker_content)) {
			$speaker_content = $this->pi_getLL('tx_wseevents_sessions.nospeakers', '[no speaker assigned]');
		}
		return $speaker_content;
	}

	/**
	 * Set the pi_USER_INT_obj variable depending on cache use
	 *
	 * @return	void
	 */
	function setCache() {
		if (1 == $this->use_cache) {
			$this->pi_USER_INT_obj = 0;
		} else {
			$this->pi_USER_INT_obj = 1;
		}
	}

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wse_events/pi1/class.tx_wseevents_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wse_events/pi1/class.tx_wseevents_pi1.php']);
}

?>
