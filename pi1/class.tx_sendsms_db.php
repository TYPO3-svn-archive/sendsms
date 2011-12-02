<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 Alexander Kraskov <t3extensions@developergarden.com>
*      Developer Garden (www.developergarden.com)
*	   Deutsche Telekom AG
*      Products & Innovation
*
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
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   51: class tx_sendsms_db
 *   60:     public function addInStatistics($now, $length)
 *   84:     public function addSmsInTable($feUserId, $smsSent, $smsSentInPeriod, $periodStart, $periodEnd)
 *  106:     public function addUserInTable($feUserId, $periodStart, $periodEnd)
 *  126:     public function getCountOfSms($feUserId)
 *
 * TOTAL FUNCTIONS: 4
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

/**
 * Database acces for the 'sendsms' extension.
 *
 * @author	Alexander Kraskov <t3extensions@developergarden.com>
 * @package	TYPO3
 * @subpackage	tx_sendsms
 */
class tx_sendsms_db {

	/**
	 * Adds date, time and length of new sms
	 *
	 * @param	array		$now: current date and time
	 * @param	int		$length: length of sms
	 * @return	void
	 */
	public function addInStatistics($now, $length) {
		$insertFields = array(
			'sms_day' => $now['mday'],
			'sms_month' => $now['mon'],
			'sms_year' => $now['year'],
			'sms_hour' => $now['hours'],
			'sms_length' => $length
		);
		$GLOBALS['TYPO3_DB']->exec_INSERTquery(
			'tx_sendsms_statistics',
			$insertFields
		);
	}

	/**
	 * Updates in database
	 *
	 * @param	int		$feUserId: Frontend user ID
	 * @param	int		$sms_sent: General number of sent sms by user
	 * @param	int		$sms_sent_in_period: Number of sent sms in period
	 * @param	string		$periodStart: Time interval, in database-format
	 * @param	string		$periodEnd: Time interval, in database-format
	 * @return	void
	 */
	public function addSmsInTable($feUserId, $smsSent, $smsSentInPeriod, $periodStart, $periodEnd) {
		$fieldsValues = array(
			'sms_sent' => $smsSent,
			'sms_sent_in_period' => $smsSentInPeriod,
			'period_start' => $periodStart,
			'period_end' => $periodEnd,
		);
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
			'tx_sendsms_sms',
			'fe_user_id=' . $feUserId,
			$fieldsValues
		);
	}

	/**
	 * Inserts in database
	 *
	 * @param	int		$feUserId: Frontend user ID
	 * @param	string		$periodStart: Time interval, in database-format
	 * @param	string		$periodEnd: Time interval, in database-format
	 * @return	void
	 */
	public function addUserInTable($feUserId, $periodStart, $periodEnd) {
		$insertFields = array(
			'fe_user_id' => $feUserId,
			'sms_sent' => 0,
			'sms_sent_in_period' => 0,
			'period_start' => $periodStart,
			'period_end' => $periodEnd,
		);
		$GLOBALS['TYPO3_DB']->exec_INSERTquery(
			'tx_sendsms_sms',
			$insertFields
		);
	}

	/**
	 * Selects from database
	 *
	 * @param	int		$feUserId: Frontend user ID
	 * @return	array		null or one row from table tx_sendsms_sms
	 */
	public function getCountOfSms($feUserId) {
		$retValue = NULL;
        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'*',
            'tx_sendsms_sms',
            'fe_user_id=' . $feUserId
			);
		// This is right! Only one "="
		if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			$retValue = $row;
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($res);
		return $retValue;
	}
}

if (!defined ('PATH_typo3conf')) die ('Resistance is futile.');

?>
