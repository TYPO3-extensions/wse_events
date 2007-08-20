<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

$TCA["tx_wseevents_events"] = Array (
	"ctrl" => $TCA["tx_wseevents_events"]["ctrl"],
	"interface" => Array (
		"showRecordFieldList" => "sys_language_uid,l18n_parent,l18n_diffsource,hidden,name,location"
	),
	"feInterface" => $TCA["tx_wseevents_events"]["feInterface"],
	"columns" => Array (
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
		"hidden" => Array (		
			"exclude" => 1,
			"label" => "LLL:EXT:lang/locallang_general.php:LGL.hidden",
			"config" => Array (
				"type" => "check",
				"default" => "0"
			)
		),
		"name" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:wse_events/locallang_db.php:tx_wseevents_events.name",		
			"config" => Array (
				"type" => "input",	
				"size" => "30",	
				"eval" => "required",
			)
		),
		"location" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:wse_events/locallang_db.php:tx_wseevents_events.location",		
			"config" => Array (
				"type" => "select",	
				"foreign_table" => "tx_wseevents_locations",	
				"foreign_table_where" => "ORDER BY tx_wseevents_locations.uid",	
				"size" => 1,	
				"minitems" => 0,
				"maxitems" => 1,	
				"wizards" => Array(
					"_PADDING" => 2,
					"_VERTICAL" => 1,
					"add" => Array(
						"type" => "script",
						"title" => "Create new record",
						"icon" => "add.gif",
						"params" => Array(
							"table"=>"tx_wseevents_locations",
							"pid" => "###CURRENT_PID###",
							"setValue" => "prepend"
						),
						"script" => "wizard_add.php",
					),
				),
			)
		),
	),
	"types" => Array (
		"0" => Array("showitem" => "sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, hidden;;1, name, location")
	),
	"palettes" => Array (
		"1" => Array("showitem" => "")
	)
);



$TCA["tx_wseevents_locations"] = Array (
	"ctrl" => $TCA["tx_wseevents_locations"]["ctrl"],
	"interface" => Array (
		"showRecordFieldList" => "sys_language_uid,l18n_parent,l18n_diffsource,hidden,name"
	),
	"feInterface" => $TCA["tx_wseevents_locations"]["feInterface"],
	"columns" => Array (
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
		"hidden" => Array (		
			"exclude" => 1,
			"label" => "LLL:EXT:lang/locallang_general.php:LGL.hidden",
			"config" => Array (
				"type" => "check",
				"default" => "0"
			)
		),
		"name" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:wse_events/locallang_db.php:tx_wseevents_locations.name",		
			"config" => Array (
				"type" => "input",	
				"size" => "30",	
				"eval" => "required",
			)
		),
	),
	"types" => Array (
		"0" => Array("showitem" => "sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, hidden;;1, name")
	),
	"palettes" => Array (
		"1" => Array("showitem" => "")
	)
);



$TCA["tx_wseevents_rooms"] = Array (
	"ctrl" => $TCA["tx_wseevents_rooms"]["ctrl"],
	"interface" => Array (
		"showRecordFieldList" => "sys_language_uid,l18n_parent,l18n_diffsource,hidden,name,seats,location"
	),
	"feInterface" => $TCA["tx_wseevents_rooms"]["feInterface"],
	"columns" => Array (
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
		"hidden" => Array (		
			"exclude" => 1,
			"label" => "LLL:EXT:lang/locallang_general.php:LGL.hidden",
			"config" => Array (
				"type" => "check",
				"default" => "0"
			)
		),
		"name" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:wse_events/locallang_db.php:tx_wseevents_rooms.name",		
			"config" => Array (
				"type" => "input",	
				"size" => "30",	
				"eval" => "required",
			)
		),
		"seats" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:wse_events/locallang_db.php:tx_wseevents_rooms.seats",		
			"config" => Array (
				"type" => "input",
				"size" => "4",
				"max" => "4",
				"eval" => "int",
				"checkbox" => "0",
				"range" => Array (
					"upper" => "1000",
					"lower" => "10"
				),
				"default" => 0
			)
		),
		"location" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:wse_events/locallang_db.php:tx_wseevents_rooms.location",		
			"config" => Array (
				"type" => "select",	
				"foreign_table" => "tx_wseevents_locations",	
				"foreign_table_where" => "ORDER BY tx_wseevents_locations.uid",	
				"size" => 1,	
				"minitems" => 0,
				"maxitems" => 1,	
				"wizards" => Array(
					"_PADDING" => 2,
					"_VERTICAL" => 1,
					"add" => Array(
						"type" => "script",
						"title" => "Create new record",
						"icon" => "add.gif",
						"params" => Array(
							"table"=>"tx_wseevents_locations",
							"pid" => "###CURRENT_PID###",
							"setValue" => "prepend"
						),
						"script" => "wizard_add.php",
					),
				),
			)
		),
	),
	"types" => Array (
		"0" => Array("showitem" => "sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, hidden;;1, name, seats, location")
	),
	"palettes" => Array (
		"1" => Array("showitem" => "")
	)
);



