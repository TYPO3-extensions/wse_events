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


require_once(t3lib_extMgm::extPath('wse_events').'class.tx_wseevents_timeslots.php');
require_once(t3lib_extMgm::extPath('wse_events').'class.tx_wseevents_events.php');

/**
 * Class 'tx_wseevents_tcemainprocdm' for the 'wse_events' extension.
 *
 * @package		TYPO3
 * @subpackage	wse_events
 * @author		Michael Oehlhof <typo3@oehlhof.de>
 */
class tx_wseevents_tcemainprocdm {

	/**
	 * Post processiong of field array
	 *
	 * @param	string		$status: edit status
	 * @param	string		$table: table name
	 * @param	integer		$id: record id
	 * @param	array		$incomingFieldArray: record data
	 * @param	array		$reference: ToDo: insert description
	 * @return	void		...
	 */
    function processDatamap_postProcessFieldArray ($status, $table, $id, &$fieldArray, &$reference) {
        if ($table == 'tx_wseevents_speakers') {
			$name = $fieldArray['name'];
			$firstname = $fieldArray['firstname'];
			// Set the fullname with name, firstname
			if ($status == 'update') {
				$row = t3lib_BEfunc::getRecord ($table, $id);
				if (is_array ($row)) {
					if (empty($name)) {
						$name = $row['name'];
					}
					if (empty($firstname)) {
						$firstname = $row['firstname'];
					}
				}
			}
			$fieldArray['fullname'] = $name.', '.$firstname;
		}
        if ($table == 'tx_wseevents_timeslots') {
#debug($fieldArray,'$fieldArray');
			if ($status == 'update') {
				// If record is edited, than read the data from database
				$row = t3lib_BEfunc::getRecord ($table, $id);
			} else {
				// If record is created, read the data from input fields
				$row = array();
			}
			if (!empty($fieldArray['event']))		$row['event'] = $fieldArray['event'];
			if (!empty($fieldArray['eventday']))	$row['eventday'] = $fieldArray['eventday'];
			if (!empty($fieldArray['begin']))		$row['begin'] = $fieldArray['begin'];
			if (!empty($fieldArray['length']))		$row['length'] = $fieldArray['length'];
			if (!empty($fieldArray['room']))		$row['room'] = $fieldArray['room'];

			$fieldArray['name'] = tx_wseevents_timeslots::formatSlotName($row);
		}
        if ($table == 'tx_wseevents_speakerrestrictions') {
			if ($status == 'update') {
				// If record is edited, than read the data from database
				$row = t3lib_BEfunc::getRecord ($table, $id);
				if (isset($fieldArray['begin'])) {
					$row['begin'] = $fieldArray['begin'];
				}
				if (isset($fieldArray['end'])) {
					$row['end'] = $fieldArray['end'];
				}
			} else {
				// If record is created, read the data from input fields
				$row = array();
				$row['begin'] = $fieldArray['begin'];
				$row['end']   = $fieldArray['end'];
				$row['event'] = $fieldArray['event'];
			}
#			$eventslots = tx_wseevents_events::getEventSlotList($row['event']);
			if ($row['end']<$row['begin']) {
				$fieldArray['end'] = $row['begin'];
			}
		}
    }

	/**
	 * Pre processiong of field array
	 *
	 * @param	array		$incomingFieldArray: record data
	 * @param	string		$table: table name
	 * @param	integer		$id: record id
	 * @param	array		$this: ToDo: insert description
	 * @return	void		...
	 */
    function processDatamap_preProcessFieldArray ($incomingFieldArray, $table, $id, &$reference) {
        if ($table == 'tx_wseevents_timeslots') {
			// Set the default slot length
# ToDo: Get the default slot length from event record
#			$row = t3lib_BEfunc::getRecord ($table, $id);
			if (is_array ($row)) {
				$incomingFieldArray['length'] = 4;
			}
		}
    }
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wse_events/class.tx_wseevents_tcemainprocdm.php']) {
	include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wse_events/class.tx_wseevents_tcemainprocdm.php']);
}
?>