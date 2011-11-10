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
 *   88: class tx_sendsms_pi1 extends tslib_pibase
 *  106:     public function main($content, $conf)
 *  221:     private function init($conf)
 *  240:     private function getFlexFormValues($flexFormSheets)
 *  255:     private function setBaseSettings()
 *  278:     private function createMarkerArray()
 *  324:     private function getOkMessage()
 *  337:     private function insertPostVariables()
 *  349:     private function insertJsValues()
 *  369:     private function insertResultForm()
 *  384:     private function insertCaptcha()
 *  409:     private function insertIntoSubpart($subpartName, $labelName, $text, $array = NULL)
 *  428:     private function insertStartForm()
 *  566:     private function testInputValues()
 *  635:     private function createTelekomClient()
 *  656:     private function sendSms($feUserId, $client)
 *  706:     private function updateStatistics($feUserId)
 *  732:     private function testEnoughSms($feUserId)
 *  819:     private function testCharactersCount()
 *  839:     private function dbGetCountOfSms($feUserId)
 *  861:     private function dbAddUserInTable($feUserId, $periodStart, $periodEnd)
 *  885:     private function dbAddSmsInTable($feUserId, $smsSent, $smsSentInPeriod, $periodStart, $periodEnd)
 *  906:     private function dbAddInStatistics($now, $length)
 *  926:     private function countChars($text)
 *  941:     private function countSms($text)
 *  956:     private function validateNumber($text)
 *  981:     private function validateAllNumbers($text, $codes = NULL)
 * 1013:     private function countPriceOfSms($arr)
 * 1047:     private function linkToDoc($text, $pageId, $pageAddParams)
 * 1067:     private function createErrorMessage($index, $value = NULL)
 * 1096:     private function addCssAndJs()
 * 1112:     private function setEnvironment()
 *
 * TOTAL FUNCTIONS: 31
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

