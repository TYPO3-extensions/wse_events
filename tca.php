<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

require_once(t3lib_extMgm::extPath('wse_events').'class.tx_wseevents_events.php');
require_once(t3lib_extMgm::extPath('wse_events').'class.tx_wseevents_locations.php');
require_once(t3lib_extMgm::extPath('wse_events').'class.tx_wseevents_rooms.php');
require_once(t3lib_extMgm::extPath('wse_events').'class.tx_wseevents_speakers.php');
require_once(t3lib_extMgm::extPath('wse_events').'class.tx_wseevents_categories.php');
require_once(t3lib_extMgm::extPath('wse_events').'class.tx_wseevents_timeslots.php');

$TCA['tx_wseevents_events'] = Array (
	'ctrl' => $TCA['tx_wseevents_events']['ctrl'],
	'interface' => Array (
		'showRecordFieldList' => 'sys_language_uid,l18n_parent,l18n_diffsource,hidden,name,location'
	),
	'feInterface' => $TCA['tx_wseevents_events']['feInterface'],
	'columns' => Array (
		'sys_language_uid' => Array (		
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.language',
			'config' => Array (
				'type' => 'select',
				'foreign_table' => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => Array(
					Array('LLL:EXT:lang/locallang_general.php:LGL.allLanguages',-1),
					Array('LLL:EXT:lang/locallang_general.php:LGL.default_value',0)
				)
			)
		),
		'l18n_parent' => Array (		
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.l18n_parent',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('', 0),
				),
				'foreign_table' => 'tx_wseevents_events',
				'foreign_table_where' => 'AND tx_wseevents_events.pid=###CURRENT_PID### AND tx_wseevents_events.sys_language_uid IN (-1,0)',
			)
		),
		'l18n_diffsource' => Array (		
			'config' => Array (
				'type' => 'passthrough'
			)
		),
		'hidden' => Array (		
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.hidden',
			'config' => Array (
				'type' => 'check',
				'default' => '0'
			)
		),
		'name' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:wse_events/locallang_db.php:tx_wseevents_events.name',		
			'config' => Array (
				'type' => 'input',	
				'size' => '30',	
				'eval' => 'required',
			)
		),
		'comment' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:wse_events/locallang_db.php:tx_wseevents_events.comment',		
			'config' => Array (
				'type' => 'text',
				'cols' => '30',	
				'rows' => '3',
			)
		),
		'location' => Array (		
			'exclude' => 1,		
			'l10n_mode' => 'exclude',
			'label' => 'LLL:EXT:wse_events/locallang_db.php:tx_wseevents_events.location',		
			'config' => Array (
				'type' => 'select',	
				'itemsProcFunc' => 'tx_wseevents_locations->getTCAlocationlist',
				'foreign_table' => 'tx_wseevents_locations',	
				'foreign_table_where' => 'ORDER BY tx_wseevents_locations.name',	
				'size' => 1,	
				'minitems' => 0,
				'maxitems' => 1,	
			)
		),
		'begin' => Array (		
			'exclude' => 1,		
			'l10n_mode' => 'exclude',
			'label' => 'LLL:EXT:wse_events/locallang_db.php:tx_wseevents_events.begin',
			'config' => Array (
				'type' => 'input',
				'size' => '12',
				'max' => '20',
				'eval' => 'date',
				'checkbox' => '0',
				'default' => '0'
			)
		),
		'length' => Array (		
			'exclude' => 1,		
			'l10n_mode' => 'exclude',
			'label' => 'LLL:EXT:wse_events/locallang_db.php:tx_wseevents_events.length',
			'config' => Array (
				'type' => 'input',
				'size' => '2',
				'max' => '2',
				'eval' => 'int',
				'range' => Array (
					'upper' => '99',
					'lower' => '1'
				),
				'default' => 1
			)
		),
		'timebegin' => Array (
			'exclude' => 1,		
			'l10n_mode' => 'exclude',
			'label' => 'LLL:EXT:wse_events/locallang_db.php:tx_wseevents_events.timebegin',
			'config' => Array (
				'type' => 'input',	
				'size' => '5',	
				'max' => '5',
			)
		),
		'timeend' => Array (
			'exclude' => 1,		
			'l10n_mode' => 'exclude',
			'label' => 'LLL:EXT:wse_events/locallang_db.php:tx_wseevents_events.timeend',
			'config' => Array (
				'type' => 'input',	
				'size' => '5',	
				'max' => '5',
			)
		),
		'slotsize' => Array (		
			'exclude' => 1,		
			'l10n_mode' => 'exclude',
			'label' => 'LLL:EXT:wse_events/locallang_db.php:tx_wseevents_events.slotsize',
			'config' => Array (
				'type' => 'input',
				'size' => '2',
				'max' => '2',
				'eval' => 'int',
				'range' => Array (
					'upper' => '99',
					'lower' => '1'
				),
				'default' => 1
			)
		),
		'maxslot' => Array (		
			'exclude' => 1,		
			'l10n_mode' => 'exclude',
			'label' => 'LLL:EXT:wse_events/locallang_db.php:tx_wseevents_events.maxslot',
			'config' => Array (
				'type' => 'input',
				'size' => '1',
				'max' => '1',
				'eval' => 'int',
				'range' => Array (
					'upper' => '99',
					'lower' => '1'
				),
				'default' => 1
			)
		),
		'defslotcount' => Array (		
			'exclude' => 1,		
			'l10n_mode' => 'exclude',
			'label' => 'LLL:EXT:wse_events/locallang_db.php:tx_wseevents_events.defslotcount',
			'config' => Array (
				'type' => 'input',
				'size' => '1',
				'max' => '1',
				'eval' => 'int',
				'range' => Array (
					'upper' => '99',
					'lower' => '1'
				),
				'default' => 1
			)
		),
	),
	'types' => Array (
		'0' => Array('showitem' => 'sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, hidden;;1, name, comment, location, begin, length, timebegin, timeend, slotsize, maxslot, defslotcount')
	),
	'palettes' => Array (
		'1' => Array('showitem' => '')
	)
);



