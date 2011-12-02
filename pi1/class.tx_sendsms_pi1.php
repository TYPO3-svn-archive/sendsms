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
 *   69: class tx_sendsms_pi1 extends tslib_pibase
 *   89:     public function main($content, $conf)
 *  211:     private function addCssAndJs()
 *  228:     private function createMarkerArray()
 *  287:     private function enoughSms($feUserId)
 *  375:     private function getFlexFormValues()
 *  403:     private function init($conf)
 *  423:     private function initBaseSettings()
 *  442:     private function linkToDoc($text, $pageId, $pageAddParams)
 *  460:     private function loadCaptcha()
 *  477:     private function spDisabledForm($message)
 *  500:     private function spResultForm()
 *  550:     private function spStartForm($error = NULL)
 *  692:     private function updateStatistics($feUserId)
 *
 * TOTAL FUNCTIONS: 13
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

// Parent class
require_once(PATH_tslib.'class.tslib_pibase.php');

// Includes the SMS Class
require_once('class.tx_sendsms_sms.php');

// Includes the DB Class
require_once('class.tx_sendsms_db.php');

/**
 * Plugin 'SMS via Telekom API' for the 'sendsms' extension.
 *
 * @author	Alexander Kraskov <t3extensions@developergarden.com>
 * @package	TYPO3
 * @subpackage	tx_sendsms
 */
class tx_sendsms_pi1 extends tslib_pibase {
	var $prefixId = 'tx_sendsms_pi1';						// Same as class name
	var $scriptRelPath = 'pi1/class.tx_sendsms_pi1.php';	// Path to this script relative to the extension dir.
	var $extKey = 'sendsms';								// The extension key.

	private $freeCap = NULL; 	// CAPTCHA
	private $templateHtml = '';	// Template
	private $bs = NULL;			// Base settings
	private $ff = NULL;			// Array with FlexForm values
	private $markerArray;		// Main marker array
	private $sms = NULL;		// SMS object
	private $db = NULL;			// DB object