// Parent class
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
	public $prefixId = 'tx_sendsms_pi1';						// Same as class name
	public $scriptRelPath = 'pi1/class.tx_sendsms_pi1.php';	// Path to this script relative to the extension dir.
	public $extKey = 'sendsms';								// The extension key.

	private $freeCap = NULL; 	// CAPTCHA
	private $templateHtml = '';	// Template
	private $bs = NULL;			// Base settings
	private $ff = NULL;			// Array with FlexForm values
	private $markerArray;		// Main marker array

	/**
	 * The main method of the PlugIn
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	string		The	content that is displayed on the website
	 */
	public function main($content, $conf) {
		// Initialize the Plugin
		$this->init($conf);

		// Add links to CSS ad JS into page
		$this->addCssAndJs();

		// Load values from FlexForm
		$this->getFlexFormValues($this->cObj->data['pi_flexform']['data']);

		// Set base settigns
		$this->setBaseSettings();

		// Set current environment
		$this->setEnvironment();

		// Get the template from file
		$this->templateHtml = $this->cObj->fileResource($conf['templateFile']);

		// Extract subparts from the template
		$subpart = $this->cObj->getSubpart($this->templateHtml, '###TEMPLATE###');

		// Initialize marker array
		$this->markerArray = $this->createMarkerArray();

		// Front end user id
		$feUserId = 0;
		if (!$this->ff['SendWithoutSignUp']) {
			$feUserId = $GLOBALS['TSFE']->fe_user->user['uid'];
		}
			if (($feUserId == 0) && !$this->ff['SendWithoutSignUp']) {
			$this->markerArray['###SPN_DISABLED###'] = htmlspecialchars($this->pi_getLL('form_login'));
			$this->markerArray['###LBL_RECIPIENTS###'] = htmlspecialchars($this->pi_getLL('form_phone'));
			$subpart = $this->cObj->getSubpart($this->templateHtml, '###DISABLED_FORM###');
			$content = $this->cObj->substituteMarkerArray($subpart, $this->markerArray);
			return $this->pi_wrapInBaseClass($content);
		}

		// Test and send
		if ($this->testEnoughSms($feUserId) == FALSE) {
			// No sms left
			$this->bs['currentPeriodEnd']->add(new DateInterval('PT1S'));
			$this->markerArray['###SPN_DISABLED###'] = htmlspecialchars(sprintf($this->pi_getLL('not_enough_sms'), $this->bs['currentPeriodEnd']->format('H:i d.m.y')));
			$subpart = $this->cObj->getSubpart($this->templateHtml, '###DISABLED_FORM###');
			$content = $this->cObj->substituteMarkerArray($subpart, $this->markerArray);
			return $this->pi_wrapInBaseClass($content);
		} else {
			// User has free sms
			$this->insertJsValues();
			$this->insertStartForm();
			if (!isset($this->piVars['submit_button'])) {
				// START PAGE - NEW SMS
				$GLOBALS["TSFE"]->fe_user->setKey('ses', $this->extKey . '_sms_sent', FALSE);
				$GLOBALS["TSFE"]->fe_user->storeSessionData();
			} else {
				// SMS WAS SEND or THERE ARE ERRORS
				// POST variables
				$this->insertPostVariables();
				// errors
				$answer = $this->testInputValues();
				if ($answer['Error'] == TRUE) {
					$this->markerArray['###INCLUDE_ERRORS###'] = $this->insertIntoSubpart(
						'###HEADER###',
						'###CONTENT_HEADER###',
						$answer['Message']
					);
				} else {
					if ($GLOBALS["TSFE"]->fe_user->getKey('ses', $this->extKey . '_sms_sent') == TRUE) {
						// SMS has been sent already
						$this->insertResultForm();
						$this->markerArray['###SPN_STATUS###'] = $this->getOkMessage();
						$subpart = $this->cObj->getSubpart($this->templateHtml, '###RESULT_FORM###');
					} else {
						$client = $this->createTelekomClient();
						if (is_null($client)) {
							$this->markerArray['###INCLUDE_ERRORS###'] = $this->insertIntoSubpart(
								'###HEADER###',
								'###CONTENT_HEADER###',
								$this->createErrorMessage('error_loginpassword')
							);
						} else {
							// Send SMS
							$response = $this->sendSms($feUserId, $client);
							if ($response['Error'] == TRUE) {
								// Error
								$this->markerArray['###INCLUDE_ERRORS###'] = $this->insertIntoSubpart(
									'###HEADER###',
									'###CONTENT_HEADER###',
									$response['Message']
								);
							} else {
								// Successfully
								$GLOBALS["TSFE"]->fe_user->setKey('ses', $this->extKey . '_sms_sent', TRUE);
								$GLOBALS["TSFE"]->fe_user->storeSessionData();
								$this->updateStatistics();
								$this->insertResultForm();
								$this->markerArray['###SPN_STATUS###'] = $this->getOkMessage();
								$subpart = $this->cObj->getSubpart($this->templateHtml, '###RESULT_FORM###');
							}
						}
					}
				}
			}
		}
		$content = $this->cObj->substituteMarkerArray($subpart, $this->markerArray);
		return $this->pi_wrapInBaseClass($content);
	}

	/**
	 * Initializes the plugin
	 *
	 * @param	array		$conf: The PlugIn configuration
	 * @return	void
	 */
	private function init($conf) {
		// Load configuration
		$this->conf = $conf;
		// Load POST variables
		$this->pi_setPiVarDefaults();
		// Load language data from locallang.xml
		$this->pi_loadLL();
		// Load data from FlexFrom
		$this->pi_initPIflexform();
		// Disable caching for this extension
		$this->pi_USER_INT_obj = 1;
	}

	/**
	 * Loads all flexform values to an array with keys
	 *
	 * @param	array		$flexFormSheets: The FlexForm array
	 * @return	void
	 */
	private function getFlexFormValues($flexFormSheets) {
		$this->ff = array();
		foreach ($flexFormSheets as $sheet) {
			foreach ($sheet['lDEF'] as $key => $value)
			{
				$this->ff[$key] = $value['vDEF'];
			}
		}
	}

	/**
	 * Sets initial settings to the base settigns array
	 *
	 * @return	void
	 */
	private function setBaseSettings() {
		$this->bs = array(
			'environment' => 'production',
			'smsMaxLength' => 765,
			'oneSmsLength' => 160,
			'manySmsLength' => 153,
			'currentPeriodStart' => new DateTime(),
			'currentPeriodEnd' => new DateTime(),
			'smsInPeriod' => 0,
			'sms_sent' => 0,
			'recipients' => '',
			'message' => '',
			'countOfNumbers' => 1,
			'PriceOfSms' => 0
		);
	}

	/**
	 * Creates initial marker array and fills it with default values
	 * and values from TS Setup
	 *
	 * @return	array		Marker Array
	 */
	private function createMarkerArray() {
		// Default values
		$array = Array(
			'###INCLUDE_ERRORS###' => '',
			'###INCLUDE_RESTRICTIONS###' => '',
			'###INCLUDE_ENVIRONMENT###' => '',
			'###LBL_RECIPIENTS###' => '',
			'###LBL_MESSAGE###' => htmlspecialchars($this->pi_getLL('form_message')),
			'###VAL_SUBMIT###' => htmlspecialchars($this->pi_getLL('form_submit')),
			'###SIGNATURELENGTH###' => strlen($this->ff['SMSsignature']),
			'###MESSAGEROWS###' => '10',
			'###MESSAGECOLS###' => '53',
			'###ISMIN###' => '20',
			'###ISMAX###' => '63'
		);

		// Maximal number of recipients
		if ($this->ff['HideRestrictions']) {
			$array['###LBL_RECIPIENTS###'] = htmlspecialchars($this->pi_getLL('form_phone'));
		} else {
			$array['###LBL_RECIPIENTS###'] = htmlspecialchars(sprintf($this->pi_getLL('form_phone_template'),
				$this->pi_getLL('form_phone'), $this->ff['NumberOfPhones']));
		}

		// TypoScript Setup values
		if (!is_null($this->conf['textarea_rows'])) {
			$array['###MESSAGEROWS###'] = $this->conf['textarea_rows'];
		}
		if (!is_null($this->conf['textarea_cols'])) {
			$array['###MESSAGECOLS###'] = $this->conf['textarea_cols'];
		}
		if (!is_null($this->conf['phone_input_min'])) {
			$array['###ISMIN###'] = $this->conf['phone_input_min'];
		}
		if (!is_null($this->conf['phone_input_max'])) {
			$array['###ISMAX###'] = $this->conf['phone_input_max'];
		}

		return $array;
	}

	/**
	 * Returns "Your SMS was sent" for one or many SMS
	 *
	 * @return	string		Message from locallang.xml
	 */
	private function getOkMessage() {
		if (($this->countSms($this->bs['message']) * $this->bs['countOfNumbers']) > 1) {
			return htmlspecialchars($this->pi_getLL('result_ok_pl'));
		} else {
			return htmlspecialchars($this->pi_getLL('result_ok'));
		}
	}

	/**
	 * Writes POST variables into marker array
	 *
	 * @return	void
	 */
	private function insertPostVariables() {
		$this->markerArray['###VAL_MESSAGE###'] = htmlspecialchars($this->piVars['smstext']);
		$this->markerArray['###VAL_RECIPIENTS###'] = htmlspecialchars($this->piVars['recipient']);
		$this->markerArray['###RECIPIENTSSIZE###'] = htmlspecialchars($this->piVars['inputsize']);
	}

	/**
	 * Writes some values into marker array
	 * This values are used by JavaScript
	 *
	 * @return	void
	 */
	private function insertJsValues() {
		if ($this->ff['Environment'] == 1) {
			$this->markerArray['###SMSLEFT###'] = $this->ff['NumberOfSMS'] - $this->bs['smsInPeriod'];
			$this->markerArray['###MAXSMS###'] = 1;
		} else {
			if ($this->ff['NumberOfSMS'] - $this->bs['smsInPeriod'] > 4) {
				$this->markerArray['###SMSLEFT###'] = $this->ff['NumberOfSMS'] - $this->bs['smsInPeriod'];
				$this->markerArray['###MAXSMS###'] = 5;
			} else {
				$this->markerArray['###SMSLEFT###'] = $this->ff['NumberOfSMS'] - $this->bs['smsInPeriod'];
				$this->markerArray['###MAXSMS###'] = $this->ff['NumberOfSMS'] - $this->bs['smsInPeriod'];
			}
		}
	}

	/**
	 * Writes values for result form into marker array
	 *
	 * @return	void
	 */
	private function insertResultForm() {
		$subpart = $this->cObj->getSubpart($this->templateHtml, '###RESULT_FORM###');
		if ($this->countSms($this->bs['message']) * $this->bs['countOfNumbers'] > 1) {
			$this->markerArray['###SPN_PRICE###'] = htmlspecialchars(sprintf($this->pi_getLL('price_sms_pl'), $this->bs['PriceOfSms']));
		} else {
			$this->markerArray['###SPN_PRICE###'] = htmlspecialchars(sprintf($this->pi_getLL('price_sms'), $this->bs['PriceOfSms']));
		}
		$this->markerArray['###LNK_NEXT_SMS###'] = sprintf(htmlspecialchars($this->pi_getLL('next_sms')), $this->linkToDoc($this->pi_getLL('click_here'), $this->pi_getPageLink($GLOBALS['TSFE']->id), ''));
	}

	/**
	 * Loads CAPTCHA and writes in marker array
	 *
	 * @return	void
	 */
	private function insertCaptcha() {
		if (t3lib_extMgm::isLoaded('sr_freecap')) {
			require_once(t3lib_extMgm::extPath('sr_freecap') . 'pi2/class.tx_srfreecap_pi2.php');
			$this->freeCap = t3lib_div::makeInstance('tx_srfreecap_pi2');
		} else {
			$this->ff['CAPTCHA'] = FALSE;
		}
		if (is_object($this->freeCap)) {
			$array = $this->freeCap->makeCaptcha();
			$array['###CAPTCHALABEL###'] = htmlspecialchars($this->pi_getLL('form_captcha'));
			return $array;
		} else {
			return NULL;
		}
	}

	/**
	 * Inserts text or marker array into subpart
	 *
	 * @param	string		$subpartName: Subpart's name
	 * @param	string		$labelName: Name of label in subpart
	 * @param	string		$text: Text to insert
	 * @param	array		$array: Marker array to insert into subpart
	 * @return	string		Subpart with text
	 */
	private function insertIntoSubpart($subpartName, $labelName, $text, $array = NULL) {
		if (is_null($array)) {
			return $this->cObj->substituteMarkerArray(
				$this->cObj->getSubpart($this->templateHtml, $subpartName),
				Array($labelName => $text)
			);
		} else {
			return $this->cObj->substituteMarkerArray(
				$this->cObj->getSubpart($this->templateHtml, $subpartName),
				$array
			);
		}
	}

	/**
	 * Fills marker array for main form
	 *
	 * @return	void
	 */
	private function insertStartForm() {
		// Restrictions
		if ($this->ff['HideRestrictions'] || $this->ff['SendWithoutSignUp']) {
			$this->markerArray['###INCLUDE_RESTRICTIONS###'] = '';
		} else {
			$this->markerArray['###INCLUDE_RESTRICTIONS###'] = $this->insertIntoSubpart(
				'###HEADER###',
				'###CONTENT_HEADER###',
				$this->insertIntoSubpart(
					'###RESTRICTIONS###',
					'###SPN_RESTRICTIONS###',
					htmlspecialchars(sprintf($this->pi_getLL('form_youhave'),
						$this->ff['NumberOfSMS'] - $this->bs['smsInPeriod'],
						$this->ff['NumberOfSMS'],
						$this->bs['limitText'])
					)
				)
			);
		}

		// Form action
		$this->markerArray['###FORMACTION###'] = $this->pi_getPageLink($GLOBALS['TSFE']->id);

		// POST variables
		$this->markerArray['###VAL_MESSAGE###'] = '';
		$this->markerArray['###VAL_RECIPIENTS###'] = '';
		$this->markerArray['###RECIPIENTSSIZE###'] = $this->markerArray['###ISMIN###'];

		// Environment
		if ($this->ff['HideEnvironment']) {
			$this->markerArray['###INCLUDE_ENVIRONMENT###'] = '';
		} else {
			$this->markerArray['###INCLUDE_ENVIRONMENT###'] = $this->insertIntoSubpart(
				'###HEADER###',
				'###CONTENT_HEADER###',
				$this->insertIntoSubpart(
					'###ENVIRONMENT###',
					'###SPN_ENVIRONMENT###',
					sprintf($this->pi_getLL('environment_template'),
						$this->linkToDoc(htmlspecialchars($this->pi_getLL('environment')),
						$this->ff['LinkEnvironmentPageID'],
						$this->ff['LinkEnvironmentPageAddParams']),
						$this->bs['environment']
					)
				)
			);
		}

		// Phone numbers input size toggle
		$this->markerArray['###TOGGLERECIPIENTS###'] = $this->cObj->getSubpart($this->templateHtml, '###TOGGLE###');

		// Phone numbers format
		if ($this->ff['HidePhoneNumberFormat']) {
			$this->markerArray['###INCLUDE_PHONE_NUMBER_FORMAT###'] = '';
		} else {
			if (!$this->ff['LinkPhoneFormatPageID']) {
				$this->markerArray['###INCLUDE_PHONE_NUMBER_FORMAT###'] = $this->insertIntoSubpart(
					'###PHONE_NUMBER_FORMAT###',
					'###LBL_FORMAT###',
					htmlspecialchars($this->pi_getLL('form_format'))
				);
			} else {
				$this->markerArray['###INCLUDE_PHONE_NUMBER_FORMAT###'] = $this->insertIntoSubpart(
					'###PHONE_NUMBER_FORMAT###',
					'###LBL_FORMAT###',
					sprintf($this->pi_getLL('form_format_template'),
						htmlspecialchars($this->pi_getLL('form_format')),
						$this->linkToDoc(htmlspecialchars($this->pi_getLL('form_format_link')),
							$this->ff['LinkPhoneFormatPageID'],
							$this->ff['LinkPhoneFormatPageAddParams']
						)
					)
				);
			}
		}

		// Characters and SMS counter
		if ($this->ff['HideCharactersCount']) {
			$this->markerArray['###INCLUDE_CHARACTERS_COUNT###'] = '';
		} else {
			$array = Array(
				'###LBL_CHARS_COUNT###' => $this->linkToDoc(htmlspecialchars($this->pi_getLL('form_characters')), $this->ff['LinkRestrictionsPageID'], $this->ff['LinkRestrictionsPageAddParams']),
				'###SPN_CHARS_COUNT###' => $this->countChars($this->piVars['smstext'] . $this->ff['SMSsignature']),
				'###LBL_SMS_COUNT###' => $this->linkToDoc(htmlspecialchars($this->pi_getLL('form_sms')), $this->ff['LinkRestrictionsPageID'], $this->ff['LinkRestrictionsPageAddParams']),
				'###SPN_SMS_COUNT###' => $this->countSms($this->piVars['smstext'] . $this->ff['SMSsignature'])
			);
			if ($this->ff['HideSignatureExplanation']) {
				$array['###LBL_FOOTNOTE_SIGN###'] = '';
			} else {
				$array['###LBL_FOOTNOTE_SIGN###'] = htmlspecialchars($this->pi_getLL('form_characters_footnote_sign'));
			}
			if ($this->testCharactersCount()) {
				$this->markerArray['###INCLUDE_CHARACTERS_COUNT###'] = $this->insertIntoSubpart('###CHARACTERS_COUNT###', '', '', $array);
			} else {
				$this->markerArray['###INCLUDE_CHARACTERS_COUNT###'] = $this->insertIntoSubpart('###CHARACTERS_COUNT_RED###', '', '', $array);
			}
		}

		// Signature
		if ($this->ff['HideSignatureExplanation']) {
				$this->markerArray['###INCLUDE_SIGNATURE_EXPLANATION###'] = '';
			} else {
				$this->markerArray['###INCLUDE_SIGNATURE_EXPLANATION###'] = $this->insertIntoSubpart('###SIGNATURE_EXPLANATION###',
					'###LBL_SIGNATURE_EXPLANATION###',
					htmlspecialchars(sprintf($this->pi_getLL('form_signature'),
						$this->pi_getLL('form_characters_footnote_sign'),
						$this->ff['WebSiteName'])
					)
				);
		}

		// CAPTCHA
		if (!$this->ff['CAPTCHA']) {
			$this->markerArray['###INCLUDE_CAPTCHA###'] = '';
		} else {
			$this->markerArray['###INCLUDE_CAPTCHA###'] = $this->insertIntoSubpart(
				'###CAPTCHA###',
				'',
				'',
				$this->insertCaptcha()
			);
		}

		// Flash SMS
		if ($this->ff['FlashSMS']) {
			$this->markerArray['###FLASHSMSLABEL###'] = sprintf(htmlspecialchars($this->pi_getLL('form_flash')), $this->linkToDoc(htmlspecialchars($this->pi_getLL('form_flash_link')), $this->ff['LinkFlashSMSPageID'], $this->ff['LinkFlashSMSPageAddParams']));
			$flashSmsContent = $this->cObj->getSubpart($this->templateHtml, '###FLASH_SMS###');
			$this->markerArray['###INCLUDE_FLASH_SMS###'] = $this->cObj->substituteMarkerArray($flashSmsContent, $this->markerArray);
		} else {
			$this->markerArray['###INCLUDE_FLASH_SMS###'] = '';
		}
	}

	/**
	 * Tests all inputed values
	 *
	 * @return	array		Array with error flag and error message
	 */
	private function testInputValues() {
		// Result array to return
		$answer = Array(
			'Error' => FALSE,
			'Message' => ''
		);

		// CAPTCHA
		if ($this->ff['CAPTCHA']) {
			if (is_object($this->freeCap) && !$this->freeCap->checkWord($this->piVars['captcha_response'])) {
				$answer['Message'] .= $this->createErrorMessage('error_captcha');
			}
		}

		// Resipients
		if (!$this->piVars['recipient']) {
			// Input is empty
			$answer['Message'] .= $this->createErrorMessage('error_phone');
		} else {
			// Prüfen alle eingetragene Rufnummern
			$this->bs['recipients'] = $this->piVars['recipient'];
			// Sind die eingetragenen Rufnummern korrekt?
			$codes = t3lib_div::makeInstance('tx_sendsms_callingcodes');
			$arrNumbers = $this->validateAllNumbers($this->bs['recipients'], $codes);
			for ($i = 0; $i < count($arrNumbers); $i++) {
				if ($arrNumbers[$i][0] == FALSE) {
					$answer['Message'] .= $this->createErrorMessage('error_numberformat', $arrNumbers[$i][2]);
				}
			}
			$this->bs['countOfNumbers'] = count($arrNumbers);
			if ($this->bs['countOfNumbers'] > $this->ff['NumberOfPhones']) {
				$answer['Message'] .= $this->createErrorMessage('error_toomanyrecipietns');
			}
			$this->bs['PriceOfSms'] = $this->countPriceOfSms($arrNumbers);
		}

		// Message
		if (!$this->piVars['smstext']) {
			$answer['Message'] .= $this->createErrorMessage('error_message');
		} else {
			$this->bs['message'] = $this->piVars['smstext'];
		}

		// Is the message too big?
		if ($this->countChars($message . $this->ff['SMSsignature']) > $this->bs['smsMaxLength']) {
			$answer['Message'] .= $this->createErrorMessage('error_longmessage');
		} else {
			if ($this->ff['Environment'] != 1) {
				$this->bs['message'] .= $this->ff['SMSsignature'];
			}
		}

		// Has user enough sms?
		if (($this->countSms($this->bs['message']) * $this->bs['countOfNumbers']) > ($this->ff['NumberOfSMS'] - $this->bs['smsInPeriod'])) {
			$answer['Message'] .= $this->createErrorMessage('error_longmessage');
		}

		// Prepare result array and return
		if (strlen($answer['Message']) > 0) {
			$answer['Error'] = TRUE;
		}
		return $answer;
	}

	/**
	 * Creates Telekom Client
	 *
	 * @return	object		Telekom client or NULL
	 */
	private function createTelekomClient() {
		// Can be created the Telekom client?
		$client = NULL;
		try {
			// Constructs the Telekom client using Telekom user name and password.
			$client = new SendSmsClient($this->bs['environment'], $this->ff['DCLogin'], $this->ff['DCPassword']);
			// Adds proxy
			if ($this->ff['Proxy']) {
				$client->use_additional_curl_options(array(CURLOPT_PROXY => $this->ff['Proxy']));
			}
		} catch(Exception $e) {}
		return $client;
	}

	/**
	 * Sends SMS using Telekom API
	 *
	 * @param	int		$feUserId: FrontEnd User ID
	 * @param	object		$client: Telekom client
	 * @return	array		Array with error flag and error message
	 */
	private function sendSms($feUserId, $client) {
		// Array to return
		$answer = Array(
			'Error' => FALSE,
			'Message' => ''
		);

		// Should be SMS send as flash SMS
		if ($this->piVars['flash_sms'] && $this->ff['FlashSMS']) {
			$flash = "true";
		} else {
			$flash = "false";
		}

		// The result of sending an SMS
		$sendSmsResponse = NULL;
		try {
			// Sends the SMS
			$sendSmsResponse = $client->sendSms($this->bs['recipients'], $this->bs['message'], $this->ff['SMSsender'], $flash, NULL);
			// Test, if the invocation of sendSms() was successful.
			if (!($sendSmsResponse->getStatus()->getStatusConstant() == SendSmsStatusConstants::SUCCESS)) {
				$errorMessage = $this->createErrorMessage('error_telekom_' . trim($sendSmsResponse->getStatus()->getStatusCode()));
				throw new Exception($errorMessage, $sendSmsResponse->getStatus()->getStatusCode());
			}
		} catch(Exception $e) {
			$answer['Error'] = TRUE;
			if ($e->getCode() == 0) {
				$answer['Message'] = $this->createErrorMessage('error_telekom_connect');
			} else {
				if (strlen($e->getMessage()) > 0) {
					$answer['Message'] .= $e->getMessage();
				} else {
					if ($e->getCode() == 30) {
						$answer['Message'] = $this->createErrorMessage('error_telekom_30');
					} else {
						$answer['Message'] = $this->createErrorMessage('error_telekom_code', $e->getCode());
					}
				}
			}
		}

		return $answer;
	}

	/**
	 * Adds sent SMS in statistics (Typo3 registry and DB)
	 *
	 * @param	int		$feUserId: FrontEnd User ID
	 * @return	void
	 */
	private function updateStatistics($feUserId) {
		// Registry
		$registry = t3lib_div::makeInstance('t3lib_Registry');
		$count = $registry->get('tx_' . $this->extKey, 'sms', 0);
		$count += $this->countSms($this->bs['message']);
		$registry->set('tx_' . $this->extKey, 'sms', $count);

		// Table "tx_sendsms_sms"
		if (!$this->ff['SendWithoutSignUp']) {
			$this->bs['smsInPeriod'] += $this->countSms($this->bs['message']) * $this->bs['countOfNumbers'];
			$this->dbAddSmsInTable($feUserId, $this->bs['sms_sent'] + $this->countSms($this->bs['message']) * $this->bs['countOfNumbers'],
				$this->bs['smsInPeriod'], $this->bs['currentPeriodStart']->format(DateTime::ISO8601), $this->bs['currentPeriodEnd']->format(DateTime::ISO8601));
		}

		// Table "tx_sendsms_stats"
		for ($i = 0; $i < $this->bs['countOfNumbers']; $i++) {
			$this->dbAddInStatistics(getdate(), $this->countChars($this->bs['message']));
		}
	}

	/**
	 * Returns TRUE if user has enough free SMS
	 *
	 * @param	int		$feUserId: FrontEnd User ID
	 * @return	bool		Result of test
	 */
	private function testEnoughSms($feUserId) {
		if ($this->ff['SendWithoutSignUp']) {
			$this->bs['smsInPeriod'] = 0;
			return TRUE;
		} else {
			$now = getdate();
			switch ($this->ff['TimeInterval']) {
				case 0:
					// 10 minutes
					$this->bs['currentPeriodStart']->setTime($now['hours'], floor($now['minutes'] / 10) * 10, 0);
					$this->bs['currentPeriodEnd']->setTime($now['hours'], floor($now['minutes'] / 10) * 10 + 9, 59);
					$this->bs['limitText'] = htmlspecialchars($this->pi_getLL('limit_10_min'));
					break;
				case 1:
					// 30 minutes
					if ($now['minute'] < 30) {
						$this->bs['currentPeriodStart']->setTime($now['hours'], 0, 0);
						$this->bs['currentPeriodEnd']->setTime($now['hours'], 29, 59);
					} else {
						$this->bs['currentPeriodStart']->setTime($now['hours'], 30, 0);
						$this->bs['currentPeriodEnd']->setTime($now['hours'], 59, 59);
					}
					$this->bs['limitText'] = htmlspecialchars($this->pi_getLL('limit_30_min'));
					break;
				case 2:
					// 1 hour
					$this->bs['currentPeriodStart']->setTime($now['hours'], 0, 0);
					$this->bs['currentPeriodEnd']->setTime($now['hours'], 59, 59);
					$this->bs['limitText'] = htmlspecialchars($this->pi_getLL('limit_60_min'));
					break;
				case 3:
					// 1 day
					$this->bs['currentPeriodStart']->setTime(0, 0, 0);
					$this->bs['currentPeriodEnd']->setTime(23, 59, 59);
					$this->bs['limitText'] = htmlspecialchars($this->pi_getLL('limit_86400_min'));
					break;
			}

			// Limits from DB
			$limits = $this->dbGetCountOfSms($feUserId);
			if (!is_null($limits)) {
				// There is row in DB
				$periodStart = new DateTime($limits['period_start']);
				$periodEnd = new DateTime($limits['period_end']);
				$this->bs['smsInPeriod'] = $limits['sms_sent_in_period'];
				$this->bs['sms_sent'] = $limits['sms_sent'];
			} else {
				// New row in DB
				$this->dbAddUserInTable($feUserId, $this->bs['currentPeriodStart']->format(DateTime::ISO8601), $this->bs['currentPeriodEnd']->format(DateTime::ISO8601));
				$periodStart = clone $this->bs['currentPeriodStart'];
				$periodEnd = clone $this->bs['currentPeriodEnd'];
			}

			// Hat der Benutzer noch freie SMS im Zeitraum?

			// Neuer Zeitraum
			if ($periodEnd->getTimestamp() <= $this->bs['currentPeriodStart']->getTimestamp()) {
				$this->bs['smsInPeriod'] = 0;
			}

			// Alter Zeitraum
			if ($periodStart->getTimestamp() >= $this->bs['currentPeriodStart']->getTimestamp() &&
				$periodEnd->getTimestamp() <= $this->bs['currentPeriodEnd']->getTimestamp()) {
				if ($this->bs['smsInPeriod'] < $this->ff['NumberOfSMS']) {
				}
			} else {
				if ($periodStart->getTimestamp()<= $this->bs['currentPeriodStart']->getTimestamp() &&
				$periodEnd->getTimestamp() >= $this->bs['currentPeriodEnd']->getTimestamp()) {
					// Alter Zeitraum
					$this->bs['smsInPeriod'] = 0;
				}
			}

			// Final test
			if ($this->ff['NumberOfSMS'] - $this->bs['smsInPeriod'] > 0) {
				return TRUE;
			} else {
				return FALSE;
			}
		}
	}

	/**
	 * Test message's length
	 *
	 * @return	array		true if there are too many symbols in message
	 */
	private function testCharactersCount() {
		if ($this->ff['NumberOfSMS'] - $this->bs['smsInPeriod'] > 4) {
			$limit = $this->bs['smsMaxLength'];
		} elseif ($this->ff['NumberOfSMS'] - $this->bs['smsInPeriod'] > 1) {
			$limit = ($this->ff['NumberOfSMS'] - $this->bs['smsInPeriod']) * $this->bs['manySmsLength'];
		} else {
			$limit = $this->bs['oneSmsLength'];
		}
		if ($this->countChars($this->piVars['smstext'] . $this->ff['SMSsignature']) > $limit) {
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * Selects from database
	 *
	 * @param	int		$feUserId: Frontend user ID
	 * @return	array		null or one row from table tx_sendsms_sms
	 */
	private function dbGetCountOfSms($feUserId) {
		$retValue = NULL;
        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'*',
            'tx_' . $this->extKey . '_sms',
            'fe_user_id=' . $feUserId
			);
		if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			$retValue = $row;
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($res);
		return $retValue;
	}

	/**
	 * Inserts in database
	 *
	 * @param	int		$feUserId: Frontend user ID
	 * @param	string		$periodStart: Time interval, in database-format
	 * @param	string		$periodEnd: Time interval, in database-format
	 * @return	void
	 */
	private function dbAddUserInTable($feUserId, $periodStart, $periodEnd) {
		$insertFields = array(
			'fe_user_id' => $feUserId,
			'sms_sent' => 0,
			'sms_sent_in_period' => 0,
			'period_start' => $periodStart,
			'period_end' => $periodEnd,
		);
		$GLOBALS['TYPO3_DB']->exec_INSERTquery(
			'tx_' . $this->extKey . '_sms',
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
	private function dbAddSmsInTable($feUserId, $smsSent, $smsSentInPeriod, $periodStart, $periodEnd) {
		$fieldsValues = array(
			'sms_sent' => $smsSent,
			'sms_sent_in_period' => $smsSentInPeriod,
			'period_start' => $periodStart,
			'period_end' => $periodEnd,
		);
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
			'tx_' . $this->extKey . '_sms',
			'fe_user_id=' . $feUserId,
			$fieldsValues
		);
	}

	/**
	 * Adds date, time and length of new sms
	 *
	 * @param	array		$now: current date and time
	 * @param	int		$length: length of sms
	 * @return	void
	 */
	private function dbAddInStatistics($now, $length) {
		$insertFields = array(
			'sms_day' => $now['mday'],
			'sms_month' => $now['mon'],
			'sms_year' => $now['year'],
			'sms_hour' => $now['hours'],
			'sms_length' => $length
		);
		$GLOBALS['TYPO3_DB']->exec_INSERTquery(
			'tx_' . $this->extKey . '_statistics',
			$insertFields
		);
	}

	/**
	 * Counts the characters in the text; some chars are counted as 2 characters
	 *
	 * @param	string		$text: message
	 * @return	int		number of chars
	 */
	private function countChars($text) {
		preg_match_all('/\x{20ac}|\x{005C}|\n|~|\^|\[|\]|\{|\}|\|/u', $text, $matches);
		$c = 0;
		if ($matches[0]) {
			$c = count($matches[0]);
		}
		return strlen($text) + $c - substr_count($text,'€') * 2;
	}

	/**
	 * Counts how many sms needs the message
	 *
	 * @param	string		$text: message
	 * @return	int		number of sms
	 */
	private function countSms($text) {
		$c = $this->countChars($text);
		if ($c <= $this->bs['oneSmsLength']) {
			return 1;
		} else {
			return ceil($c / $this->bs['manySmsLength']);
		}
	}

	/**
	 * Validates only one phone number
	 *
	 * @param	string		$text: One phone number from all entered numbers
	 * @return	boolean		Validation mark
	 */
	private function validateNumber($text) {
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
	 * @param	string		$text: Text with numbers
	 * @param	object		$codes: Class tx_sendsms_callingcodes object, finds calling code for phone numbers
	 * @return	array		array with phone numbers und validation mark (true/false)
	 */
	private function validateAllNumbers($text, $codes = NULL) {
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
	 * Counts price of all SMS sent to all numbers
	 *
	 * @param	array		$arr: Array with all phone numbers and zones (1-4)
	 * @return	float		price
	 */
	private function countPriceOfSms($arr) {
		$sum = 0;
		foreach ($arr as $r) {
			if (!is_null($r[4])) {
				switch ($r[4]) {
					case 0:
						$sum += $this->countSms($this->bs['message']) * 0.099;
						break;
					case 1:
						$sum += $this->countSms($this->bs['message']) * 0.105;
						break;
					case 2:
						$sum += $this->countSms($this->bs['message']) * 0.127;
						break;
					case 3:
						$sum += $this->countSms($this->bs['message']) * 0.165;
						break;
					case 4:
						$sum += $this->countSms($this->bs['message']) * 0.202;
						break;
				}
			}
		}
		return $sum;
	}

	/**
	 * Returns TypoLink
	 *
	 * @param	string		$text: Text, that is displayed on page
	 * @param	string		$formatLink: Text, Page ID
	 * @param	string		$formatLinkAddParams: Page additional parameters
	 * @return	string		Complete TypoLink with cHash
	 */
	private function linkToDoc($text, $pageId, $pageAddParams) {
		if ($pageId) {
			$conf = array (
				'parameter' => $pageId,
				'additionalParams' => $pageAddParams,
				'target' => 'blank',
				'useCacheHash' => 1,
			);
			return $this->cObj->typoLink($text, $conf);
		}
		return $text;
	}

	/**
	 * Gets error subpart from template and returns with error message
	 *
	 * @param	string		$index: error message index from locallang.xml
	 * @param	string		$value: %1$s value
	 * @return	string		html error template with text
	 */
	private function createErrorMessage($index, $value = NULL) {
		if (is_null($value)) {
			return $this->insertIntoSubpart(
				'###ERROR###',
				'###SPN_ERROR###',
				htmlspecialchars($this->pi_getLL($index))
			);
		} else {
			if (strlen($index) == 0) {
				return $this->insertIntoSubpart(
					'###ERROR###',
					'###SPN_ERROR###',
					htmlspecialchars($value)
				);
			} else {
				return $this->insertIntoSubpart(
					'###ERROR###',
					'###SPN_ERROR###',
					htmlspecialchars(sprintf($this->pi_getLL($index), $value))
				);
			}
		}
	}

	/**
	 * Adds link to CSS and JS files into HTML document
	 *
	 * @return	void
	 */
	private function addCssAndJs() {
		$css = '<link rel="stylesheet" type="text/css" href="' .
			t3lib_extMgm::siteRelPath($this->extKey) . 'res/tx_' . $this->extKey . '_style.css" />';
		if($GLOBALS['TSFE']->additionalHeaderData[$this->prefixId]){
			$GLOBALS['TSFE']->additionalHeaderData[$this->prefixId] .= $css;
		} else {
			$GLOBALS['TSFE']->additionalHeaderData[$this->prefixId] = $css;
		}
		$GLOBALS['TSFE']->additionalHeaderData[$this->prefixId].= '<script src="'.t3lib_extMgm::siteRelPath($this->extKey).'/pi1/js/tx_' . $this->extKey . '_pi1.js" /></script>';
	}

	/**
	 * Sets some base settings values in compliance with selected environment
	 *
	 * @return	void
	 */
	private function setEnvironment() {
		switch ($this->ff['Environment']) {
			case 0:
				$this->bs['environment'] = 'production';
			break;
			case 1:
				$this->bs['environment'] = 'sandbox';
				$this->bs['smsMaxLength'] = $this->bs['oneSmsLength'];
				$this->ff['SMSsignature'] = ' SMS API by developergarden.com';
				$this->ff['WebSiteName'] = 'Developer Garden';
			break;
			case 2:
				$this->bs['environment'] = 'mock';
			break;
		}
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sendsms/pi1/class.tx_sendsms_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sendsms/pi1/class.tx_sendsms_pi1.php']);
}
?>