$TCA['tx_wseevents_locations'] = Array (
	'ctrl' => $TCA['tx_wseevents_locations']['ctrl'],
	'interface' => Array (
		'showRecordFieldList' => 'sys_language_uid,l18n_parent,l18n_diffsource,hidden,name'
	),
	'feInterface' => $TCA['tx_wseevents_locations']['feInterface'],
	'columns' => Array (
		'sys_language_uid' => Array (		
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.language',
			'config' => Array (
				'type' => 'select',
				'foreign_table' => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => Array(
					Array('LLL:EXT:lang/locallang_general.php:LGL.allLanguages',-1),
					Array('LLL:EXT:lang/locallang_general.php:LGL.default_value',0)
				)
			)
		),
		'l18n_parent' => Array (		
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.l18n_parent',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('', 0),
				),
				'foreign_table' => 'tx_wseevents_locations',
				'foreign_table_where' => 'AND tx_wseevents_locations.pid=###CURRENT_PID### AND tx_wseevents_locations.sys_language_uid IN (-1,0)',
			)
		),
		'l18n_diffsource' => Array (		
			'config' => Array (
				'type' => 'passthrough'
			)
		),
		'hidden' => Array (		
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.hidden',
			'config' => Array (
				'type' => 'check',
				'default' => '0'
			)
		),
		'name' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:wse_events/locallang_db.php:tx_wseevents_locations.name',		
			'config' => Array (
				'type' => 'input',	
				'size' => '30',	
				'eval' => 'required',
			)
		),
		'website' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:wse_events/locallang_db.php:tx_wseevents_locations.website',		
			'config' => Array (
				'type' => 'input',	
				'size' => '30',
				'wizards' => Array(
					'link' => Array(
					    'type' => 'popup',
					    'title' => 'Link',
					    'icon' => 'link_popup.gif',
					    'script' => 'browse_links.php?mode=wizard',
					    'JSopenParams' => 'height=300,width=500,status=0,menubar=0,scrollbars=1'
					),
				),
			)
		),
		'comment' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:wse_events/locallang_db.php:tx_wseevents_events.comment',		
			'config' => Array (
				'type' => 'text',
				'cols' => '30',	
				'rows' => '3',
			)
		),
	),
	'types' => Array (
		'0' => Array('showitem' => 'sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, hidden;;1, name, website, comment')
	),
	'palettes' => Array (
		'1' => Array('showitem' => '')
	)
);