	/**
	 * The main method of the PlugIn
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	string		The	content that is displayed on the website
	 */
	public function main($content, $conf) {
		// Initializes the Plugin
		$this->init($conf);

		// Adds links to CSS and JS into page
		$this->addCssAndJs();

		// Loads values from FlexForm
		$this->getFlexFormValues();

		// Loads CAPTCHA if needs (Field "Captcha" in FlexForm)
		$this->loadCaptcha();

		// Sets base settigns
		$this->initBaseSettings();

		// Creates DB object
		$this->db = t3lib_div::makeInstance('tx_sendsms_db');

		// Creates SMS object
		$this->sms = t3lib_div::makeInstance('tx_sendsms_sms');

		// Initializes SMS object
		$this->sms->init($this->ff['Environment'], $this->ff['DCLogin'], $this->ff['DCPassword'], $this->ff['Proxy'], $this->ff['SMSsender'], $this->ff['SMSsignature']);

		// Initializes marker array
		$this->markerArray = $this->createMarkerArray();

		// Front end user id
		$feUserId = 0;
		if (!$this->ff['SendWithoutSignUp']) {
			$feUserId = $GLOBALS['TSFE']->fe_user->user['uid'];
		}
		if (($feUserId == 0) && !$this->ff['SendWithoutSignUp']) {
			$this->markerArray['###LBL_RECIPIENTS###'] = htmlspecialchars($this->pi_getLL('form_phone'));
			$subpart = $this->spDisabledForm(htmlspecialchars($this->pi_getLL('form_login')));
			$content = $this->cObj->substituteMarkerArray($subpart, $this->markerArray);
			return $this->pi_wrapInBaseClass($content);
		}

		// Test and send
		if (!$this->enoughSms($feUserId)) {
			// No sms left
			$this->bs['currentPeriodEnd']->add(new DateInterval('PT1S'));
			$subpart = $this->spDisabledForm(htmlspecialchars(sprintf($this->pi_getLL('not_enough_sms'), $this->bs['currentPeriodEnd']->format('H:i d.m.y'))));
			$content = $this->cObj->substituteMarkerArray($subpart, $this->markerArray);
			return $this->pi_wrapInBaseClass($content);
		} else {
			// User has free sms
			//
			// Sets restrictions
			$this->sms->setRestrictions($this->ff['NumberOfPhones'], $this->bs['maxSmsInPeriod'], $this->bs['smsSentInPeriod']);

			if (!isset($this->piVars['submit_button'])) {
				// START PAGE - NEW SMS
				$GLOBALS["TSFE"]->fe_user->setKey('ses', $this->extKey . '_sms_sent', FALSE);
				$GLOBALS["TSFE"]->fe_user->storeSessionData();
				$subpart = $this->spStartForm();
			} else {
				// SMS WAS SEND or THERE ARE ERRORS

				$this->markerArray['###VAL_MESSAGE###'] = htmlspecialchars($this->piVars['smstext']);
				$this->markerArray['###VAL_RECIPIENTS###'] = htmlspecialchars($this->piVars['recipient']);
				$this->markerArray['###RECIPIENTSSIZE###'] = htmlspecialchars($this->piVars['inputsize']);
				if (htmlspecialchars($this->piVars['flash_sms']) == 'on') {
					$this->markerArray['###CHECKED_FLASH_SMS###'] = ' checked="checked"';
				}
				// CAPTCHA
				if ($this->ff['CAPTCHA']) {
					if (is_object($this->freeCap)) {
						if(!$this->freeCap->checkWord($this->piVars['captcha_response'])) {
							$subpart = $this->spStartForm(array('error' => TRUE, 'label' => 'error_captcha', 'value' => ''));
							$content = $this->cObj->substituteMarkerArray($subpart, $this->markerArray);
							return $this->pi_wrapInBaseClass($content);
						}
					}
				}

				if ($this->ff['FeedbackMode']) {
					$this->sms->recipients = trim($this->ff['FeedbackPhone']);
				} else {
					$this->sms->recipients = trim(htmlspecialchars($this->piVars['recipient']));
				}
				$this->sms->message = trim(htmlspecialchars($this->piVars['smstext']));

				$answer = $this->sms->test();
				if ($answer['error']) {
					$subpart = $this->spStartForm($answer);
				} else {
					if ($GLOBALS["TSFE"]->fe_user->getKey('ses', $this->extKey . '_sms_sent') == TRUE) {
						// SMS has been sent already
						$subpart = $this->spResultForm();
					} else {
						// Send SMS
						$flash = FALSE;
						if ($this->ff['FlashSMS'] && htmlspecialchars($this->piVars['flash_sms']) == 'on') {
							$flash = TRUE;
						}
						$response = $this->sms->send($flash);
						if ($response['error'] > 0) {
							// Error
							$subpart = $this->spStartForm($response);
						} else {
							// Successfully
							$GLOBALS["TSFE"]->fe_user->setKey('ses', $this->extKey . '_sms_sent', TRUE);
							$GLOBALS["TSFE"]->fe_user->storeSessionData();
							$this->updateStatistics($feUserId);
							$subpart = $this->spResultForm();
						}
					}
				}
			}
		}
		$content = $this->cObj->substituteMarkerArray($subpart, $this->markerArray);
		return $this->pi_wrapInBaseClass($content);
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
		$GLOBALS['TSFE']->additionalHeaderData[$this->prefixId] .= '<script src="' . t3lib_extMgm::siteRelPath($this->extKey) . '/pi1/js/tx_' . $this->extKey . '_pi1.js" /></script>';
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
			'###LBL_MESSAGE###' => htmlspecialchars($this->pi_getLL('form_message')),
			'###VAL_SUBMIT###' => htmlspecialchars($this->pi_getLL('form_submit')),
			'###SIGNATURELENGTH###' => strlen($this->ff['SMSsignature']),
			'###MESSAGEROWS###' => '10',
			'###MESSAGECOLS###' => '53',
			'###ISMIN###' => '20',
			'###ISMAX###' => '63',
			'###SPN_RESTRICTIONS###' => '',
			'###LBL_CHARS_COUNT###' => $this->linkToDoc(htmlspecialchars($this->pi_getLL('form_characters')), $this->ff['LinkRestrictionsPageID'], $this->ff['LinkRestrictionsPageAddParams']),
			'###SPN_CHARS_COUNT###' => '0',
			'###LBL_SMS_COUNT###' => $this->linkToDoc(htmlspecialchars($this->pi_getLL('form_sms')), $this->ff['LinkRestrictionsPageID'], $this->ff['LinkRestrictionsPageAddParams']),
			'###SPN_SMS_COUNT###' => '0',
			'###VAL_RECIPIENTS###' => '',
			'###VAL_MESSAGE###' => '',
			'###CHECKED_FLASH_SMS###' => ''
		);

