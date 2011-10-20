<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 Alexander Kraskov <alexander.kraskov@telekom.de>
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
 *   82: class tx_sendsms_pi1 extends tslib_pibase
 *   91:     protected function dbGetCountOfSms($feUserId)
 *  115:     protected function dbAddUserInTable($feUserId, $periodStart, $periodEnd)
 *  139:     protected function dbAddSmsInTable($feUserId, $smsSent, $smsSentInPeriod, $periodStart, $periodEnd)
 *  160:     protected function dbAddInStatistics($now, $length)
 *  180:     protected function count($text)
 *  194:     protected function countSms($text)
 *  209:     protected function errorText($err, $id)
 *  222:     protected function validateNumber($text)
 *  246:     protected function validateAllNumbers($text, $codes = NULL)
 *  277:     protected function priceOfSms($arr)
 *  310:     protected function linkToDoc($text, $formatLink, $formatLinkAddParams)
 *  330:     protected function getDisabledForm($textAreaRows, $textAreaCols, $formLogin = FALSE)
 *  365:     protected function addJs($phoneInputMin, $phoneInputMax, $phoneInputToggle, $smsLeft, $maxSms, $hideCharactersCount, $phones)
 *  483:     protected function addCss()
 *  500:     protected function formPhoneNumbersFormat($hideNumberFormat, $formatLink, $formatLinkAddParams)
 *  523:     protected function formEnvironment($hideEnvironment, $formatLink4, $formatLinkAddParams4, $environment)
 *  539:     protected function formFlashSms($flashSms, $formatLink3, $formatLinkAddParams3)
 *  563:     protected function formCharactersCount($numberOfSms, $smsInPeriod, $smsMaxLength, $formatLink2, $formatLinkAddParams2,
	$signature, $hideSignatureExplanation, $hideCharactersCount)
 *  602:     protected function formSmsCount($numberOfSms, $smsInPeriod, $signature, $formatLink2, $formatLinkAddParams2, $hideCharactersCount)
 *  630:     protected function formTextArea($textAreaRows, $textAreaCols, $signature)
 *  649:     protected function formCaptcha($markerArray)
 *  673:     protected function formSignature($signature, $websiteName, $signatureWrap, $hideSignatureExplanation)
 *  696:     protected function formPhoneInput($phoneInputMin, $signature, $phoneInputToggle, $phoneInputToggleText,
	$numberOfRecipients, $hideRestrictions)
 *  740:     function main($content, $conf)
 *
 * TOTAL FUNCTIONS: 24
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */
// Base extension class
require_once(PATH_tslib.'class.tslib_pibase.php');
// Includes the Send SMS client
require_once(dirname(__FILE__) . '/../lib/sdk/sendsms/client/SendSmsClient.php');
require_once(dirname(__FILE__) . '/../lib/sdk/sendsms/data/SendSmsStatusConstants.php');
// Includes the Calling Codes
require_once('class.tx_sendsms_callingcodes.php');
/**
 * Plugin 'Developer Garden: Send SMS' for the 'sendsms' extension.
 *
 * @author	Alexander Kraskov <alexander.kraskov@telekom.de>
 * @package	TYPO3
 * @subpackage	tx_sendsms
 */
class tx_sendsms_pi1 extends tslib_pibase {
	var $prefixId      = 'tx_sendsms_pi1';					// Same as class name
	var $scriptRelPath = 'pi1/class.tx_sendsms_pi1.php';	// Path to this script relative to the extension dir.
	var $extKey        = 'sendsms';							// The extension key.
	protected $prefixClass   = 'tx-sendsms';				// Same as class name
	protected $freeCap = NULL; 								// CAPTCHA
	/**
	 * Selects number of user's sms from table tx_sendsms_sms
	 *
	 * @param	int		$feUserId: Frontend user ID
	 * @return	array		null or one row from table tx_sendsms_sms
	 */
	protected function dbGetCountOfSms($feUserId) {
		$retValue = NULL;
        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'*',
            'tx_sendsms_sms',
            'fe_user_id=' . $feUserId
			);

