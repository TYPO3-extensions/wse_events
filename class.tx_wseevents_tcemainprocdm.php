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

/**
 * Class 'tx_wseevents_tcemainprocdm' for the 'wse_events' extension.
 *
 * @package		TYPO3
 * @subpackage	wse_events
 * @author		Michael Oehlhof <typo3@oehlhof.de>
 */

require_once(t3lib_extMgm::extPath('wse_events').'class.tx_wseevents_timeslots.php');

class tx_wseevents_tcemainprocdm {
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
			if ($status == 'update') {
				// If record is edited, than read the data from database
				$row = t3lib_BEfunc::getRecord ($table, $id);
			} else {
				// If record is created, read the data from input fields
				$row = array();
				$row['event'] = $fieldArray['event'];
				$row['eventday'] = $fieldArray['eventday'];
				$row['begin'] = $fieldArray['begin'];
				$row['length'] = $fieldArray['length'];
				$row['room'] = $fieldArray['room'];
			}
			$fieldArray['name'] = tx_wseevents_timeslots::formatSlotName($row);
		}
    }
	
    function processDatamap_preProcessFieldArray ($incomingFieldArray, $table, $id, $this) {
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