		if ($this->ff['HideSignatureExplanation']) {
			$array['###LBL_FOOTNOTE_SIGN###'] = '';
		} else {
			$array['###LBL_FOOTNOTE_SIGN###'] = htmlspecialchars($this->pi_getLL('form_characters_footnote_sign'));
		}

		// Maximal number of recipients
		if ($this->ff['HideMaxRecipients']) {
			$array['###LBL_RECIPIENTS###'] = htmlspecialchars($this->pi_getLL('form_phone'));
		} else {
			$array['###LBL_RECIPIENTS###'] = htmlspecialchars(sprintf(
				$this->pi_getLL('form_phone_template'),
				$this->pi_getLL('form_phone'), $this->ff['NumberOfPhones'])
			);
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
	 * Returns TRUE if user has enough free SMS
	 *
	 * @param	int		$feUserId: FrontEnd User ID
	 * @return	bool		Result of test
	 */
	private function enoughSms($feUserId) {
		if ($this->ff['SendWithoutSignUp']) {
			$this->bs['smsSentInPeriod'] = 0;
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

			// Limits from Database
			$limits = $this->db->getCountOfSms($feUserId);
			if (!is_null($limits)) {
				// There is row in DB
				$periodStart = new DateTime($limits['period_start']);
				$periodEnd = new DateTime($limits['period_end']);
				$this->bs['smsSentInPeriod'] = $limits['sms_sent_in_period'];
				$this->bs['smsSent'] = $limits['sms_sent'];
			} else {
				// New row in DB
				$this->db->addUserInTable($feUserId, $this->bs['currentPeriodStart']->format(DateTime::ISO8601), $this->bs['currentPeriodEnd']->format(DateTime::ISO8601));
				$periodStart = clone $this->bs['currentPeriodStart'];
				$periodEnd = clone $this->bs['currentPeriodEnd'];
			}

			// Hat der Benutzer noch freie SMS im Zeitraum?

			// Neuer Zeitraum
			if ($periodEnd->getTimestamp() <= $this->bs['currentPeriodStart']->getTimestamp()) {
				$this->bs['smsSentInPeriod'] = 0;
			}

			// Alter Zeitraum
			if ($periodStart->getTimestamp() >= $this->bs['currentPeriodStart']->getTimestamp() &&
				$periodEnd->getTimestamp() <= $this->bs['currentPeriodEnd']->getTimestamp()) {
				if ($this->bs['smsSentInPeriod'] < $this->bs['maxSmsInPeriod']) {
				}
			} else {
				if ($periodStart->getTimestamp()<= $this->bs['currentPeriodStart']->getTimestamp() &&
				$periodEnd->getTimestamp() >= $this->bs['currentPeriodEnd']->getTimestamp()) {
					// Alter Zeitraum
					$this->bs['smsSentInPeriod'] = 0;
				}
			}

			// Final test
			if ($this->bs['maxSmsInPeriod'] - $this->bs['smsSentInPeriod'] > 0) {
				return TRUE;
			} else {
				return FALSE;
			}
		}
	}

	/**
	 * Loads all flexform values to an array with keys
	 *
	 * @param	array		$flexFormSheets: The FlexForm array
	 * @return	void
	 */
	private function getFlexFormValues() {
		// Page language
		$langId = $GLOBALS['TSFE']->config['config']['sys_language_uid'];
		$langDefinition = ($langId != 0) ? 'l' . strtoupper($this->LLkey) : 'lDEF';
		// Array with flex form values
		$this->ff = array();
		// Flex form struct
		$struct = array(
			'sms_sheet0' => array('DCLogin', 'DCPassword', 'Proxy',	'Environment',),
			'sms_sheet1' => array('SMSsender', 'SMSsignature', 'WebSiteName', 'FeedbackMode', 'FeedbackPhone'),
			'sms_sheet2' => array('NumberOfSMS', 'TimeInterval', 'NumberOfPhones', 'SendWithoutSignUp', 'FlashSMS', 'CAPTCHA'),
			'sms_sheet3' => array('LinkPhoneFormatPageID', 'LinkPhoneFormatPageAddParams', 'LinkRestrictionsPageID', 'LinkRestrictionsPageAddParams', 'LinkFlashSMSPageID', 'LinkFlashSMSPageAddParams', 'LinkEnvironmentPageID', 'LinkEnvironmentPageAddParams'),
			'sms_sheet4' => array('HideRestrictions', 'HideMaxRecipients', 'HideEnvironment', 'HidePhoneNumberFormat', 'HideCharactersCount', 'HideSignatureExplanation', '')
		);
		// Load data
		foreach ($struct as $sheetName => $sheetValue) {
			foreach ($sheetValue as $itemName) {
				$this->ff[$itemName] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], $itemName, $sheetName);
			}
		}
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
		// Get the template from file
		$this->templateHtml = $this->cObj->fileResource($conf['templateFile']);
	}

	/**
	 * Sets initial settings to the base settigns array
	 *
	 * @return	void
	 */
	private function initBaseSettings() {
		$this->bs = array(
			'currentPeriodStart' => new DateTime(),
			'currentPeriodEnd' => new DateTime(),
			'maxSmsInPeriod' => $this->ff['NumberOfSMS'],
			'smsSentInPeriod' => 0,
			'smsSent' => 0,
			'limitText' => ''
		);
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
	 * Loads CAPTCHA and writes in marker array
	 *
	 * @return	void
	 */
	private function loadCaptcha() {
		if ($this->ff['CAPTCHA']) {
			if (t3lib_extMgm::isLoaded('sr_freecap')) {
				require_once(t3lib_extMgm::extPath('sr_freecap') . 'pi2/class.tx_srfreecap_pi2.php');
				$this->freeCap = t3lib_div::makeInstance('tx_srfreecap_pi2');
			} else {
				$this->ff['CAPTCHA'] = FALSE;
			}
		}
	}

	/**
	 * Returns subpart for disabled form
	 *
	 * @param	string		$message: message to show in header
	 * @return	string		template subpart
	 */
	private function spDisabledForm($message) {
		$template = $this->cObj->getSubpart($this->templateHtml, '###DISABLED_FORM###');

		$subparts = array(
			'###RECIPIENTS###' => ''
		);

		$this->markerArray['###SPN_DISABLED###'] = $message;
		$this->markerArray['###LBL_RECIPIENTS###'] = htmlspecialchars($this->pi_getLL('form_phone'));

		// Recipients
		if (!$this->ff['FeedbackMode']) {
			$subparts['###RECIPIENTS###'] = $this->cObj->getSubpart($template, '###RECIPIENTS###');
		}

		return $this->cObj->substituteSubpartArray($template, $subparts);
	}

	/**
	 * Returns subpart for result form
	 *
	 * @return	string		template subpart
	 */
	private function spResultForm() {
		$template = $this->cObj->getSubpart($this->templateHtml, '###RESULT_FORM###');

		$subparts = array(
			'###ENVIROMENT###' => '',
			'###RECIPIENTS###' => '',
			'###PRICE###' => ''
		);

		// Environment
		if (!$this->ff['HideEnvironment']) {
			$subparts['###ENVIRONMENT###'] = $this->cObj->getSubpart($template, '###ENVIRONMENT###');
			$this->markerArray['###SPN_ENVIRONMENT###'] = sprintf(
				$this->pi_getLL('environment_template'),
				$this->linkToDoc(htmlspecialchars($this->pi_getLL('environment')), $this->ff['LinkEnvironmentPageID'], $this->ff['LinkEnvironmentPageAddParams']),
				$this->sms->getEnvironment()
			);
		}

		// Recipients
		if (!$this->ff['FeedbackMode']) {
			$subparts['###RECIPIENTS###'] = $this->cObj->getSubpart($template, '###RECIPIENTS###');
		}

		// Price
		if (!$this->ff['HidePrice']) {
			$subparts['###PRICE###'] = $this->cObj->getSubpart($template, '###PRICE###');
			if ($this->sms->getCountOfSms() > 1) {
				$this->markerArray['###SPN_PRICE###'] = htmlspecialchars(sprintf($this->pi_getLL('price_sms_pl'), $this->sms->getPrice()));
			} else {
				$this->markerArray['###SPN_PRICE###'] = htmlspecialchars(sprintf($this->pi_getLL('price_sms'), $this->sms->getPrice()));
			}
		}

		$this->markerArray['###LNK_NEXT_SMS###'] = sprintf(htmlspecialchars($this->pi_getLL('next_sms')), $this->linkToDoc($this->pi_getLL('click_here'), $this->pi_getPageLink($GLOBALS['TSFE']->id), ''));
		if (($this->sms->getCountOfSms()) > 1) {
			$this->markerArray['###SPN_STATUS###'] =  htmlspecialchars($this->pi_getLL('result_ok_pl'));
		} else {
			$this->markerArray['###SPN_STATUS###'] =  htmlspecialchars($this->pi_getLL('result_ok'));
		}

		return $this->cObj->substituteSubpartArray($template, $subparts);
	}

	/**
	 * Returns subpart for start form
	 *
	 * @param	array		$error: array with error flag, locallang.xml label and value
	 * @return	void
	 */
	private function spStartForm($error = NULL) {
		$template = $this->cObj->getSubpart($this->templateHtml, '###TEMPLATE###');

		$subparts = array(
			'###RESTRICTIONS###' => '',
			'###ENVIRONMENT###' => '',
			'###ERROR###' => '',
			'###RECIPIENTS###' => '',
			'###PHONE_NUMBER_FORMAT###' => '',
			'###CHARACTERS_COUNT###' => '',
			'###CHARACTERS_COUNT_RED###' => '',
			'###SIGNATURE_EXPLANATION###' => '',
			'###FLASH_SMS###' => '',
			'###CAPTCHA###' => ''
		);

		// Error
		if  (!is_null($error)) {
			$subparts['###ERROR###'] = $this->cObj->getSubpart($template, '###ERROR###');
			if (strlen($error['value']) > 0) {
				$this->markerArray['###SPN_ERROR###'] = htmlspecialchars(
					sprintf(
						$this->pi_getLL($error['label']),
						$error['value']
					)
				);
			} else {
				$this->markerArray['###SPN_ERROR###'] = htmlspecialchars($this->pi_getLL($error['label']));
			}
		}

		// Restrictions
		if (!$this->ff['HideRestrictions'] && !$this->ff['SendWithoutSignUp']) {
			$subparts['###RESTRICTIONS###'] = $this->cObj->getSubpart($template, '###RESTRICTIONS###');
			$this->markerArray['###SPN_RESTRICTIONS###'] = htmlspecialchars(sprintf($this->pi_getLL('form_youhave'), $this->bs['maxSmsInPeriod'] - $this->bs['smsSentInPeriod'], $this->bs['maxSmsInPeriod'], $this->bs['limitText']));
		}


		// Form action
		$this->markerArray['###FORMACTION###'] = $this->pi_getPageLink($GLOBALS['TSFE']->id);

		if ($this->ff['Environment'] == 1) {
			$this->markerArray['###SMSLEFT###'] = $this->bs['maxSmsInPeriod'] - $this->bs['smsSentInPeriod'];
			$this->markerArray['###MAXSMS###'] = 1;
		} else {
			if ($this->ff['NumberOfSMS'] - $this->bs['smsInPeriod'] > 4) {
				$this->markerArray['###SMSLEFT###'] = $this->bs['maxSmsInPeriod'] - $this->bs['smsSentInPeriod'];
				$this->markerArray['###MAXSMS###'] = 5;
			} else {
				$this->markerArray['###SMSLEFT###'] = $this->bs['maxSmsInPeriod'] - $this->bs['smsSentInPeriod'];
				$this->markerArray['###MAXSMS###'] = $this->bs['maxSmsInPeriod'] - $this->bs['smsSentInPeriod'];
			}
		}

		// Environment
		if (!$this->ff['HideEnvironment']) {
			$subparts['###ENVIRONMENT###'] = $this->cObj->getSubpart($template, '###ENVIRONMENT###');
			$this->markerArray['###SPN_ENVIRONMENT###'] = sprintf(
				$this->pi_getLL('environment_template'),
				$this->linkToDoc(htmlspecialchars($this->pi_getLL('environment')), $this->ff['LinkEnvironmentPageID'], $this->ff['LinkEnvironmentPageAddParams']),
				$this->sms->getEnvironment()
			);
		}

		// Recipients input
		if (!$this->ff['FeedbackMode']) {
			$subparts['###RECIPIENTS###'] = $this->cObj->getSubpart($template, '###RECIPIENTS###');
		}

		// Phone numbers format
		if (!$this->ff['HidePhoneNumberFormat'] && !$this->ff['FeedbackMode']) {
			$subparts['###PHONE_NUMBER_FORMAT###'] = $this->cObj->getSubpart($template, '###PHONE_NUMBER_FORMAT###');
			if (!$this->ff['LinkPhoneFormatPageID']) {
				$this->markerArray['###LBL_FORMAT###'] = htmlspecialchars($this->pi_getLL('form_format'));
			} else {
				$this->markerArray['###LBL_FORMAT###'] = sprintf(
					$this->pi_getLL('form_format_template'),
					htmlspecialchars($this->pi_getLL('form_format')),
					$this->linkToDoc(htmlspecialchars($this->pi_getLL('form_format_link')),
						$this->ff['LinkPhoneFormatPageID'],
						$this->ff['LinkPhoneFormatPageAddParams']
					)
				);
			}
		}

		// Characters and SMS counter
		if (!$this->ff['HideCharactersCount']) {
			$this->markerArray['###SPN_CHARS_COUNT###'] = $this->sms->getCountOfChars();
			$this->markerArray['###SPN_SMS_COUNT###'] = $this->sms->getCountOfSms();
			if ($this->sms->testMessageLength()) {
				$subparts['###CHARACTERS_COUNT###'] = $this->cObj->getSubpart($template, '###CHARACTERS_COUNT###');
				$subparts['###CHARACTERS_COUNT_RED###'] = '';
			} else {
				$subparts['###CHARACTERS_COUNT###'] = '';
				$subparts['###CHARACTERS_COUNT_RED###'] = $this->cObj->getSubpart($template, '###CHARACTERS_COUNT_RED###');
			}
		}

		// Signature
		if (!$this->ff['HideSignatureExplanation']) {
			$this->markerArray['###LBL_SIGNATURE_EXPLANATION###'] = htmlspecialchars(
				sprintf(
					$this->pi_getLL('form_signature'),
					$this->pi_getLL('form_characters_footnote_sign'),
					$this->ff['WebSiteName']
				)
			);
			$subparts['###SIGNATURE_EXPLANATION###'] = $this->cObj->getSubpart($template, '###SIGNATURE_EXPLANATION###');
		}

		// Flash SMS
		if ($this->ff['FlashSMS']) {
			$this->markerArray['###FLASHSMSLABEL###'] = sprintf(
				htmlspecialchars($this->pi_getLL('form_flash')),
				$this->linkToDoc(htmlspecialchars($this->pi_getLL('form_flash_link')),
				$this->ff['LinkFlashSMSPageID'],
				$this->ff['LinkFlashSMSPageAddParams'])
			);
			$subparts['###FLASH_SMS###'] = $this->cObj->getSubpart($template, '###FLASH_SMS###');
		}

		// CAPTCHA
		if ($this->ff['CAPTCHA']) {
			if (is_object($this->freeCap)) {
				$array = $this->freeCap->makeCaptcha();
				$array['###CAPTCHALABEL###'] = htmlspecialchars($this->pi_getLL('form_captcha'));
				$this->markerArray = array_merge($this->markerArray, $array);
			}
			$subparts['###CAPTCHA###'] = $this->cObj->getSubpart($template, '###CAPTCHA###');

		}

		return $this->cObj->substituteSubpartArray($template, $subparts);
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
		$count += $this->sms->getCountOfSms();
		$registry->set('tx_' . $this->extKey, 'sms', $count);

		// Table "tx_sendsms_sms"
		if (!$this->ff['SendWithoutSignUp']) {
			$this->db->addSmsInTable(
				$feUserId,
				$this->bs['smsSent'] + $this->sms->getCountOfSms(),
				$this->bs['smsSentInPeriod'] + $this->sms->getCountOfSms(),
				$this->bs['currentPeriodStart']->format(DateTime::ISO8601),
				$this->bs['currentPeriodEnd']->format(DateTime::ISO8601)
			);
		}

		// Table "tx_sendsms_stats"
		for ($i = 0; $i < $this->sms->getCountOfRecipients(); $i++) {
			$this->db->addInStatistics(getdate(), $this->sms->getCountOfChars());
		}
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sendsms/pi1/class.tx_sendsms_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sendsms/pi1/class.tx_sendsms_pi1.php']);
}

?>