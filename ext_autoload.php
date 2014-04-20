<?php
$extensionPath = t3lib_extMgm::extPath('wse_events');
return array(
	'tx_wseevents_categories' => $extensionPath . 'class.tx_wseevents_categories.php',
	'tx_wseevents_dbplugin' => $extensionPath . 'class.tx_wseevents_dbplugin.php',
	'tx_wseevents_events' => $extensionPath . 'class.tx_wseevents_events.php',
	'tx_wseevents_locations' => $extensionPath . 'class.tx_wseevents_locations.php',
	'tx_wseevents_rooms' => $extensionPath . 'class.tx_wseevents_rooms.php',
	'tx_wseevents_speakers' => $extensionPath . 'class.tx_wseevents_speakers.php',
	'tx_wseevents_tcemainprocdm' => $extensionPath . 'class.tx_wseevents_tcemainprocdm.php',
	'tx_wseevents_timeslots' => $extensionPath . 'class.tx_wseevents_timeslots.php',
	'tx_wseevents_backendlist' => $extensionPath . 'mod1/class.tx_wseevents_backendlist.php',
	'tx_wseevents_categorieslist' => $extensionPath . 'mod1/class.tx_wseevents_categorieslist.php',
	'tx_wseevents_eventslist' => $extensionPath . 'mod1/class.tx_wseevents_eventslist.php',
	'tx_wseevents_locationslist' => $extensionPath . 'mod1/class.tx_wseevents_locationslist.php',
	'tx_wseevents_roomslist' => $extensionPath . 'mod1/class.tx_wseevents_roomslist.php',
	'tx_wseevents_sessionplanninglist' => $extensionPath . 'mod1/class.tx_wseevents_sessionplanninglist.php',
	'tx_wseevents_sessionslist' => $extensionPath . 'mod1/class.tx_wseevents_sessionslist.php',
	'tx_wseevents_speakerrestrictionslist' => $extensionPath . 'mod1/class.tx_wseevents_speakerrestrictionslist.php',
	'tx_wseevents_speakerslist' => $extensionPath . 'mod1/class.tx_wseevents_speakerslist.php',
	'tx_wseevents_timeslotslist' => $extensionPath . 'mod1/class.tx_wseevents_timeslotslist.php',
);