$TCA['tx_wseevents_rooms'] = Array (
	'ctrl' => $TCA['tx_wseevents_rooms']['ctrl'],
	'interface' => Array (
		'showRecordFieldList' => 'sys_language_uid,l18n_parent,l18n_diffsource,hidden,name,number,seats,location'
	),
	'feInterface' => $TCA['tx_wseevents_rooms']['feInterface'],
	'columns' => Array (
		'sys_language_uid' => Array (		
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.language',
			'config' => Array (
				'type' => 'select',
				'foreign_table' => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => Array(
					Array('LLL:EXT:lang/locallang_general.php:LGL.allLanguages',-1),
					Array('LLL:EXT:lang/locallang_general.php:LGL.default_value',0)
				)
			)
		),
		'l18n_parent' => Array (		
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.l18n_parent',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('', 0),
				),
				'foreign_table' => 'tx_wseevents_rooms',
				'foreign_table_where' => 'AND tx_wseevents_rooms.pid=###CURRENT_PID### AND tx_wseevents_rooms.sys_language_uid IN (-1,0)',
			)
		),
		'l18n_diffsource' => Array (		
			'config' => Array (
				'type' => 'passthrough'
			)
		),
		'hidden' => Array (		
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.hidden',
			'config' => Array (
				'type' => 'check',
				'default' => '0'
			)
		),
		'name' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:wse_events/locallang_db.php:tx_wseevents_rooms.name',		
			'config' => Array (
				'type' => 'input',	
				'size' => '30',	
				'eval' => 'required',
			)
		),
		'comment' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:wse_events/locallang_db.php:tx_wseevents_events.comment',		
			'config' => Array (
				'type' => 'text',
				'cols' => '30',	
				'rows' => '3',
			)
		),
		'number' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:wse_events/locallang_db.php:tx_wseevents_rooms.number',		
			'config' => Array (
				'type' => 'input',
				'size' => '4',
				'max' => '4',
				'eval' => 'int',
				'range' => Array (
					'upper' => '100',
					'lower' => '1'
				),
				'default' => 0
			)
		),
		'seats' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:wse_events/locallang_db.php:tx_wseevents_rooms.seats',		
			'config' => Array (
				'type' => 'input',
				'size' => '4',
				'max' => '4',
				'eval' => 'int',
				'checkbox' => '0',
				'range' => Array (
					'upper' => '1000',
					'lower' => '10'
				),
				'default' => 0
			)
		),
		'location' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:wse_events/locallang_db.php:tx_wseevents_rooms.location',		
			'config' => Array (
				'type' => 'select',	
				'foreign_table' => 'tx_wseevents_locations',	
				'foreign_table_where' => 'AND tx_wseevents_locations.pid=###CURRENT_PID### ORDER BY tx_wseevents_locations.name',	
				'size' => 1,	
				'minitems' => 0,
				'maxitems' => 1,	
			)
		),
	),
	'types' => Array (
		'0' => Array('showitem' => 'sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, hidden;;1, name, comment, number, seats, location'),
		'1' => Array('showitem' => 'sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, hidden;;1, name, comment'),
	),
	'palettes' => Array (
		'1' => Array('showitem' => '')
	)
);



