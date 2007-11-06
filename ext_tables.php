<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

//include_once(t3lib_extMgm::extPath($_EXTKEY).'class.tx_wseevents_addFieldsToFlexForm.php');

t3lib_extMgm::allowTableOnStandardPages('tx_wseevents_events');

t3lib_extMgm::addToInsertRecords('tx_wseevents_events');

$TCA['tx_wseevents_events'] = Array (
	'ctrl' => Array (
		'title' => 'LLL:EXT:wse_events/locallang_db.php:tx_wseevents_events',		
		'label' => 'name',	
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'languageField' => 'sys_language_uid',	
		'transOrigPointerField' => 'l18n_parent',	
		'transOrigDiffSourceField' => 'l18n_diffsource',	
		'default_sortby' => 'ORDER BY crdate DESC',	
		'delete' => 'deleted',	
		'enablecolumns' => Array (		
			'disabled' => 'hidden',
		),
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY)."tca.php",
		'iconfile' => t3lib_extMgm::extRelPath($_EXTKEY)."icon_tx_wseevents_events.gif",
	),
	'feInterface' => Array (
		'fe_admin_fieldList' => 'sys_language_uid, l18n_parent, l18n_diffsource, hidden, name, comment, location, begin, length',
	)
);


t3lib_extMgm::allowTableOnStandardPages('tx_wseevents_locations');

t3lib_extMgm::addToInsertRecords('tx_wseevents_locations');

$TCA['tx_wseevents_locations'] = Array (
	'ctrl' => Array (
		'title' => 'LLL:EXT:wse_events/locallang_db.php:tx_wseevents_locations',		
		'label' => 'name',	
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'languageField' => 'sys_language_uid',	
		'transOrigPointerField' => 'l18n_parent',	
		'transOrigDiffSourceField' => 'l18n_diffsource',	
		'default_sortby' => 'ORDER BY crdate',	
		'delete' => 'deleted',	
		'enablecolumns' => Array (		
			'disabled' => 'hidden',
		),
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY)."tca.php",
		'iconfile' => t3lib_extMgm::extRelPath($_EXTKEY)."icon_tx_wseevents_locations.gif",
	),
	'feInterface' => Array (
		'fe_admin_fieldList' => 'sys_language_uid, l18n_parent, l18n_diffsource, hidden, name, comment',
	)
);


t3lib_extMgm::allowTableOnStandardPages('tx_wseevents_rooms');

t3lib_extMgm::addToInsertRecords('tx_wseevents_rooms');

$TCA['tx_wseevents_rooms'] = Array (
	'ctrl' => Array (
		'title' => 'LLL:EXT:wse_events/locallang_db.php:tx_wseevents_rooms',		
		'label' => 'name',	
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'languageField' => 'sys_language_uid',	
		'transOrigPointerField' => 'l18n_parent',	
		'transOrigDiffSourceField' => 'l18n_diffsource',	
		'default_sortby' => 'ORDER BY crdate',	
		'delete' => 'deleted',	
		'type' => 'sys_language_uid',
		'enablecolumns' => Array (		
			'disabled' => 'hidden',
		),
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY)."tca.php",
		'iconfile' => t3lib_extMgm::extRelPath($_EXTKEY)."icon_tx_wseevents_rooms.gif",
	),
	'feInterface' => Array (
		'fe_admin_fieldList' => 'sys_language_uid, l18n_parent, l18n_diffsource, hidden, name, comment, seats, location',
	)
);


t3lib_extMgm::allowTableOnStandardPages('tx_wseevents_timeslots');

t3lib_extMgm::addToInsertRecords('tx_wseevents_timeslots');

$TCA['tx_wseevents_timeslots'] = Array (
	'ctrl' => Array (
		'requestUpdate' => 'event',
		'title' => 'LLL:EXT:wse_events/locallang_db.php:tx_wseevents_timeslots',		
		'label' => 'name',	
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => 'ORDER BY crdate',	
		'delete' => 'deleted',	
		'enablecolumns' => Array (		
			'disabled' => 'hidden',
		),
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY)."tca.php",
		'iconfile' => t3lib_extMgm::extRelPath($_EXTKEY)."icon_tx_wseevents_timeslots.gif",
	),
	'feInterface' => Array (
		'fe_admin_fieldList' => 'sys_language_uid, l18n_parent, l18n_diffsource, hidden, name, comment, event, eventday, room, begin, end',
	),
);


t3lib_extMgm::allowTableOnStandardPages('tx_wseevents_sessions');

t3lib_extMgm::addToInsertRecords('tx_wseevents_sessions');

$TCA['tx_wseevents_sessions'] = Array (
	'ctrl' => Array (
		'title' => 'LLL:EXT:wse_events/locallang_db.php:tx_wseevents_sessions',		
		'label' => 'name',	
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'languageField' => 'sys_language_uid',	
		'transOrigPointerField' => 'l18n_parent',	
		'transOrigDiffSourceField' => 'l18n_diffsource',

		'versioning_followPages' => TRUE,
		'origUid' => 't3_origuid',
		'shadowColumnsForNewPlaceholders' => 'sys_language_uid,l18n_parent',

		'default_sortby' => 'ORDER BY crdate',	
		'delete' => 'deleted',	
		'type' => 'sys_language_uid',
		'enablecolumns' => Array (		
			'disabled' => 'hidden',
		),
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY)."tca.php",
		'iconfile' => t3lib_extMgm::extRelPath($_EXTKEY)."icon_tx_wseevents_sessions.gif",
	),
	'feInterface' => Array (
		'fe_admin_fieldList' => 'sys_language_uid, l18n_parent, l18n_diffsource, hidden, event, name, comment, speaker, timeslots, category, number, teaser, description',
	)
);