$TCA["tx_wseevents_timeslots"] = Array (
	"ctrl" => $TCA["tx_wseevents_timeslots"]["ctrl"],
	"interface" => Array (
		"showRecordFieldList" => "sys_language_uid,l18n_parent,l18n_diffsource,hidden,event,name,begin,end,sessions"
	),
	"feInterface" => $TCA["tx_wseevents_timeslots"]["feInterface"],
	"columns" => Array (
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
				'foreign_table' => 'tx_wseevents_timeslots',
				'foreign_table_where' => 'AND tx_wseevents_timeslots.pid=###CURRENT_PID### AND tx_wseevents_timeslots.sys_language_uid IN (-1,0)',
			)
		),
		'l18n_diffsource' => Array (		
			'config' => Array (
				'type' => 'passthrough'
			)
		),
		"hidden" => Array (		
			"exclude" => 1,
			"label" => "LLL:EXT:lang/locallang_general.php:LGL.hidden",
			"config" => Array (
				"type" => "check",
				"default" => "0"
			)
		),
		"event" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:wse_events/locallang_db.php:tx_wseevents_timeslots.event",		
			"config" => Array (
				"type" => "select",	
				"foreign_table" => "tx_wseevents_events",	
				"foreign_table_where" => "AND tx_wseevents_events.pid=###CURRENT_PID### ORDER BY tx_wseevents_events.uid",	
				"size" => 1,	
				"minitems" => 0,
				"maxitems" => 1,
			)
		),
		"name" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:wse_events/locallang_db.php:tx_wseevents_timeslots.name",		
			"config" => Array (
				"type" => "input",	
				"size" => "30",	
				"eval" => "required",
			)
		),
		"begin" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:wse_events/locallang_db.php:tx_wseevents_timeslots.begin",		
			"config" => Array (
				"type" => "input",
				"size" => "12",
				"max" => "20",
				"eval" => "datetime",
				"checkbox" => "0",
				"default" => "0"
			)
		),
		"end" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:wse_events/locallang_db.php:tx_wseevents_timeslots.end",		
			"config" => Array (
				"type" => "input",
				"size" => "12",
				"max" => "20",
				"eval" => "datetime",
				"checkbox" => "0",
				"default" => "0"
			)
		),
		"sessions" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:wse_events/locallang_db.php:tx_wseevents_timeslots.sessions",		
			"config" => Array (
				"type" => "select",	
				'items' => Array (
					Array("LLL:EXT:wse_events/locallang_db.php:tx_wseevents_timeslots.nosessions", '')
				),
				"foreign_table" => "tx_wseevents_sessions",	
				"foreign_table_where" => "AND tx_wseevents_sessions.event=###REC_FIELD_event### ORDER BY tx_wseevents_sessions.name",	
				"size" => 1,	
				"minitems" => 0,
				"maxitems" => 1,
			)
		),
	),
	"types" => Array (
		"0" => Array("showitem" => "sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, hidden;;1, event, name, begin, end, sessions")
	),
	"palettes" => Array (
		"1" => Array("showitem" => "")
	)
);