$TCA['tx_wseevents_timeslots'] = Array (
	'ctrl' => $TCA['tx_wseevents_timeslots']['ctrl'],
	'interface' => Array (
		'showRecordFieldList' => 'hidden,event,begin,end,sessions'
	),
	'feInterface' => $TCA['tx_wseevents_timeslots']['feInterface'],
	'columns' => Array (
		'hidden' => Array (		
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.hidden',
			'config' => Array (
				'type' => 'check',
				'default' => '0'
			)
		),
		'name' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:wse_events/locallang_db.php:tx_wseevents_timeslots.name',		
			'config' => Array (
				'type' => 'input',	
				'size' => '30',	
			)
		),
		'comment' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:wse_events/locallang_db.php:tx_wseevents_events.comment',		
			'config' => Array (
				'type' => 'text',
				'cols' => '30',	
				'rows' => '3',
			)
		),
		'event' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:wse_events/locallang_db.php:tx_wseevents_timeslots.event',		
			'config' => Array (
				'type' => 'select',	
				'foreign_table' => 'tx_wseevents_events',	
				'foreign_table_where' => 'AND tx_wseevents_events.pid=###CURRENT_PID### AND tx_wseevents_events.sys_language_uid=0 ORDER BY tx_wseevents_events.name',	
				'size' => 1,	
				'minitems' => 0,
				'maxitems' => 1,
			)
		),
		'eventday' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:wse_events/locallang_db.php:tx_wseevents_timeslots.eventday',		
			'config' => Array (
				'type' => 'select',
				'itemsProcFunc' => 'tx_wseevents_events->getTCAeventDays',
				'size' => '1',
				'max' => '2',
				'eval' => 'int',
				'range' => Array (
					'upper' => '99',
					'lower' => '1',
				),
				'minitems' => 0,
				'maxitems' => 1,
				'default' => 1
			)
		),
		'room' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:wse_events/locallang_db.php:tx_wseevents_sessions.room',		
			'config' => Array (
				'type' => 'select',
				'itemsProcFunc' => 'tx_wseevents_rooms->getTCAroomlist',
				'foreign_table' => 'tx_wseevents_rooms',
				'foreign_table_where' => 'ORDER BY tx_wseevents_rooms.name', //AND tx_wseevents_events.uid=###REC_FIELDS_event AND tx_wseevents_events.location=tx_wseevents_rooms.location 
//				'additional_foreign_table' => 'tx_wseevents_events',
				'size' => 1,	
				'minitems' => 0,
				'maxitems' => 1,
			)
		),
		'begin' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:wse_events/locallang_db.php:tx_wseevents_timeslots.begin',		
			'config' => Array (
				'type' => 'select',
				'itemsProcFunc' => 'tx_wseevents_events->getTCAslotList',
				'size' => '1',
				'max' => '2',
				'eval' => 'int',
				'range' => Array (
					'upper' => '99',
					'lower' => '1',
				),
				'default' => 1
			)
		),
		'length' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:wse_events/locallang_db.php:tx_wseevents_timeslots.length',		
			'config' => Array (
				'type' => 'select',
				'itemsProcFunc' => 'tx_wseevents_events->getTCAsessionLength',
				'size' => '1',
				'max' => '2',
				'eval' => 'int',
				'range' => Array (
					'upper' => '99',
					'lower' => '1',
				),
			)
		),
	),
	'types' => Array (
		'0' => Array('showitem' => 'comment, event, eventday, room, begin, length')
	),
	'palettes' => Array (
		'1' => Array('showitem' => '')
	)
);



