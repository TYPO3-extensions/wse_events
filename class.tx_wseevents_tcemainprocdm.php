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
 * @author		Michael Oehlhof
 */

 class tx_wseevents_tcemainprocdm {
    function processDatamap_postProcessFieldArray ($status, $table, $id, &$fieldArray, &$reference) ) {
        if ($table == 'tx_wseevents_speakers') {
			// Set the fullname with name, firstname
			$fieldArray['fullname'] = $fieldArray['name'].', '.$fieldArray['firstname'];
		}
    }
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wse_events/class.tx_wseevents_tcemainprocdm.php']) {
	include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wse_events/class.tx_wseevents_tcemainprocdm.php']);

?>
