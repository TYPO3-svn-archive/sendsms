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
 *   70: class tx_sendsms_sms
 *  131:     private function countChars($text)
 *  146:     private function countSms($text)
 *  160:     public function  getCountOfChars()
 *  169:     public function  getCountOfRecipients()
 *  178:     public function  getCountOfSms()
 *  187:     public function getEnvironment()
 *  197:     public function getPrice()
 *  235:     public function init($environment, $username, $password, $proxy = '', $sender = 'SendSmsExt', $signature = '')
 *  262:     public function send($flash = FALSE)
 *  329:     public function setRestrictions($maxRecipients, $maxSms, $smsSent)
 *  353:     public function test()
 *  367:     public function testMessageLength()
 *  380:     private function testMessage()
 *  415:     private function testRecipients($codes)
 *  460:     private function validateAllNumbers($text, $codes = NULL)
 *  492:     private function validateNumber($text)
 *
 * TOTAL FUNCTIONS: 16
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

// Includes the Send SMS client
require_once(dirname(__FILE__) . '/../lib/sdk/sendsms/client/SendSmsClient.php');
require_once(dirname(__FILE__) . '/../lib/sdk/sendsms/data/SendSmsStatusConstants.php');

// Includes the Calling Codes
require_once('class.tx_sendsms_callingcodes.php');

/**
 * SMS class for the 'sendsms' extension.
 *
 * @author	Alexander Kraskov <t3extensions@developergarden.com>
 * @package	TYPO3
 * @subpackage	tx_sendsms
 */
class tx_sendsms_sms {

	/*
	 * Defaults for SMS sending
	 * See documentation on www.developergarden.com
	 */
	const SingleSmsLength = 160;
	const MultiSmsLength = 153;
	const MaxSmsLength = 765;
	const MaxSmsLengthInSandBox = 160;

	/*
	 * Defaults for price calculation
	 * See price list on www.developercenter.telekom.com
	 */
	const PriceZone0 = 0.099;
	const PriceZone1 = 0.105;
	const PriceZone2 = 0.127;
	const PriceZone3 = 0.165;
	const PriceZone4 = 0.202;

	/*
	 * Send SMS Telekom Client
	 */
	private $environment = '';
	private $username = '';
	private $password = '';
	private $proxy = '';

	/*
	 *
	 */
	private $sender = '';
	private $signature = '';

	/*
	 *  Restrictions
	 */
	private $maxLength = 0;
	private $maxSms = 0;
	private $maxRecipients = 0;
	private $smsSent = 0;

	/*
	 *
	 */
	private $codes = NULL;
	private $numbers = array();