t3lib_extMgm::allowTableOnStandardPages('tx_wseevents_speakers');

t3lib_extMgm::addToInsertRecords('tx_wseevents_speakers');

$TCA['tx_wseevents_speakers'] = Array (
	'ctrl' => Array (
		'title' => 'LLL:EXT:wse_events/locallang_db.php:tx_wseevents_speakers',		
		'label' => 'fullname',	
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'languageField' => 'sys_language_uid',	
		'transOrigPointerField' => 'l18n_parent',	
		'transOrigDiffSourceField' => 'l18n_diffsource',	

		'versioning_followPages' => TRUE,
		'origUid' => 't3_origuid',
		'shadowColumnsForNewPlaceholders' => 'sys_language_uid,l18n_parent',

		'default_sortby' => 'ORDER BY name,firstname',	
		'delete' => 'deleted',	
		'type' => 'sys_language_uid',
		'enablecolumns' => Array (		
			'disabled' => 'hidden',
		),
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY)."tca.php",
		'iconfile' => t3lib_extMgm::extRelPath($_EXTKEY)."icon_tx_wseevents_speakers.gif",
	),
	'feInterface' => Array (
		'fe_admin_fieldList' => 'sys_language_uid, l18n_parent, l18n_diffsource, hidden, name, firstname, comment, email, info',
	)
);


t3lib_extMgm::allowTableOnStandardPages('tx_wseevents_speakerrestrictions');

t3lib_extMgm::addToInsertRecords('tx_wseevents_speakerrestrictions');

$TCA['tx_wseevents_speakerrestrictions'] = Array (
	'ctrl' => Array (
		'requestUpdate' => 'event',
		'title' => 'LLL:EXT:wse_events/locallang_db.php:tx_wseevents_speakerrestrictions',		
		'label' => 'speaker',	
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => 'ORDER BY event,speaker',	
		'delete' => 'deleted',	
		'enablecolumns' => Array (		
			'disabled' => 'hidden',
		),
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY)."tca.php",
		'iconfile' => t3lib_extMgm::extRelPath($_EXTKEY)."tx_wseevents_speakerrestrictions.gif",
	),
	'feInterface' => Array (
		'fe_admin_fieldList' => 'sys_language_uid, l18n_parent, l18n_diffsource, hidden, speaker, comment, event, eventday, begin, end',
	),
);


t3lib_extMgm::allowTableOnStandardPages('tx_wseevents_categories');

t3lib_extMgm::addToInsertRecords('tx_wseevents_categories');

$TCA['tx_wseevents_categories'] = Array (
	'ctrl' => Array (
		'title' => 'LLL:EXT:wse_events/locallang_db.php:tx_wseevents_categories',		
		'label' => 'name',	
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'languageField' => 'sys_language_uid',	
		'transOrigPointerField' => 'l18n_parent',	
		'transOrigDiffSourceField' => 'l18n_diffsource',	
		'default_sortby' => 'ORDER BY crdate',	
		'delete' => 'deleted',	
		'type' => 'sys_language_uid',
		'enablecolumns' => Array (		
			'disabled' => 'hidden',
		),
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY)."tca.php",
		'iconfile' => t3lib_extMgm::extRelPath($_EXTKEY)."icon_tx_wseevents_categories.gif",
	),
	'feInterface' => Array (
		'fe_admin_fieldList' => 'sys_language_uid, l18n_parent, l18n_diffsource, hidden, name, comment, shortkey',
	)
);


// enable Workspace versioning only for TYPO3 v 4.0 and higher
if (t3lib_div::int_from_ver(TYPO3_version) >= 4000000) {
	$TCA['tx_wseevents_sessions']['ctrl']['versioningWS'] = TRUE;
	$TCA['tx_wseevents_speakers']['ctrl']['versioningWS'] = TRUE;
} else {
	$TCA['tx_wseevents_sessions']['ctrl']['versioning'] = TRUE;
	$TCA['tx_wseevents_speakers']['ctrl']['versioning'] = TRUE;
}

t3lib_div::loadTCA('tt_content');
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi1']='layout,select_key,pages';

// add pi_flexforms to be rendered when the plugin is shown
$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_pi1']='pi_flexform';

t3lib_extMgm::addPlugin(Array('LLL:EXT:wse_events/locallang_db.php:tt_content.list_type_pi1', $_EXTKEY.'_pi1'),'list_type');


t3lib_extMgm::addStaticFile($_EXTKEY,'pi1/static/','WSE Events');

// now we add the flexform xml-file
t3lib_extMgm::addPiFlexFormValue($_EXTKEY.'_pi1', 'FILE:EXT:wse_events/flexform_ds_pi1.xml');

if (TYPO3_MODE == 'BE')	{
	t3lib_extMgm::addModule('web','txwseeventsM1','',t3lib_extMgm::extPath($_EXTKEY).'mod1/');
}

?>