$TCA['tx_wseevents_sessions'] = Array (
	'ctrl' => $TCA['tx_wseevents_sessions']['ctrl'],
	'interface' => Array (
		'showRecordFieldList' => 'sys_language_uid,l18n_parent,l18n_diffsource,hidden,event,name,speaker,room,timeslots,category,number,teaser,description'
	),
	'feInterface' => $TCA['tx_wseevents_sessions']['feInterface'],
	'columns' => Array (
		'sys_language_uid' => Array (		
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.language',
			'config' => Array (
				'type' => 'select',
				'foreign_table' => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => Array(
					Array('LLL:EXT:lang/locallang_general.php:LGL.allLanguages',-1),
					Array('LLL:EXT:lang/locallang_general.php:LGL.default_value',0)
				)
			)
		),
		'l18n_parent' => Array (		
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.l18n_parent',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('', 0),
				),
				'foreign_table' => 'tx_wseevents_sessions',
				'foreign_table_where' => 'AND tx_wseevents_sessions.pid=###CURRENT_PID### AND tx_wseevents_sessions.sys_language_uid IN (-1,0)',
			)
		),
		'l18n_diffsource' => Array (		
			'config' => Array (
				'type' => 'passthrough'
			)
		),
		'hidden' => Array (		
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.hidden',
			'config' => Array (
				'type' => 'check',
				'default' => '0'
			)
		),
		'event' => Array (		
			'exclude' => 1,
			'l10n_mode' => 'exclude',
			'label' => 'LLL:EXT:wse_events/locallang_db.php:tx_wseevents_sessions.event',		
			'config' => Array (
				'type' => 'select',	
				'foreign_table' => 'tx_wseevents_events',	
				'foreign_table_where' => 'AND tx_wseevents_events.pid=###CURRENT_PID### AND tx_wseevents_events.sys_language_uid=0 ORDER BY tx_wseevents_events.name',	
				'size' => 1,	
				'minitems' => 0,
				'maxitems' => 1,
			)
		),
		'name' => Array (		
			'exclude' => 1,		
			'l10n_mode' => 'prefixLangTitle',
			'label' => 'LLL:EXT:wse_events/locallang_db.php:tx_wseevents_sessions.name',		
			'config' => Array (
				'type' => 'input',	
				'size' => '48',	
				'eval' => 'required',
			)
		),
		'comment' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:wse_events/locallang_db.php:tx_wseevents_events.comment',		
			'config' => Array (
				'type' => 'text',
				'cols' => '30',	
				'rows' => '3',
			)
		),
		'speaker' => Array (		
			'exclude' => 1,		
			'l10n_mode' => 'exclude',
			'label' => 'LLL:EXT:wse_events/locallang_db.php:tx_wseevents_sessions.speaker',		
			'config' => Array (
				'type' => 'select',	
				'itemsProcFunc' => 'tx_wseevents_speakers->getTCAspeakerlist',
				'foreign_table' => 'tx_wseevents_speakers',	
#				'foreign_table_where' => 'ORDER BY tx_wseevents_speakers.uid',	
				'size' => 6,	
				'minitems' => 0,
				'maxitems' => 4,
			)
		),
		'timeslots' => Array (		
			'exclude' => 1,		
			'l10n_mode' => 'exclude',
			'label' => 'LLL:EXT:wse_events/locallang_db.php:tx_wseevents_sessions.timeslots',
			'config' => Array (
				'type' => 'select',	
				'itemsProcFunc' => 'tx_wseevents_timeslots->getTCAavailableSlots',
				'foreign_table' => 'tx_wseevents_timeslots',	
				'foreign_table_where' => 'ORDER BY tx_wseevents_timeslots.name',	
				'size' => 6,	
				'minitems' => 0,
				'maxitems' => 3,
			)
		),
		'category' => Array (		
			'exclude' => 1,		
			'l10n_mode' => 'exclude',
			'label' => 'LLL:EXT:wse_events/locallang_db.php:tx_wseevents_sessions.category',		
			'config' => Array (
				'type' => 'select',	
				'itemsProcFunc' => 'tx_wseevents_categories->getTCAcategorylist',
				'foreign_table' => 'tx_wseevents_categories',	
#				'foreign_table_where' => 'ORDER BY tx_wseevents_categories.name',
				'size' => 1,	
				'minitems' => 0,
				'maxitems' => 1,
			)
		),
		'number' => Array (		
			'exclude' => 1,		
			'l10n_mode' => 'exclude',
			'label' => 'LLL:EXT:wse_events/locallang_db.php:tx_wseevents_sessions.number',		
			'config' => Array (
				'type' => 'input',
				'size' => '4',
				'max' => '4',
				'eval' => 'int',
				'checkbox' => '0',
				'range' => Array (
					'upper' => '100',
					'lower' => '1'
				),
				'default' => 0
			)
		),
		'teaser' => Array (		
			'exclude' => 1,		
			'l10n_mode' => 'prefixLangTitle',
			'label' => 'LLL:EXT:wse_events/locallang_db.php:tx_wseevents_sessions.teaser',		
			'config' => Array (
				'type' => 'text',
				'cols' => '40',	
				'rows' => '4s',
			)
		),
		'description' => Array (		
			'exclude' => 1,		
			'l10n_mode' => 'prefixLangTitle',
			'label' => 'LLL:EXT:wse_events/locallang_db.php:tx_wseevents_sessions.description',		
			'config' => Array (
				'type' => 'text',
				'cols' => '30',
				'rows' => '5',
				'wizards' => Array(
					'_PADDING' => 2,
					'RTE' => Array(
						'notNewRecords' => 1,
						'RTEonly' => 1,
						'type' => 'script',
						'title' => 'Full screen Rich Text Editing|Formatteret redigering i hele vinduet',
						'icon' => 'wizard_rte2.gif',
						'script' => 'wizard_rte.php',
					),
				),
			)
		),
	),
	'types' => Array (
		'0' => Array('showitem' => 'sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, hidden;;1, event, name, category, number, comment, speaker, room, timeslots, teaser, description;;;richtext[paste|bold|italic|underline|formatblock|class|left|center|right|orderedlist|unorderedlist|outdent|indent|link|image]:rte_transform[mode=ts]'),
		'1' => Array('showitem' => 'sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, hidden;;1, name, comment, teaser, description;;;richtext[paste|bold|italic|underline|formatblock|class|left|center|right|orderedlist|unorderedlist|outdent|indent|link|image]:rte_transform[mode=ts]')
	),
	'palettes' => Array (
		'1' => Array('showitem' => '')
	)
);