	/*
	 * Public variables
	 */
	public $recipients = '';
	public $message = '';

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
		if ($c <= $this::SingleSmsLength) {
			return 1;
		} else {
			return ceil($c / $this::MultiSmsLength);
		}
	}

	/**
	 * Counts length of message. Some charachters has 2 places
	 *
	 * @return	int		message lengtth
	 */
	public function  getCountOfChars() {
		return $this->countChars($this->message . $this->signature);
	}

	/**
	 * Counts recipients
	 *
	 * @return	int		number of recipients
	 */
	public function  getCountOfRecipients() {
		return count($this->numbers);
	}

	/**
	 * Counts SMS (message lenght and recipients)
	 *
	 * @return	int		number of sms
	 */
	public function  getCountOfSms() {
		return $this->countSms($this->message . $this->signature) * count($this->numbers);
	}

	/**
	 * Returns environment name
	 *
	 * @return	string		environment
	 */
	public function getEnvironment() {
		return $this->environment;
	}

	/**
	 * Counts price of all SMS sent to all numbers
	 *
	 * @param	array		$arr: Array with all phone numbers and zones (1-4)
	 * @return	float		price
	 */
	public function getPrice() {
		$sum = 0;
		$sms = $this->countSms($this->message);
		foreach ($this->numbers as $r) {
			if (!is_null($r[4])) {
				switch ($r[4]) {
					case 0:
						$sum += $sms * $this::PriceZone0; // Germany
					break;
					case 1:
						$sum += $sms * $this::PriceZone1;
					break;
					case 2:
						$sum += $sms * $this::PriceZone2;
					break;
					case 3:
						$sum += $sms * $this::PriceZone3;
					break;
					case 4:
						$sum += $sms * $this::PriceZone4;
					break;
				}
			}
		}
		return $sum;
	}

	/**
	 * Initializes object
	 *
	 * @param	int		$environment: 0-production, 1-sandbox, 2-mock
	 * @param	string		$username: Telekom Developer Center username
	 * @param	string		$password: Telekom Developer Center password
	 * @param	string		$proxy: Proxy (optional
	 * @param	string		$sender: Sender name
	 * @param	string		$signature: adds to message
	 * @return	void
	 */
	public function init($environment, $username, $password, $proxy = '', $sender = 'SendSmsExt', $signature = '')
	{
		$this->username = $username;
		$this->password = $password;
		$this->proxy = $proxy;
		$this->sender = $sender;
		$this->signature =  $signature;
		switch ($environment) {
			case 0:
				$this->environment = 'production';
			break;
			case 1:
				$this->environment = 'sandbox';
				$this->signature  = ' SMS API by developergarden.com';
			break;
			case 2:
				$this->environment = 'mock';
			break;
		}
	}

	/**
	 * Sends the text message
	 *
	 * @param	bool		$flash: if TRUE sends flash sms
	 * @return	array		array with error flag, label and value
	 */
	public function send($flash = FALSE) {
		$retValue = array(
			'error' => TRUE,
			'label' => '',
			'value' => ''
		);

		if ($flash) {
			$flash = 'true';
		} else {
			$flash = 'false';
		}

		// Can be created the Telekom client?
		try {
			$client = new SendSmsClient($this->environment, $this->username, $this->password);
			if ($this->proxy) {
				$client->use_additional_curl_options(array(CURLOPT_PROXY => $this->proxy));
			}
		} catch(Exception $e) {
			$retValue['label'] = 'error_loginpassword';
			return $retValue;
		}

		// The result of sending an SMS
		$sendSmsResponse = NULL;
		try {
			// Sends the SMS
			$sendSmsResponse = $client->sendSms($this->recipients, $this->message, $this->sender, $flash, NULL);
			// Test, if the invocation of sendSms() was successful.
			if (!($sendSmsResponse->getStatus()->getStatusConstant() == SendSmsStatusConstants::SUCCESS)) {
				$errorMessage = 'error_telekom_' . trim($sendSmsResponse->getStatus()->getStatusCode());
				throw new Exception($errorMessage, $sendSmsResponse->getStatus()->getStatusCode());
			}
		} catch(Exception $e) {
			if ($e->getCode() == 0) {
				$retValue['label'] = 'error_telekom_connect';
				return $retValue;
			} else {
				if (strlen($e->getMessage()) > 0) {
					$retValue['label'] = $e->getMessage();
					return $retValue;
				} else {
					if ($e->getCode() == 30) {
						$retValue['label'] = 'error_telekom_30';
						return $retValue;
					} else {
						$retValue['label'] = 'error_telekom_code';
						$retValue['value'] = $e->getCode();
						return $retValue;
					}
				}
			}
		}

		$retValue['error'] = FALSE;
		return $retValue;
	}

	/**
	 * Sets SMS sending restrictions
	 *
	 * @param	int		$maxRecipients: maximal number of recipients
	 * @param	int		$maxSms: maximal number of sms to send
	 * @param	int		$smsSent: number of sent sms
	 * @return	void
	 */
	public function setRestrictions($maxRecipients, $maxSms, $smsSent) {
		$this->maxRecipients = $maxRecipients;
		$this->maxSms = $maxSms;
		$this->smsSent = $smsSent;
		if ($this->environment == 'sandbox') {
			$this->maxLength = $this::MaxSmsLengthInSandBox;
		} else {
			if ($maxSms - $smsSent == 1) {
				$this->maxLength = $this::SingleSmsLength;
			} elseif ($maxSms - $smsSent > 4) {
				$this->maxLength = $this::MaxSmsLength;
			} else {
				$this->maxLength = ($maxSms - $smsSent) * $this::MultiSmsLength;
			}
		}

		$this->codes = t3lib_div::makeInstance('tx_sendsms_callingcodes');
	}

	/**
	 * Tests recipients and message
	 *
	 * @return	array		array with error flag, error label and sometimes second value for sprintf
	 */
	public function test() {
		$retValue = $this->testRecipients($this->codes);
		if ($retValue['error']) {
			return $retValue;
		} else {
			return $this->testMessage();
		}
	}

	/**
	 * Tests lenght of message+signature
	 *
	 * @return	bool		FALSE if message is too long
	 */
	public function testMessageLength() {
		if ($this->countChars($this->message . $this->signature) > $this->maxLength) {
			return FALSE;
		} else {
			return TRUE;
		}
	}

	/**
	 * Tests message
	 *
	 * @return	array		array with error flag, error label and sometimes second value for sprintf
	 */
		private function testMessage() {
		$retValue = array(
			'error' => TRUE,
			'label' => '',
			'value' => ''
		);

		// Is message empty?
		if (strlen($this->message) == 0) {
			$retValue['label'] = 'error_message';
			return $retValue;
		}

		// Is the message too big?
		if ($this->countChars($this->message . $this->signature) > $this->maxLength) {
			$retValue['label'] = 'error_longmessage';
			return $retValue;
		}

		// Has user enough sms?
		if (($this->countSms($this->message  . $this->signature) * $this->countOfRecipients) > ($this->maxSms - $this->smsSent)) {
			$retValue['label'] = 'error_longmessage';
			return $retValue;
		}

		$retValue['error'] = FALSE;
		return $retValue;
	}

	/**
	 * Tests recipietns
	 *
	 * @param	object		$codes: class "tx_sendsms_callingcodes "
	 * @return	array		array with error flag, error label and sometimes second value for sprintf
	 */
	private function testRecipients($codes) {
		$retValue = array(
			'error' => TRUE,
			'label' => '',
			'value' => ''
		);

		// Is recipients field empty?
		if (strlen($this->recipients) == 0) {
			$retValue['label'] = 'error_phone';
			return $retValue;
		}

		// Test all inputed numbers
		$this->numbers = $this->validateAllNumbers($this->recipients, $this->codes);

		$errorNumbers = '';
		foreach($this->numbers as $number) {
			if ($number[0] == FALSE) {
				$errorNumbers .= $number[2] . ', ';
			}
		}
		if (strlen($errorNumbers) > 0) {
			$errorNumbers = substr($errorNumbers, 0, strlen($errorNumbers) - 2);
			$retValue['label'] = 'error_numberformat';
			$retValue['value'] = $errorNumbers;
			return $retValue;
		} else {
			if (count($this->numbers) > $this->maxRecipients) {
				$retValue['label'] = 'error_toomanyrecipietns';
				return $retValue;
			}
		}

		$retValue['error'] = FALSE;
		return $retValue;
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
}

if (!defined ('PATH_typo3conf')) die ('Resistance is futile.');

?>