$TCA["tx_wseevents_sessions"] = Array (
	"ctrl" => $TCA["tx_wseevents_sessions"]["ctrl"],
	"interface" => Array (
		"showRecordFieldList" => "sys_language_uid,l18n_parent,l18n_diffsource,hidden,event,name,speaker,room,timeslots,categorie,number,teaser,description"
	),
	"feInterface" => $TCA["tx_wseevents_sessions"]["feInterface"],
	"columns" => Array (
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
		"hidden" => Array (		
			"exclude" => 1,
			"label" => "LLL:EXT:lang/locallang_general.php:LGL.hidden",
			"config" => Array (
				"type" => "check",
				"default" => "0"
			)
		),
		"event" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:wse_events/locallang_db.php:tx_wseevents_sessions.event",		
			"config" => Array (
				"type" => "select",	
				"foreign_table" => "tx_wseevents_events",	
				"foreign_table_where" => "AND tx_wseevents_events.pid=###CURRENT_PID### ORDER BY tx_wseevents_events.uid",	
				"size" => 1,	
				"minitems" => 0,
				"maxitems" => 1,
			)
		),
		"name" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:wse_events/locallang_db.php:tx_wseevents_sessions.name",		
			"config" => Array (
				"type" => "input",	
				"size" => "30",	
				"eval" => "required",
			)
		),
		"speaker" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:wse_events/locallang_db.php:tx_wseevents_sessions.speaker",		
			"config" => Array (
				"type" => "select",	
				"foreign_table" => "tx_wseevents_speakers",	
				"foreign_table_where" => "ORDER BY tx_wseevents_speakers.uid",	
				"size" => 4,	
				"minitems" => 0,
				"maxitems" => 4,
			)
		),
		"room" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:wse_events/locallang_db.php:tx_wseevents_sessions.room",		
			"config" => Array (
				"type" => "select",	
				"foreign_table" => "tx_wseevents_rooms",	
				"foreign_table_where" => "ORDER BY tx_wseevents_rooms.uid",	
				"size" => 1,	
				"minitems" => 0,
				"maxitems" => 1,
			)
		),
		"timeslots" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:wse_events/locallang_db.php:tx_wseevents_sessions.timeslots",		
			"config" => Array (
				"type" => "select",	
				"foreign_table" => "tx_wseevents_timeslots",	
				"foreign_table_where" => "ORDER BY tx_wseevents_timeslots.uid",	
				"size" => 3,	
				"minitems" => 0,
				"maxitems" => 3,
			)
		),
		"categorie" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:wse_events/locallang_db.php:tx_wseevents_sessions.categorie",		
			"config" => Array (
				"type" => "select",	
				"foreign_table" => "tx_wseevents_categories",	
				"foreign_table_where" => "ORDER BY tx_wseevents_categories.uid",	
				"size" => 1,	
				"minitems" => 0,
				"maxitems" => 1,
			)
		),
		"number" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:wse_events/locallang_db.php:tx_wseevents_sessions.number",		
			"config" => Array (
				"type" => "input",
				"size" => "4",
				"max" => "4",
				"eval" => "int",
				"checkbox" => "0",
				"range" => Array (
					"upper" => "1000",
					"lower" => "10"
				),
				"default" => 0
			)
		),
		"teaser" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:wse_events/locallang_db.php:tx_wseevents_sessions.teaser",		
			"config" => Array (
				"type" => "text",
				"cols" => "30",	
				"rows" => "3",
			)
		),
		"description" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:wse_events/locallang_db.php:tx_wseevents_sessions.description",		
			"config" => Array (
				"type" => "text",
				"cols" => "30",
				"rows" => "5",
				"wizards" => Array(
					"_PADDING" => 2,
					"RTE" => Array(
						"notNewRecords" => 1,
						"RTEonly" => 1,
						"type" => "script",
						"title" => "Full screen Rich Text Editing|Formatteret redigering i hele vinduet",
						"icon" => "wizard_rte2.gif",
						"script" => "wizard_rte.php",
					),
				),
			)
		),
	),
	"types" => Array (
		"0" => Array("showitem" => "sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, hidden;;1, event, name, speaker, room, timeslots, categorie, number, teaser, description;;;richtext[paste|bold|italic|underline|formatblock|class|left|center|right|orderedlist|unorderedlist|outdent|indent|link|image]:rte_transform[mode=ts]")
	),
	"palettes" => Array (
		"1" => Array("showitem" => "")
	)
);



$TCA["tx_wseevents_speakers"] = Array (
	"ctrl" => $TCA["tx_wseevents_speakers"]["ctrl"],
	"interface" => Array (
		"showRecordFieldList" => "sys_language_uid,l18n_parent,l18n_diffsource,hidden,name,email"
	),
	"feInterface" => $TCA["tx_wseevents_speakers"]["feInterface"],
	"columns" => Array (
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
		"hidden" => Array (		
			"exclude" => 1,
			"label" => "LLL:EXT:lang/locallang_general.php:LGL.hidden",
			"config" => Array (
				"type" => "check",
				"default" => "0"
			)
		),
		"name" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:wse_events/locallang_db.php:tx_wseevents_speakers.name",		
			"config" => Array (
				"type" => "input",	
				"size" => "30",	
				"eval" => "required",
			)
		),
		"email" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:wse_events/locallang_db.php:tx_wseevents_speakers.email",		
			"config" => Array (
				"type" => "input",	
				"size" => "30",
			)
		),
	),
	"types" => Array (
		"0" => Array("showitem" => "sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, hidden;;1, name, email")
	),
	"palettes" => Array (
		"1" => Array("showitem" => "")
	)
);



$TCA["tx_wseevents_categories"] = Array (
	"ctrl" => $TCA["tx_wseevents_categories"]["ctrl"],
	"interface" => Array (
		"showRecordFieldList" => "sys_language_uid,l18n_parent,l18n_diffsource,hidden,name,shortkey"
	),
	"feInterface" => $TCA["tx_wseevents_categories"]["feInterface"],
	"columns" => Array (
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
		"hidden" => Array (		
			"exclude" => 1,
			"label" => "LLL:EXT:lang/locallang_general.php:LGL.hidden",
			"config" => Array (
				"type" => "check",
				"default" => "0"
			)
		),
		"name" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:wse_events/locallang_db.php:tx_wseevents_categories.name",		
			"config" => Array (
				"type" => "input",	
				"size" => "30",	
				"eval" => "required",
			)
		),
		"shortkey" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:wse_events/locallang_db.php:tx_wseevents_categories.shortkey",		
			"config" => Array (
				"type" => "input",	
				"size" => "5",	
				"max" => "3",	
				"eval" => "required,upper,nospace,uniqueInPid",
			)
		),
	),
	"types" => Array (
		"0" => Array("showitem" => "sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, hidden;;1, name, shortkey")
	),
	"palettes" => Array (
		"1" => Array("showitem" => "")
	)
);
?>