$TCA['tx_wseevents_speakers'] = Array (
	'ctrl' => $TCA['tx_wseevents_speakers']['ctrl'],
	'interface' => Array (
		'showRecordFieldList' => 'sys_language_uid,l18n_parent,l18n_diffsource,hidden,name,firstname,comment,country,email,company,companylink,info,image'
	),
	'feInterface' => $TCA['tx_wseevents_speakers']['feInterface'],
	'columns' => Array (
		'sys_language_uid' => Array (		
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.language',
			'config' => Array (
				'type' => 'select',
				'foreign_table' => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => Array(
					Array('LLL:EXT:lang/locallang_general.php:LGL.allLanguages',-1),
					Array('LLL:EXT:lang/locallang_general.php:LGL.default_value',0)
				)
			)
		),
		'l18n_parent' => Array (		
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.l18n_parent',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('', 0),
				),
				'foreign_table' => 'tx_wseevents_speakers',
				'foreign_table_where' => 'AND tx_wseevents_speakers.pid=###CURRENT_PID### AND tx_wseevents_speakers.sys_language_uid IN (-1,0)',
			)
		),
		'l18n_diffsource' => Array (		
			'config' => Array (
				'type' => 'passthrough'
			)
		),
		'hidden' => Array (		
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.hidden',
			'config' => Array (
				'type' => 'check',
				'default' => '0'
			)
		),
		'fullname' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:wse_events/locallang_db.php:tx_wseevents_speakers.fullname',
			'config' => Array (
				'type' => 'input',	
				'size' => '30',	
				'eval' => 'required',
			)
		),
		'name' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:wse_events/locallang_db.php:tx_wseevents_speakers.name',		
			'config' => Array (
				'type' => 'input',	
				'size' => '30',	
				'eval' => 'required',
			)
		),
		'firstname' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:wse_events/locallang_db.php:tx_wseevents_speakers.firstname',		
			'config' => Array (
				'type' => 'input',	
				'size' => '30',	
			)
		),
		'comment' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:wse_events/locallang_db.php:tx_wseevents_events.comment',		
			'config' => Array (
				'type' => 'text',
				'cols' => '30',	
				'rows' => '3',
			)
		),
		'country' => Array (
			'exclude' => 1,		
			'label' => 'LLL:EXT:wse_events/locallang_db.php:tx_wseevents_speakers.country',		
			'config' => Array (
				'type' => 'select',	
				'foreign_table' => 'static_countries',
				'foreign_table_where' => 'ORDER BY cn_short_en',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
			)
		),
		'email' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:wse_events/locallang_db.php:tx_wseevents_speakers.email',		
			'config' => Array (
				'type' => 'input',	
				'size' => '30',
				'eval' => 'trim',
				'wizards' => Array(
					'link' => Array(
					    'type' => 'popup',
					    'title' => 'LLL:EXT:wse_events/locallang_db.php:tx_wseevents_speakers.emailwizard',
					    'icon' => 'link_popup.gif',
					    'script' => 'browse_links.php?mode=wizard',
					    'JSopenParams' => 'height=300,width=500,status=0,menubar=0,scrollbars=1'
					),
				),
			)
		),
		'company' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:wse_events/locallang_db.php:tx_wseevents_speakers.company',		
			'config' => Array (
				'type' => 'input',	
				'size' => '30',
			)
		),
		'companylink' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:wse_events/locallang_db.php:tx_wseevents_speakers.companylink',		
			'config' => Array (
				'type' => 'input',	
				'size' => '30',
				'eval' => 'trim',
				'wizards' => Array(
					'link' => Array(
					    'type' => 'popup',
					    'title' => 'LLL:EXT:wse_events/locallang_db.php:tx_wseevents_speakers.companylinkwizard',
					    'icon' => 'link_popup.gif',
					    'script' => 'browse_links.php?mode=wizard',
					    'JSopenParams' => 'height=300,width=500,status=0,menubar=0,scrollbars=1'
					),
				),
			)
		),
		'info' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:wse_events/locallang_db.php:tx_wseevents_speakers.info',		
			'config' => Array (
				'type' => 'text',
				'cols' => '30',
				'rows' => '5',
				'wizards' => Array(
					'_PADDING' => 2,
					'RTE' => Array(
						'notNewRecords' => 1,
						'RTEonly' => 1,
						'type' => 'script',
						'title' => 'LLL:EXT:wse_events/locallang_db.php:tx_wseevents_speakers.info_rte',
						'icon' => 'wizard_rte2.gif',
						'script' => 'wizard_rte.php',
					),
				),
			)
		),
		'image' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:wse_events/locallang_db.php:tx_wseevents_speakers.image',
			'config' => Array (
				'type' => 'group',
				'internal_type' => 'file',
				'allowed' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],
				'max_size' => 500,
				'uploadfolder' => 'uploads/tx_wseevents',
				'show_thumbs' => 1,
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
			)
		),
	),
	'types' => Array (
		'0' => Array('showitem' => 'sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, hidden;;1, name, firstname, comment, company, companylink, country, email, image, info;;;richtext[paste|bold|italic|underline|formatblock|class|left|center|right|orderedlist|unorderedlist|outdent|indent|link|image]:rte_transform[mode=ts]'),
		'1' => Array('showitem' => 'sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, hidden;;1, name, firstname, comment, info;;;richtext[paste|bold|italic|underline|formatblock|class|left|center|right|orderedlist|unorderedlist|outdent|indent|link|image]:rte_transform[mode=ts]'),
	),
	'palettes' => Array (
		'1' => Array('showitem' => '')
	)
);