		if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			$retValue = $row;
		}

		$GLOBALS['TYPO3_DB']->sql_free_result($res);

		return $retValue;
	}
	/**
	 * Inserts new users in table tx_sendsms_sms
	 *
	 * @param	int		$feUserId: Frontend user ID
	 * @param	string		$periodStart: Time interval, in database-format
	 * @param	string		$periodEnd: Time interval, in database-format
	 * @return	void
	 */
	protected function dbAddUserInTable($feUserId, $periodStart, $periodEnd) {
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
	 * Updates number of users's sms in table tx_sendsms_sms
	 *
	 * @param	int		$feUserId: Frontend user ID
	 * @param	int		$sms_sent: General number of sent sms by user
	 * @param	int		$sms_sent_in_period: Number of sent sms in period
	 * @param	string		$periodStart: Time interval, in database-format
	 * @param	string		$periodEnd: Time interval, in database-format
	 * @return	void
	 */
	protected function dbAddSmsInTable($feUserId, $smsSent, $smsSentInPeriod, $periodStart, $periodEnd) {
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
	 * Adds date, time and length of new sms in table tx_sendsms_statistcs
	 *
	 * @param	array		$now: current date and time
	 * @param	int		$length: length of sms
	 * @return	void
	 */
	protected function dbAddInStatistics($now, $length) {
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
	 * Counts chars in the text; some chars are counted as 2 characters
	 *
	 * @param	string		$text: message
	 * @return	int		number of chars in message
	 */
	protected function count($text) {
		preg_match_all('/\x{20ac}|\x{005C}|\n|~|\^|\[|\]|\{|\}|\|/u', $text, $matches);
		$c = 0;
		if ($matches[0]) {
			$c = count($matches[0]);
		}
		return strlen($text) + $c - substr_count($text, '€') * 2;
	}
	/**
	 * Counts how many sms contains the message
	 *
	 * @param	string		$text: message
	 * @return	int		number of sms
	 */
	protected function countSms($text) {
		$c = $this->count($text);
		if ($c < 161) {
			return 1;
		} else {
			return ceil($c / 153);
		}
	}
	/**
	 * Returns span-element with error message if $err==TRUE
	 *
	 * @param	boolean		$err: first value
	 * @param	int		$id: id of error message (see locallang.xml)
	 * @return	string		empty string or span-element with error message
	 */
	protected function errorText($err, $id) {
		if ($err) {
			return '<span class="' . $this->prefixClass . '-error">' . htmlspecialchars($this->pi_getLL($id)) . '</span><br />';
		} else {
			return '';
		}
	}
	/**
	 * Validates only one phone number
	 *
	 * @param	string		$text: One phone number from all entered numbers
	 * @return	boolean		Validation mark
	 */
	protected function validateNumber($text) {
		preg_match_all('/[\+a-zA-Z0-9]+/', $text, $matches);
		$num = '';
		if ($matches[0]) {
			foreach ($matches[0] as $n)	{
				$num .= $n;
			}
		}
		if (preg_match('/^((0|(00)|\+)[1-9]{1}[0-9]{0,3})[a-zA-Z0-9]{3,}$/', $num)) {
			if (preg_match('/^[-\+a-zA-Z0-9\(\)\[\]<>\s]{1,}$/', $text)) {
				return TRUE;
			} else {
				return FALSE;
			}
		}
		return FALSE;
	}
	/**
	 * Validates all phone numbers
	 *
	 * @param	string		$text: Text, that user entered
	 * @param	object		$codes: Class tx_sendsms_callingcodes object, find calling code for phone numbers
	 * @return	array		array with phone numbers, tarifzones and validation mark (true/false)
	 */
	protected function validateAllNumbers($text, $codes = NULL) {
		preg_match_all('/([^,])+/u', $text, $matches);
		$res = array();
		if ($matches[0]) {
			foreach ($matches[0] as $n)	{
				preg_match_all('/[\+a-zA-Z0-9]+/', $n, $m);
				$cleanNum = '';
				if ($m[0]) {
					foreach ($m[0] as $nn) {
						$cleanNum .= $nn;
					}
				}
				$cleanNum = trim($cleanNum);
				if (strlen($cleanNum) > 0) {
					if (is_null($codes)) {
						$res[] = array($this->validateNumber($n), $cleanNum, $n);
					} else {
						$code = $codes->getCallingCode($cleanNum);
						$res[] = array($this->validateNumber($n), $cleanNum, $n, $code, $codes->getTarifzone($code));
					}
				}
			}
		}
		return $res;
	}
	/**
	 * Counts price of all SMS witch has been sent
	 *
	 * @param	array		$arr: Array with all phone numbers and zones (1-4)
	 * @return	float		price
	 */
	protected function priceOfSms($arr) {
		$sum = 0;
		foreach ($arr as $r) {
			if (!is_null($r[4])) {
				switch ($r[4]) {
					case 0:
						$sum += $this->countSms($message) * 0.099;
						break;
					case 1:
						$sum += $this->countSms($message) * 0.105;
						break;
					case 2:
						$sum += $this->countSms($message) * 0.127;
						break;
					case 3:
						$sum += $this->countSms($message) * 0.165;
						break;
					case 4:
						$sum += $this->countSms($message) * 0.202;
						break;
				}
			}
		}
		return $sum;
	}
	/**
	 * Returns TypoLink to documentation
	 *
	 * @param	string		$text: Text to display on page
	 * @param	string		$formatLink: Page ID
	 * @param	string		$formatLinkAddParams: Page additional parameters
	 * @return	string		Complete TypoLink with cHash
	 */
	protected function linkToDoc($text, $formatLink, $formatLinkAddParams) {
		if ($formatLink) {
			$conf = array (
				'parameter' => $formatLink,
				'additionalParams' => $formatLinkAddParams,
				'target' => 'blank',
				'useCacheHash' => 1,
			);
			return $this->cObj->typoLink($text, $conf);
		}
		return $text;
	}
	/**
	 * Returns disabled main form
	 *
	 * @param	int		$textAreaRows: textarea rows
	 * @param	int		$textAreaCols: textarea columns
	 * @param	bool		$formLogin: if TRUE, shows message "you must login in to send sms"
	 * @return	string		main html form with disabled elements
	 */
	protected function getDisabledForm($textAreaRows, $textAreaCols, $formLogin = FALSE) {
		$form = '<form>';
		if ($formLogin) {
			$form .= '<label>' . htmlspecialchars($this->pi_getLL('form_login')) . '</label><br /><br />';
		}
		$form .= '<label for="' . $this->prefixId . '_phone">' .
			htmlspecialchars($this->pi_getLL('form_phone')) .
			'</label><br />' .
			'<input type="text" id="' . $this->prefixId . '_phone" value="" disabled="disabled">' .
			'<br /><br />' .
			'<label for="' . $this->prefixId . '_text">' .
				htmlspecialchars($this->pi_getLL('form_message')) .
			'</label><br />' .
			'<textarea id="' . $this->prefixId . '_text"' .
			' rows="' . $textAreaRows . '"' .
			' cols="' . $textAreaCols . '"' .
			' disabled="disabled">' .
			'</textarea>' .
			'<br /><br />' .
			'<input disabled="disabled" type="submit"'.
			' value="' . htmlspecialchars($this->pi_getLL('form_submit')) . '">' .
			'</form>';
		return $form;
	}
	/**
	 * Adds JavaScript to HTML document
	 *
	 * @param	int		$phoneInputMin: minimal size of input element
	 * @param	int		$phoneInputMax: maximal size of input element
	 * @param	bool		$phoneInputToggle: show toggle link
	 * @param	int		$smsLeft: how many free sms left
	 * @param	int		$maxSms: maximal number of sms
	 * @param	bool		$hideCharactersCount: if true, the function returns empty string
	 * @param	string		$phones: Feedback phone numbers 
	 * @return	void
	 */
	protected function addJs($phoneInputMin, $phoneInputMax, $phoneInputToggle, $smsLeft, $maxSms, $hideCharactersCount, $phones) {
		$jsCode = '';
		if ($phoneInputToggle == TRUE) {
			$jsCode .= '
				function morePhones() {
					var phone = document.getElementById("' . $this->prefixId . '_phone");
					var inputSize = document.getElementById("' . $this->prefixId . '_inputsize");
					if (phone.size == ' . $phoneInputMax . ') {
						phone.size = "' . $phoneInputMin . '";
						inputSize.value = "' . $phoneInputMin . '";
					} else {
						phone.size = ' . $phoneInputMax . ';
						inputSize.value = ' . $phoneInputMax . ';
					}
				}
			';
		}
		if ($hideCharactersCount) {
			$jsCode .= 'function smsLength(c) {return false;}';
		} else {
			$jsCode .= '
				// Counts chars, witch have 2 places in sms in text
				function getChars(txt) {
					if(!txt) {
						return 0;
					}
					var doubleCount = txt.match(/\^|{|}|\u005c|\[|\]|~|\||\u20ac/g);
					if(doubleCount) {
						doubleCount = doubleCount.length;
					} else {
						doubleCount = 0;
					}
					return doubleCount + txt.length;
				}
				// Deletes spaces in text
				function trim (text) {
					return text.replace(/\s/g, "");
				}
				// Counts recipients in recipients input
				function R(txt) {
					if(!txt) {
						return 1;
					}
					var c = 0;
					var myArray = txt.match(/([^,])+/g);
					if (myArray) {
						for (var i = 0; i < myArray.length; i++) {
							if (trim(myArray[i]).length > 0) {
								c++;
							}
						}
						return c;
					}
					return 1;
				}
				//displays the number of characters and SMS in the form
				function smsLength(c) {
					var smsText = document.getElementById("' . $this->prefixId . '_text");';
					if (!is_null($phones)) {
						$jsCode .= 'var recipients = ' . count($this->validateAllNumbers($phones)) . ';';
					} else {
						$jsCode .= 'var phones = document.getElementById("' . $this->prefixId . '_phone");' .
							'var recipients = R(phones.value);';
					}
					$jsCode .= 'var labelChars = document.getElementById("' . $this->prefixId . '_text_chars");
					var labelSms = document.getElementById("' . $this->prefixId . '_text_sms");
					var smsLeft = ' . $smsLeft . ';
					var len = getChars(smsText.value);
					if(len == 0) {
						labelChars.innerHTML = c;
						labelSms.innerHTML = recipients;
						if (recipients > smsLeft) {
							labelSms.style.color = "red";
							labelChars.style.color = "red";
						} else {
							labelSms.style.color = "";
							labelChars.style.color = "";
						}
						return;
					}
					var sms;
					var max = ' . $maxSms . ';
					if (c + len > 160) {
						sms = Math.ceil((c+len) / 153);
					} else {
						sms = Math.ceil((c+len) / 160);
					}
					if (sms * recipients > max) {
						labelSms.style.color = "red";
						labelChars.style.color = "red";
					} else {
						labelSms.style.color = "";
						labelChars.style.color = "";
					}
					labelChars.innerHTML = c+len;
					labelSms.innerHTML = sms * recipients;
					return;
				}
			';
		}
		if(!$GLOBALS['TSFE']->additionalHeaderData[$this->prefixId]){
			$GLOBALS['TSFE']->additionalHeaderData[$this->prefixId] = t3lib_div::wrapJS($jsCode);
		} else {
			$GLOBALS['TSFE']->additionalHeaderData[$this->prefixId] .= t3lib_div::wrapJS($jsCode);
		}
	}
	/**
	 * Adds link to CSS in HTML document
	 *
	 * @return	void
	 */
	protected function addCss() {
		$css = '<link rel="stylesheet" type="text/css" href="' .
			t3lib_extMgm::siteRelPath($this->extKey) . 'res/tx_sendsms_style.css" />';
		if(!$GLOBALS['TSFE']->additionalHeaderData[$this->prefixId]){
			$GLOBALS['TSFE']->additionalHeaderData[$this->prefixId] = $css;
		} else {
			$GLOBALS['TSFE']->additionalHeaderData[$this->prefixId] .= $css;
		}
	}
	/**
	 * Returns html string with characters and sms counter
	 *
	 * @param	bool		$hideNumberFormat: flag to hide this string (if true, returns empty string)
	 * @param	string		$formatLink: page id
	 * @param	string		$formatLinkAddParams: page additional parameters
	 * @return	string		html elements
	 */
	protected function formPhoneNumbersFormat($hideNumberFormat, $formatLink, $formatLinkAddParams) {
		$retValue = '';
		if (!$hideNumberFormat) {
			if (!$formatLink) {
				$retValue = '<label for="' . $this->prefixId . '_phone">' .
					htmlspecialchars($this->pi_getLL('form_format')) . '</label><br />';
			} else {
				$retValue = '<label for="' . $this->prefixId . '_phone">' .
				sprintf($this->pi_getLL('form_format_template'), htmlspecialchars($this->pi_getLL('form_format')),
					$this->linkToDoc(htmlspecialchars($this->pi_getLL('form_format_link')), $formatLink, $formatLinkAddParams)) . '</label><br />';
			}
		}
		return $retValue;
	}
	/**
	 * Returns html string with enviroment and link to documentation
	 *
	 * @param	bool		$hideNumberFormat: flag to hide this string (if true, returns empty string)
	 * @param	string		$formatLink4: page id
	 * @param	string		$formatLinkAddParams4: page additional parameters
	 * @param	string		$environment: enviroment (production, sandbox or mock)
	 * @return	string		html elements
	 */
	protected function formEnvironment($hideEnvironment, $formatLink4, $formatLinkAddParams4, $environment) {
		$retValue = '';
		if (!$hideEnvironment) {
			$link = $this->linkToDoc(htmlspecialchars($this->pi_getLL('environment')), $formatLink4, $formatLinkAddParams4);
			$retValue =  '<span>' . sprintf($this->pi_getLL('environment_template'), $link, $environment) . '</span>';
		}
		return $retValue;
	}
	/**
	 * Returns html string with enviroment and link to documentation
	 *
	 * @param	bool		$flashSms: show or hide the flash sms option
	 * @param	string		$formatLink3: Page ID
	 * @param	string		$formatLinkAddParams3: page additional parameters
	 * @return	string		html elements
	 */
	protected function formFlashSms($flashSms, $formatLink3, $formatLinkAddParams3) {
		$retValue = '';
		if ($flashSms) {
			$retValue = '<div class="' . $this->prefixClass . '-flash"><input type="checkbox"' .
				' id="' . $this->prefixId . '_flash_sms" name="' . $this->prefixId . '[flash_sms]" value="1" />' .
				'<label for="' . $this->prefixId . '_flash_sms">&nbsp;' .
				sprintf(htmlspecialchars($this->pi_getLL('form_flash')), $this->linkToDoc(htmlspecialchars($this->pi_getLL('form_flash_link')),
				$formatLink3, $formatLinkAddParams3)) . '</label></div>';
		}
		return $retValue;
	}
	/**
	 * Returns html string with count of characters and link to documentation
	 *
	 * @param	int		$numberOfSms: maximal allowed number of sms
	 * @param	int		$smsInPeriod: how many sms user sent already in period
	 * @param	int		$smsMaxLength: maximal length of sms (765 or 160)
	 * @param	string		$formatLink2: page id
	 * @param	string		$formatLinkAddParams2: page additional parameters
	 * @param	string		$signature: the message's signature (from FlexForm)
	 * @param	bool		$hideSignatureExplanation: hides
	 * @param	bool		$hideCharactersCount: if true, returns empty string
	 * @return	string		html elements
	 */
	protected function formCharactersCount($numberOfSms, $smsInPeriod, $smsMaxLength, $formatLink2, $formatLinkAddParams2,
	$signature, $hideSignatureExplanation, $hideCharactersCount) {
		$retValue = '';
		if (!$hideCharactersCount) {
			$text = $this->linkToDoc(htmlspecialchars($this->pi_getLL('form_characters')), $formatLink2, $formatLinkAddParams2);
			$number = '<span id="' . $this->prefixId . '_text_chars"';
			if ($numberOfSms - $smsInPeriod > 4) {
				$limit = $smsMaxLength;
			} elseif ($numberOfSms - $smsInPeriod > 1) {
				$limit = ($numberOfSms - $smsInPeriod) * 153;
			} else {
				$limit = 160;
			}
			if ($this->count($this->piVars['smstext'] . $signature) > $limit) {
				$number .= ' style="color:red;"';
			}
			$number .= '>' . $this->count($this->piVars['smstext'] . $signature) . '</span>';
			$footnote = '';
			if (!$hideSignatureExplanation && strlen($signature) > 0)
			{
				$footnote = htmlspecialchars($this->pi_getLL('form_characters_footnote_sign'));
			}
			$retValue = '<label for="' . $this->prefixId . '_text">' .
				sprintf($this->pi_getLL('form_characters_template'), $text, $number, $footnote) .
				'</label>';
		}
		return $retValue;
	}
	/**
	 * Returns html string with count of sms and link to documentation
	 *
	 * @param	int		$numberOfSms: maximal allowed number of sms
	 * @param	int		$smsInPeriod: how many sms user sent already in period
	 * @param	string		$signature: the message's signature (from FlexForm)
	 * @param	string		$formatLink2: page id
	 * @param	string		$formatLinkAddParams2: page additional parameters
	 * @param	bool		$hideCharactersCount: if true, returns empty string
	 * @return	string		html elements
	 */
	protected function formSmsCount($numberOfSms, $smsInPeriod, $signature, $formatLink2, $formatLinkAddParams2, $hideCharactersCount) {
		$retValue = '';
		if (!$hideCharactersCount) {
			$text =	$this->linkToDoc(htmlspecialchars($this->pi_getLL('form_sms')), $formatLink2, $formatLinkAddParams2);
			$number = '<span id="' . $this->prefixId . '_text_sms"';
			if ($numberOfSms - $smsInPeriod > 4) {
				$limit = 5;
			} else {
				$limit = $numberOfSms - $smsInPeriod;
			}
			if ($this->countSms($this->piVars['smstext'] . $signature) > $limit) {
				$number .= ' style="color:red;"';
			}
			$number .= '>' . $this->countSms($this->piVars['smstext'] . $signature) . '</span>';
			$retValue = '<label for="' . $this->prefixId . '_text">' .
				sprintf($this->pi_getLL('form_sms_template'), $text, $number) .
				'</label>';
		}
		return $retValue;
	}
	/**
	 * Makes a html element textarea (message text) for main form
	 *
	 * @param	int		$textAreaRows: Count of TextArea's rows (TS Setup)
	 * @param	int		$textAreaCols: Count of TextArea's columns (TS Setup)
	 * @param	string		$signature: The signature text, from FlexForm
	 * @return	string		The html text that explains user why the sms have the signature
	 */
	protected function formTextArea($textAreaRows, $textAreaCols, $signature) {
		$retValue = '<label for="' . $this->prefixId . '_text">' .
			htmlspecialchars($this->pi_getLL('form_message')) . '</label>' .
			'<br />' .
			'<textarea rows="' . $textAreaRows . '"'.
			' cols="' . $textAreaCols . '"' .
			' id="' . $this->prefixId . '_text"' .
			' name="' . $this->prefixId . '[smstext]"' .
			' onkeyup="smsLength(' . $this->count($signature) . ');">' .
			$this->piVars['smstext'] .
			'</textarea>';
		return $retValue;
	}
	/**
	 * Makes a html element with CAPTCHA for main form
	 *
	 * @param	array		$markerArray: array with captcha from sr_freecap
	 * @return	string		The html text that has a captcha image and input field etc
	 */
	protected function formCaptcha($markerArray) {
		$retValue = '<div class="' . $this->prefixClass . '-captcha">' .
			'<label for="' . $this->prefixId . '_captcha_response" id="' . $this->prefixId . '_captcha_label">' .
			htmlspecialchars($this->pi_getLL('form_captcha')) . '</label><br />' .
			$markerArray['###SR_FREECAP_IMAGE###'] . '<br />' .
			$markerArray['###SR_FREECAP_CANT_READ###'] . '<br />' .
			$markerArray['###SR_FREECAP_ACCESSIBLE###'] .
			$markerArray['###CAPTCHA_INSERT###'] .
			$markerArray['###SR_FREECAP_ACCESSIBLE###'] .
			'<input type="text" size="15" id="' . $this->prefixId . '_captcha_response"' .
			' name="' . $this->prefixId . '[captcha_response]"' .
			' title="' . $markerArray['###SR_FREECAP_NOTICE###'] . '" value="">' .
			'</div>';
		return $retValue;
	}
	/**
	 * Makes a html element with explanation of signature for main form
	 *
	 * @param	string		$signature: The signature text, from FlexForm
	 * @param	string		$websiteName: The name of curren Website, from FlexForm
	 * @param	int		$signatureWrap: Html tag that wraps the explanation pf signature
	 * @param	bool		$hideSignatureExplanation: Flag to hide this element
	 * @return	string		The html text that explains user why the sms have the signature
	 */
	protected function formSignature($signature, $websiteName, $signatureWrap, $hideSignatureExplanation) {
		$retValue = '';
		if (!$hideSignatureExplanation) {
			if (strlen($signature) > 0) {
				$retValue = '<' . $signatureWrap . '>' .
					htmlspecialchars(sprintf($this->pi_getLL('form_signature'),
					$this->pi_getLL('form_characters_footnote_sign'), $websiteName)) .
					'</' . $signatureWrap . '><br />';
			}
		}
		return $retValue;
	}
	/**
	 * Makes a html input element "phone number" for main form
	 *
	 * @param	int		$phoneInputMin: The PlugIn content
	 * @param	string		$signature: The signature, from FlexForm
	 * @param	bool		$phoneInputToggle: Allow user to resize the input element
	 * @param	string		$phoneInputToggleText: Text of link, that resizes the input element, default: "..."
	 * @param	int		$numberOfRecipients: Maximal allowed number of recipients
	 * @param	bool		$hideRestrictions: Flag to hide this element
	 * @return	string		The html text with label and input for phone numbers
	 */
	protected function formPhoneInput($phoneInputMin, $signature, $phoneInputToggle, $phoneInputToggleText,
	$numberOfRecipients, $hideRestrictions) {
		$retValue = '<label for="' . $this->prefixId . '_phone">';
		if ($hideRestrictions) {
			$retValue .= htmlspecialchars($this->pi_getLL('form_phone'));
		} else {
			$retValue .= htmlspecialchars(sprintf($this->pi_getLL('form_phone_template'),
				$this->pi_getLL('form_phone'), $numberOfRecipients));
		}
		$retValue .= '</label>' .
			'<br />' .
			'<input size="';
		if (htmlspecialchars($this->piVars['inputsize'])) {
			$retValue .= htmlspecialchars($this->piVars['inputsize']);
		} else {
			$retValue .= $phoneInputMin;
		}
		$retValue .= '" type="text" id="' . $this->prefixId . '_phone" name="' .
			$this->prefixId . '[recipient]"' .
			' value="' . htmlspecialchars($this->piVars['recipient']) . '"' .
			' onchange="smsLength(' . $this->count($signature) .');"' .
			' onkeypress="smsLength(' . $this->count($signature) . ');" />';
		if ($phoneInputToggle == TRUE) {
			$retValue .= '&nbsp;<a href="#" onclick="this.blur();morePhones();return false;">' .
				$phoneInputToggleText . '</a>';
			}
		$retValue .= '<input type="hidden" id="' . $this->prefixId . '_inputsize"' .
			' name="' . $this->prefixId . '[inputsize]"' .
			' value="';
		if (isset($this->piVars['inputsize'])) {
			$retValue .= htmlspecialchars($this->piVars['inputsize']);
		} else {
			$retValue .= $phoneInputMin;
		}
		$retValue .= '">';
		return $retValue;
	}
	/**
	 * The main method of the PlugIn
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	The		content that is displayed on the website
	 */
	function main($content, $conf) {
		$this->conf = $conf;
		$this->pi_setPiVarDefaults();
		// Load language data from locallang.xml
		$this->pi_loadLL();
		// Disable caching for this extension only
		$this->pi_USER_INT_obj = 1;
		// Load data from FlexFrom
		$this->pi_initPIflexform();
		/*
		** Einstellungen aus TS Setup (TypoScript Object Browser)
		*/
		// Default values
		$textAreaRows = '10';
		$textAreaCols = '53';
		$phoneInputMin = '20';
		$phoneInputMax = '48';
		$phoneInputToggle = TRUE;
		$phoneInputToggleText = '...';
		$signatureWrap = 'small';
		// Loading values from TypoScript Setup
		if (!is_null($this->conf['textarea_rows'])) {
			$textAreaRows = $this->conf['textarea_rows'];
		}
		if (!is_null($this->conf['textarea_cols'])) {
			$textAreaCols = $this->conf['textarea_cols'];
		}
		if (!is_null($this->conf['phone_input_min'])) {
			$phoneInputMin = $this->conf['phone_input_min'];
		}
		if (!is_null($this->conf['phone_input_max'])) {
			$phoneInputMax = $this->conf['phone_input_max'];
		}
		if (!is_null($this->conf['phone_input_toggle'])) {
			if ($this->conf['phone_input_toggle'] == '0') {
				$phoneInputToggle = FALSE;
			}
		}
		if (!is_null($this->conf['phone_input_toggle_text'])) {
				$phoneInputToggleText = $this->conf['phone_input_toggle_text'];
		}
		if (!is_null($this->conf['signature_wrap'])) {
				$signatureWrap = $this->conf['signature_wrap'];
		}
		/*
		** Frontend User ID
		*/
		$feUserId = 0;
		$anonymous = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'SendWithoutSignUp', 'sheet2');
		if (!$anonymous) {
			$feUserId = $GLOBALS['TSFE']->fe_user->user['uid'];
		}
		if (($feUserId == 0) && !$anonymous) {
			return $this->pi_wrapInBaseClass($this->getDisabledForm($textAreaRows, $textAreaCols, TRUE));
		}
		/*
		** Error codes
		*/
		$ok = FALSE;
		$err = FALSE;
		$errEmptyPhone = FALSE;
		$errWrongPhone = FALSE;
		$errEmptyMessage = FALSE;
		$errCaptcha = FALSE;
		$errLongMessage = FALSE;
		$errLoginPassword = FALSE;
		$errTooManyRecipients = FALSE;
		$errNumbersText = '';
		/*
		** Gateway error flag & message
		*/
		$errTelekom = FALSE;
		$errTelekomMessage = '';
		/*
		** Initialization values
		*/
		$smsMaxLength = 765;
		$environment = 'production';
		$status = '';
		$countOfSmsText = '';
		$limitText = '';
		$countOfNumbers = 1;
		$currentPeriodStart = new DateTime();
		$currentPeriodEnd = new DateTime();
		$now = getdate();
		$limits = NULL;
		$smsInPeriod = 0;
		$periodStart = NULL;
		$periodEnd = NULL;
		/*
		** Page's language
		*/
		$langId = $GLOBALS['TSFE']->config['config']['sys_language_uid'];
		$langDefinition = ($langId != 0) ? 'l' . strtoupper($this->LLkey) : 'lDEF';
		/*
		** Einstellungen der Extension (in Flexforms)
		*/
		// Seite 1
		$username = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'DCLogin', 'sheet0');
		$password = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'DCPassword', 'sheet0');
		$proxy = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'Proxy', 'sheet0');
		$env = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'Environment', 'sheet0');
		// Seite 1
		$originator = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'SMSsender', 'sheet1');
		$signature = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'SMSsignature', 'sheet1');
		$websiteName = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'WebSiteName', 'sheet1');
		$feedbackMode = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'FeedbackMode', 'sheet1');
		if ($feedbackMode == '1') {
			$feedbackMode = TRUE;
		} else {
			$feedbackMode = FALSE;
		}
		$feedbackNumber = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'FeedbackNumber', 'sheet1');
		if (strlen($feedbackNumber) == 0) {
			$feedbackNumber = NULL;
		}
		// Seite 2
		$timeInterval = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'TimeInterval', 'sheet2');
		$numberOfSms = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'NumberOfSMS', 'sheet2');
		$numberOfRecipients = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'NumberOfPhones', 'sheet2');
		$flashSms = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'FlashSMS', 'sheet2');
		if ($flashSms == '1') {
			$flashSms = TRUE;
		} else {
			$flashSms = FALSE;
		}
		$showCaptcha = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'CAPTCHA', 'sheet2');
		if ($showCaptcha == '1') {
			$showCaptcha = TRUE;
		} else {
			$showCaptcha = FALSE;
		}
		// Seite 3
		$formatLink = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'LinkPhoneFormatPageID', 'sheet3');
		$formatLinkAddParams = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'LinkPhoneFormatPageAddParams', 'sheet3');
		$formatLink2 = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'LinkRestrictionsPageID', 'sheet3');
		$formatLinkAddParams2 = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'LinkRestrictionsPageAddParams', 'sheet3');
		$formatLink3 = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'LinkFlashSMSPageID', 'sheet3');
		$formatLinkAddParams3 = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'LinkFlashSMSPageAddParams', 'sheet3');
		$formatLink4 = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'LinkEnvironmentPageID', 'sheet3');
		$formatLinkAddParams4 = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'LinkEnvironmentPageAddParams', 'sheet3');
		// Seite 4
		$hideRestrictions = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'HideRestrictions', 'sheet4');
		$hideEnvironment = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'HideEnvironment', 'sheet4');
		$hideNumberFormat = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'HidePhoneNumberFormat', 'sheet4');
		$hideCharactersCount = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'HideCharactersCount', 'sheet4');
		$hideSignatureExplanation = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'HideSignatureExplanation', 'sheet4');
		/*
		** CAPTCHA
		*/
		if ($showCaptcha) {
			if (t3lib_extMgm::isLoaded('sr_freecap')) {
				require_once(t3lib_extMgm::extPath('sr_freecap') . 'pi2/class.tx_srfreecap_pi2.php');
				$this->freeCap = t3lib_div::makeInstance('tx_srfreecap_pi2');
			} else {
				$showCaptcha = FALSE;
			}
			if (is_object($this->freeCap)) {
				$markerArray = $this->freeCap->makeCaptcha();
			}
		}
		/*
		** Envoirment
		*/
		switch ($env) {
			case 0:
				$environment = 'production';
			break;
			case 1:
				$environment = 'sandbox';
				$smsMaxLength = 160;
				$signature = ' SMS API by developergarden.com';
				$websiteName = 'Developer Garden';
			break;
			case 2:
				$environment = 'mock';
			break;
		}
		/*
		** Time Interval
		*/
		if ($anonymous) {
			$ok = TRUE;
			$smsInPeriod = 0;
			/*
			** Adding JavaScript to page
			*/
			if ($env == 1) {
				$this->addJs($phoneInputMin, $phoneInputMax, $phoneInputToggle, $numberOfRecipients, 1, $hideCharactersCount, $feedbackNumber);
			} else {
				if ($numberOfSms > 5) {
					$this->addJs($phoneInputMin, $phoneInputMax, $phoneInputToggle, $numberOfRecipients, 5, $hideCharactersCount, $feedbackNumber);
				} else {
					$this->addJs($phoneInputMin, $phoneInputMax, $phoneInputToggle, $numberOfRecipients, $numberOfSms, $hideCharactersCount, $feedbackNumber);
				}
			}
		} else {
			switch ($timeInterval) {
				case 0:
					// 10 minutes
					$currentPeriodStart->setTime($now['hours'], floor($now['minutes'] / 10) * 10, 0);
					$currentPeriodEnd->setTime($now['hours'], floor($now['minutes'] / 10) * 10 + 9, 59);
					$limitText = htmlspecialchars($this->pi_getLL('limit_10_min'));
					break;
				case 1:
					// 30 minutes
					if ($now['minute'] < 30) {
						$currentPeriodStart->setTime($now['hours'], 0, 0);
						$currentPeriodEnd->setTime($now['hours'], 29, 59);
					} else {
						$currentPeriodStart->setTime($now['hours'], 30, 0);
						$currentPeriodEnd->setTime($now['hours'], 59, 59);
					}
					$limitText = htmlspecialchars($this->pi_getLL('limit_30_min'));
					break;
				case 2:
					// 1 hour
					$currentPeriodStart->setTime($now['hours'], 0, 0);
					$currentPeriodEnd->setTime($now['hours'], 59, 59);
					$limitText = htmlspecialchars($this->pi_getLL('limit_60_min'));
					break;
				case 3:
					// 1 day
					$currentPeriodStart->setTime(0, 0, 0);
					$currentPeriodEnd->setTime(23, 59, 59);
					$limitText = htmlspecialchars($this->pi_getLL('limit_86400_min'));
					break;
			}
			// Limits from DB
			$limits = $this->dbGetCountOfSms($feUserId);
			// New row in DB
			if (!is_null($limits)) {
				$periodStart = new DateTime($limits['period_start']);
				$periodEnd = new DateTime($limits['period_end']);
				$smsInPeriod = $limits['sms_sent_in_period'];
			} else {
				$this->dbAddUserInTable($feUserId, $currentPeriodStart->format(DateTime::ISO8601), $currentPeriodEnd->format(DateTime::ISO8601));
				$periodStart = clone $currentPeriodStart;
				$periodEnd = clone $currentPeriodEnd;
				$limits = array (
					'sms_sent' => 0
				);
			}
			// Hat der Benutzer noch freie SMS im Zeitraum?
			// Neuer Zeitraum
			if ($periodEnd->getTimestamp() <= $currentPeriodStart->getTimestamp()) {
				$ok = TRUE;
				$smsInPeriod = 0;
			}
			// Alter Zeitraum
			if ($periodStart->getTimestamp() >= $currentPeriodStart->getTimestamp() &&
				$periodEnd->getTimestamp() <= $currentPeriodEnd->getTimestamp()) {
				if ($smsInPeriod < $numberOfSms) {
					$ok = TRUE;
				}
			} else {
				if ($periodStart->getTimestamp()<= $currentPeriodStart->getTimestamp() &&
				$periodEnd->getTimestamp() >= $currentPeriodEnd->getTimestamp()) {
					// Alter Zeitraum
					$ok = TRUE;
					$smsInPeriod = 0;
				}
			}
			/*
			** Adding JavaScript to page
			*/
			if ($env == 1) {
				$this->addJs($phoneInputMin, $phoneInputMax, $phoneInputToggle, $numberOfSms - $smsInPeriod, 1, $hideCharactersCount, $feedbackNumber);
			} else {
				if ($numberOfSms - $smsInPeriod > 4) {
					$this->addJs($phoneInputMin, $phoneInputMax, $phoneInputToggle, $numberOfSms - $smsInPeriod, 5, $hideCharactersCount, $feedbackNumber);
				} else {
					$this->addJs($phoneInputMin, $phoneInputMax, $phoneInputToggle,
						$numberOfSms - $smsInPeriod, $numberOfSms - $smsInPeriod, $hideCharactersCount, $feedbackNumber);
				}
			}
		}
		/*
		** Adding CSS to page
		*/
		$this->addCss();
		/*
		** Test and send
		*/
		if ($ok) {
			if (isset($this->piVars['submit_button'])) {
				// TEST
				if ($showCaptcha) {
					// Ist CAPTCHA ok?
					if (is_object($this->freeCap) &&
					!$this->freeCap->checkWord($this->piVars['captcha_response'])) {
						$err = TRUE;
						$errCaptcha = TRUE;
					}
				}
				// Ist Feedback-Funktion angeschaltet?
				if ($feedbackMode) {
					//Nehmen die Feedback-Rufnummer
					$recipients = $feedbackNumber;
				} else {
					// Hat der Benutzer die Handynummer eingetragen?
					if (!$this->piVars['recipient']) {
						$err = TRUE;
						$errEmptyPhone = TRUE;
					} else {
						$recipients = $this->piVars['recipient'];
						// Sind die eingetragenen Handynummern korrekt?
						$codes = t3lib_div::makeInstance('tx_sendsms_callingcodes');
						$arrNumbers = $this->validateAllNumbers($recipients, $codes);
						for ($i = 0; $i < count($arrNumbers); $i++) {
							if ($arrNumbers[$i][0] == FALSE) {
								$err = TRUE;
								$errWrongPhone = TRUE;
								$errNumbersText .= htmlspecialchars(sprintf($this->pi_getLL('error_numberformat'), $arrNumbers[$i][2])) . '<br />';
							}
						}
						$countOfNumbers = count($arrNumbers);
						if ($countOfNumbers > $numberOfRecipients) {
							$err = TRUE;
							$errTooManyRecipients = TRUE;
						}
					}
				}
				// Hat der Benutzer den Text eingegeben?
				if (!$this->piVars['smstext']) {
					$err = TRUE;
					$errEmptyMessage = TRUE;
				} else {
					$message = $this->piVars['smstext'];
				}
				// Ist der Text zu lang?
				if ($this->count($message . $signature) > $smsMaxLength) {
					$err = TRUE;
					$errLongMessage = TRUE;
				} else {
					if ($env != 1) {
						$message .= $signature;
					}
				}
				// Hat der Benutzer genug SMS?
				if (($this->countSms($message) * $countOfNumbers) > ($numberOfSms - $smsInPeriod)) {
					$err = TRUE;
					$errLongMessage = TRUE;
				}
				// Kann Telecom client gebaut werden?
				try {
					// Constructs the Telekom client using the user name and password.
					$client = new SendSmsClient($environment, $username, $password);
					//Proxy
					if ($proxy) {
						$client->use_additional_curl_options(array(CURLOPT_PROXY => $proxy));
					}
				} catch(Exception $e) {
					$err = TRUE;
					$errLoginPassword = TRUE;
				}
				// SEND
				if (!$err) {
					// Should the SMS be sent as flash SMS
					if ($this->piVars['flash_sms'] && $flashSms == TRUE) {
						$flash = "true";
					} else {
						$flash = "false";
					}
					// The result of sending an SMS
					$sendSmsResponse = NULL;
					try {
						// Sends the SMS
						$sendSmsResponse = $client->sendSms($recipients, $message, $originator, $flash, NULL);
						// Test, if the invocation of sendSms() was successful.
						if (!($sendSmsResponse->getStatus()->getStatusConstant() == SendSmsStatusConstants::SUCCESS)) {
							// if ($langDefinition == 'lDEF') {
								// $errTelekomMessage = $sendSmsResponse->getStatus()->getStatusDescriptionEnglish();
							// } elseif ($langDefinition == 'lDE') {
								// $errTelekomMessage = $sendSmsResponse->getStatus()->getStatusDescriptionGerman();
							// } else {
							$errTelekomMessage = htmlspecialchars($this->pi_getLL('error_telekom_' .
								trim($sendSmsResponse->getStatus()->getStatusCode())));
							// }
							throw new Exception();
						}
					} catch(Exception $e) {
						$err = TRUE;
						$errTelekom = TRUE;
						if (is_null($sendSmsResponse) && strlen($errTelekomMessage) == 0) {
							if ($e->getMessage() == "couldn't connect to host") {
								$errTelekomMessage = htmlspecialchars($this->pi_getLL('error_telekom_connect'));
							} else {
								$errTelekomMessage = $e->getMessage();
							}
						}
					}
				}
				// SMS WAS SEND
				if (!$err) {
					// Wir schreiben in Registry die gesendete SMS
					$registry = t3lib_div::makeInstance('t3lib_Registry');
					$count = $registry->get('tx_' . $this->extKey, 'sms', 0);
					$count += $this->countSms($message);
					$registry->set('tx_' . $this->extKey, 'sms', $count);
					// Wir sagen dem Benutzer, dass alles in Ordnung ist
					if (($this->countSms($message) * $countOfNumbers) > 1) {
						$status .= htmlspecialchars($this->pi_getLL('result_ok_pl'));
					} else {
						$status .= htmlspecialchars($this->pi_getLL('result_ok'));
					}
					// Schreiben in die Tabelle "Statistik" Information über die gesendete SMS
					if (!$anonymous) {
						$smsInPeriod += $this->countSms($message) * $countOfNumbers;
						$this->dbAddSmsInTable($feUserId, $limits['sms_sent'] + $this->countSms($message) * $countOfNumbers,
							$smsInPeriod, $currentPeriodStart->format(DateTime::ISO8601), $currentPeriodEnd->format(DateTime::ISO8601));
					}
					for ($i = 0; $i < $countOfNumbers; $i++) {
						$this->dbAddInStatistics($now, $this->count($message));
					}
				}
			}
		}
		// Darf der Benutzer noch SMS schicken?
		if ((($numberOfSms - $smsInPeriod) <= 0) && !$anonymous) {
			$currentPeriodEnd->add(new DateInterval('PT1S'));
			$countOfSmsText = htmlspecialchars(sprintf($this->pi_getLL('not_enough_sms'), $currentPeriodEnd->format('H:i d.m.y')));
			$content = $this->getDisabledForm($textAreaRows, $textAreaCols, !$anonymous);
		} else {
			// MAIN FORM
			if (!$anonymous) {
				$countOfSmsText = htmlspecialchars(sprintf($this->pi_getLL('form_youhave'), $numberOfSms - $smsInPeriod, $numberOfSms, $limitText));
			} else {
				$countOfSmsText = '';
			}
			$content = '<div id="' . $this->prefixId . '_body">' .
				'<form action="' . $this->pi_getPageLink($GLOBALS['TSFE']->id) . '" method="POST">';
				if (!$feedbackMode) {
					$content .= '<div class="' . $this->prefixClass . '-phone">' .
					$this->formPhoneInput($phoneInputMin, $signature, $phoneInputToggle, $phoneInputToggleText,
						$numberOfRecipients, $hideRestrictions) .
					'<br />' .
					$this->formPhoneNumbersFormat($hideNumberFormat, $formatLink, $formatLinkAddParams) .
					'</div>';
				}
			$content .= '<div class="' . $this->prefixClass . '-message">' .
				$this->formTextArea($textAreaRows, $textAreaCols, $signature) .
				'<br />';
			if (!$hideCharactersCount) {
				$content .= $this->formCharactersCount($numberOfSms, $smsInPeriod, $smsMaxLength,
					$formatLink2, $formatLinkAddParams2, $signature, $hideSignatureExplanation, $hideCharactersCount) .
					'&nbsp;' .
					$this->formSmsCount($numberOfSms, $smsInPeriod, $signature, $formatLink2, $formatLinkAddParams2, $hideCharactersCount);
			}
			$content .= '</div>' . $this->formSignature($signature, $websiteName, $signatureWrap, $hideSignatureExplanation);
			if ($flashSms) {
				$content .= $this->formFlashSms($flashSms, $formatLink3, $formatLinkAddParams3);
			}
			if ($showCaptcha) {
				$content .= $this->formCaptcha($markerArray);
			}
			$content .=	'<div class="' . $this->prefixClass . '-submit"><input type="submit" id="' . $this->prefixId . '_submit" name="' . $this->prefixId . '[submit_button]"' .
				' value="' . htmlspecialchars($this->pi_getLL('form_submit')) . '" />' .
				'</div></form></div>';
		}
		if (!isset($this->piVars['submit_button'])) {
			// FORM
			$header = '<div id="' . $this->prefixId . '_header">';
			if (!$hideRestrictions) {
				if (!$anonymous) {
					$header .= '<span>' . $countOfSmsText . '</span><br />';
				}
			}
			if ($ok == TRUE) {
				$header .= $this->formEnvironment($hideEnvironment, $formatLink4, $formatLinkAddParams4, $environment);
			}
			$header .= '</div>';
			$content = $header . $content;
		} else {
			if ($err) {
				// ERROR
				$status .= $this->errorText($errEmptyPhone, 'error_phone');
				if ($errWrongPhone) {
					$status .= '<span style="color:red;">' . $errNumbersText . '</span>';
				}
				$status .= $this->errorText($errTooManyRecipients, 'error_toomanyrecipietns');
				$status .= $this->errorText($errEmptyMessage, 'error_message');
				$status .= $this->errorText($errLongMessage, 'error_longmessage');
				$status .= $this->errorText($errCaptcha, 'error_captcha');
				$status .= $this->errorText($errLoginPassword, 'error_loginpassword');
				if ($errTelekom) {
					$status .= '<span style="color:red;">' . $errTelekomMessage . '</span>';
				}
				$content = '<div id="' . $this->prefixId . '_answer">' . $status . '</div>' . $content;
				$content = '<div id="' . $this->prefixId . '_header"><span>' . $countOfSmsText . '</span>' .
					$this->formEnvironment($hideEnvironment, $formatLink4, $formatLinkAddParams4, $environment) .
					'</div>' . $content;
			} else {
				// SMS WAS SEND
				$content = '<div id="' . $this->prefixId . '_answer">' . $status . '</div>';
				$content .= '<div id="' . $this->prefixId . '_body">';
				if (!$feedbackMode) {
					$content .= '<strong>' . htmlspecialchars($this->pi_getLL('form_phone')) . '</strong>&nbsp;' .
						'<span>' . htmlspecialchars($this->piVars['recipient']) . '</span><br /><br />';
				}
				$content .= '<strong>' . htmlspecialchars($this->pi_getLL('form_message')) . '</strong><br />' .
					'<span>' . htmlspecialchars($this->piVars['smstext']) . '</span><br /><br />';
				if (!$feedbackMode) {
					$content .= '<span>';
					if ($this->countSms($message) * $countOfNumbers > 1) {
						$content .= htmlspecialchars(sprintf($this->pi_getLL('price_sms_pl'), $this->priceOfSms($arrNumbers)));
					} else {
						$content .= htmlspecialchars(sprintf($this->pi_getLL('price_sms'), $this->priceOfSms($arrNumbers)));
					}
					$content .= '</span><br /><br />';
				}
				$content .= '<span>' .
					sprintf(htmlspecialchars($this->pi_getLL('next_sms')), $this->linkToDoc($this->pi_getLL('click_here'),
						$this->pi_getPageLink($GLOBALS['TSFE']->id), '')) .
					'</span>' .
					'</div>';
			}
		}
		return $this->pi_wrapInBaseClass($content);
	}
}
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sendsms/pi1/class.tx_sendsms_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sendsms/pi1/class.tx_sendsms_pi1.php']);
}
?>