$TCA['tx_wseevents_speakerrestrictions'] = Array (
	'ctrl' => $TCA['tx_wseevents_speakerrestrictions']['ctrl'],
	'interface' => Array (
		'showRecordFieldList' => 'sys_language_uid,l18n_parent,l18n_diffsource,hidden,speaker,comment,event,eventday,begin,end'
	),
	'feInterface' => $TCA['tx_wseevents_speakerrestrictions']['feInterface'],
	'columns' => Array (
		'hidden' => Array (		
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.hidden',
			'config' => Array (
				'type' => 'check',
				'default' => '0'
			)
		),
		'speaker' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:wse_events/locallang_db.php:tx_wseevents_speakerrestrictions.speaker',		
			'config' => Array (
				'type' => 'select',	
				'itemsProcFunc' => 'tx_wseevents_speakers->getTCAspeakerlist',
				'foreign_table' => 'tx_wseevents_speakers',	
#				'foreign_table_where' => 'ORDER BY tx_wseevents_speakers.uid',	
				'size' => 1,	
				'minitems' => 0,
				'maxitems' => 1,
			)
		),
		'comment' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:wse_events/locallang_db.php:tx_wseevents_speakerrestrictions.comment',		
			'config' => Array (
				'type' => 'text',
				'cols' => '30',	
				'rows' => '3',
			)
		),
		'event' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:wse_events/locallang_db.php:tx_wseevents_speakerrestrictions.event',		
			'config' => Array (
				'type' => 'select',	
				'foreign_table' => 'tx_wseevents_events',	
				'foreign_table_where' => 'AND tx_wseevents_events.pid=###CURRENT_PID### AND tx_wseevents_events.sys_language_uid=0 ORDER BY tx_wseevents_events.name',	
				'size' => 1,	
				'minitems' => 0,
				'maxitems' => 1,
			)
		),
		'eventday' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:wse_events/locallang_db.php:tx_wseevents_speakerrestrictions.eventday',		
			'config' => Array (
				'type' => 'select',
				'itemsProcFunc' => 'tx_wseevents_events->getTCAeventDays',
				'size' => '1',
				'max' => '2',
				'eval' => 'int',
				'range' => Array (
					'upper' => '99',
					'lower' => '1',
				),
				'minitems' => 0,
				'maxitems' => 1,
				'default' => 1
			)
		),
		'begin' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:wse_events/locallang_db.php:tx_wseevents_speakerrestrictions.begin',		
			'config' => Array (
				'type' => 'select',
				'itemsProcFunc' => 'tx_wseevents_events->getTCAslotList',
				'size' => '1',
				'max' => '2',
				'eval' => 'int',
				'range' => Array (
					'upper' => '99',
					'lower' => '1',
				),
				'default' => 1
			)
		),
		'end' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:wse_events/locallang_db.php:tx_wseevents_speakerrestrictions.end',		
			'config' => Array (
				'type' => 'select',
				'itemsProcFunc' => 'tx_wseevents_events->getTCAslotList',
				'size' => '1',
				'max' => '2',
				'eval' => 'int',
				'range' => Array (
					'upper' => '99',
					'lower' => '1',
				),
			)
		),
	),
	'types' => Array (
		'0' => Array('showitem' => 'sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, hidden;;1, speaker,comment, event, eventday, begin, end')
	),
	'palettes' => Array (
		'1' => Array('showitem' => '')
	)
);



$TCA['tx_wseevents_categories'] = Array (
	'ctrl' => $TCA['tx_wseevents_categories']['ctrl'],
	'interface' => Array (
		'showRecordFieldList' => 'sys_language_uid,l18n_parent,l18n_diffsource,hidden,name,shortkey'
	),
	'feInterface' => $TCA['tx_wseevents_categories']['feInterface'],
	'columns' => Array (
		'sys_language_uid' => Array (		
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.language',
			'config' => Array (
				'type' => 'select',
				'foreign_table' => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => Array(
					Array('LLL:EXT:lang/locallang_general.php:LGL.allLanguages',-1),
					Array('LLL:EXT:lang/locallang_general.php:LGL.default_value',0)
				)
			)
		),
		'l18n_parent' => Array (		
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.l18n_parent',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('', 0),
				),
				'foreign_table' => 'tx_wseevents_categories',
				'foreign_table_where' => 'AND tx_wseevents_categories.pid=###CURRENT_PID### AND tx_wseevents_categories.sys_language_uid IN (-1,0)',
			)
		),
		'l18n_diffsource' => Array (		
			'config' => Array (
				'type' => 'passthrough'
			)
		),
		'hidden' => Array (		
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.hidden',
			'config' => Array (
				'type' => 'check',
				'default' => '0'
			)
		),
		'name' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:wse_events/locallang_db.php:tx_wseevents_categories.name',		
			'config' => Array (
				'type' => 'input',	
				'size' => '30',	
				'eval' => 'required',
			)
		),
		'comment' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:wse_events/locallang_db.php:tx_wseevents_events.comment',		
			'config' => Array (
				'type' => 'text',
				'cols' => '30',	
				'rows' => '3',
			)
		),
		'shortkey' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:wse_events/locallang_db.php:tx_wseevents_categories.shortkey',		
			'config' => Array (
				'type' => 'input',	
				'size' => '5',	
				'max' => '3',	
				'eval' => 'required,upper,nospace',
			)
		),
		'color' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:wse_events/locallang_db.php:tx_wseevents_categories.color',		
			'config' => Array (
				'type' => 'input',	
				'size' => '10',	
				'wizards' => array(
					'colorpick' => array(
						'type' => 'colorbox',
						'title' => '',
						'script' => 'wizard_colorpicker.php',
						'dim' => '20x20',
						'tableStyle' => 'border: solid 1px black; margin-left: 20px;',
						'JSopenParams' => 'height=550,width=365,status=0,menubar=0,scrollbars=1',
						'exampleImg' => 'gfx/wizard_colorpickerex.jpg',
					),
				),
			)
		),
	),
	'types' => Array (
		'0' => Array('showitem' => 'sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, hidden;;1, name, comment, shortkey, color'),
		'1' => Array('showitem' => 'sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, hidden;;1, name, comment'),
	),
	'palettes' => Array (
		'1' => Array('showitem' => '')
	)
);
?>