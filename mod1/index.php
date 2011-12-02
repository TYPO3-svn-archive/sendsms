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
 *   65: class  tx_sendsms_module1 extends t3lib_SCbase
 *   74:     function init()
 *   90:     function menuConfig()
 *  110:     function selectedMonth($s)
 *  129:     function main()
 *  162:     function jumpToUrl(URL)
 *  233:     function printContent()
 *  245:     function addMessage($text, $caption, $type)
 *  263:     function errorMaker($name, $response)
 *  280:     function moduleContent()
 *
 * TOTAL FUNCTIONS: 9
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */


$LANG->includeLLFile('EXT:sendsms/mod1/locallang.xml');
require_once(PATH_t3lib . 'class.t3lib_scbase.php');
$BE_USER->modAccess($MCONF,1);	// This checks permissions and exits if the users has no permission for entry.
	// DEFAULT initialization of a module [END]

require_once('class.tx_sendsms_diagramm.php');


/**
 * Module 'Send SMS T3 Extension' for the 'sendsms' extension.
 *
 * @author	Alexander Kraskov <t3extensions@developergarden.com>
 * @package	TYPO3
 * @subpackage	tx_sendsms
 */
class  tx_sendsms_module1 extends t3lib_SCbase {
	var $pageinfo;
	var $extKey = 'sendsms';	// The extension key.

	/**
	 * Initializes the Module
	 *
	 * @return	void
	 */
	function init()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;

		parent::init();
		/*
		if (t3lib_div::_GP('clear_all_cache'))	{
			$this->include_once[] = PATH_t3lib.'class.t3lib_tcemain.php';
		}
		*/
	}

	/**
	 * Adds items to the ->MOD_MENU array. Used for the function menu selector.
	 *
	 * @return	void
	 */
	function menuConfig()	{
		global $LANG;
		$this->MOD_MENU = Array (
			'function' => Array (
				'1' => $LANG->getLL('function1'),
				'2' => $LANG->getLL('function2'),
				'3' => $LANG->getLL('function3'),
				'4' => $LANG->getLL('function4'),
				'5' => $LANG->getLL('function5'),
			)
		);
		parent::menuConfig();
	}

	/**
	 * Returns content for HTML Form's element "select" filled with months
	 *
	 * @param	int		$s:	selected month index (1-12)
	 * @return	string		with tags "option"
	 */
	function selectedMonth($s) {
		global $LANG;
		$retValue='';
		for ($i=1; $i<13; $i++) {
			if ($i != $s) {
				$retValue.='<option value="'.$i.'">'.$LANG->getLL('m'.$i).'</option>';
			} else {
				$retValue.='<option selected="selected" value="'.$i.'">'.$LANG->getLL('m'.$i).'</option>';
			}
		}
		return $retValue;
	}

	/**
	 * Main function of the module. Write the content to $this->content
	 * If you chose "web" as main module, you will need to consider the $this->id parameter which will contain the uid-number of the page clicked in the page tree
	 *
	 * @return	void		...
	 */
	function main()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;

		// Access check!
		// The page will show only if there is a valid page and if this page may be viewed by the user
		$this->pageinfo = t3lib_BEfunc::readPageAccess($this->id,$this->perms_clause);
		$access = is_array($this->pageinfo) ? 1 : 0;

		if (($this->id && $access) || ($BE_USER->user['admin'] && !$this->id))	{

			// Draw the header.
			$this->doc = t3lib_div::makeInstance('template');
			$this->doc->backPath = $BACK_PATH;
			$this->doc->form = '<div class="typo3-fullDoc">' .
			'<div id="typo3-docheader">' .
				'<div id="typo3-docheader-row1">' .
					'<div class="buttonsleft"></div>' .
					'<div class="buttonsright">';
							// ShortCut
							if ($BE_USER->mayMakeShortcut()) {
								$this->doc->form .= $this->doc->makeShortcutIcon('id', implode(',', array_keys($this->MOD_MENU)), $this->MCONF['name']);
							}
$this->doc->form .= '</div>' .
				'</div>' .
				'<div id="typo3-docheader-row2">' .
					'<div class="docheader-row2-left">' .
						'<div class="docheader-funcmenu">';
							// Control
							$this->doc->form .= '<form action="" method="post" enctype="multipart/form-data">';
							// JavaScript
							$this->doc->JScode = '
								<script language="javascript" type="text/javascript">
									script_ended = 0;
									function jumpToUrl(URL)	{
										document.location = URL;
									}
								</script>
								';
							$this->doc->postCode='
								<script language="javascript" type="text/javascript">
									script_ended = 1;
									if (top.fsMod) top.fsMod.recentIds["web"] = 0;
								</script>
								';
							$this->doc->form .= $this->doc->funcMenu('', t3lib_BEfunc::getFuncMenu($this->id,'SET[function]', $this->MOD_SETTINGS['function'], $this->MOD_MENU['function']));
							$this->doc->form .= '</form>' .
						'</div>' .
					'</div>' .
					'<div class="docheader-row2-right"></div>' .
				'</div>' .
			'</div>';

			$this->content .= $this->doc->startPage($LANG->getLL('title'));
			$this->content .= '<div id="typo3-docbody"><div id="typo3-inner-docbody">';
			$this->content .= $this->doc->header($LANG->getLL('title'));
			$this->content .= $this->doc->spacer(5);
			$this->content .= $LANG->getLL('important_links') . ' ';
			$this->content .= '<a href="http://www.developergarden.com" style="color:green;" target="_blank">Developer Garden</a> & ';
			$this->content .= '<a href="http://www.developercenter.telekom.com" style="color:#E20074;" target="_blank">Developer Center</a><br />';
			$this->content .= $this->doc->spacer(5);
			$this->content .= $this->doc->divider(5);

			// Render content:
			$this->moduleContent();

			$this->content .= '</div></div></div>';

		} else {
			// If no access or if ID == zero
			// Draw the header.
			$this->doc = t3lib_div::makeInstance('template');
			$this->doc->backPath = $BACK_PATH;
			$this->doc->form = '<div class="typo3-fullDoc">' .
				'<div id="typo3-docheader">' .
					'<div id="typo3-docheader-row1">' .
						'<div class="buttonsleft"></div>' .
						'<div class="buttonsright"></div>' .
					'</div>' .
					'<div id="typo3-docheader-row2">' .
						'<div class="docheader-row2-left"></div>' .
						'<div class="docheader-row2-right"></div>' .
					'</div>' .
				'</div>';
			$this->content .= $this->doc->startPage($LANG->getLL('title'));
			$this->content .= '<div id="typo3-docbody"><div id="typo3-inner-docbody">';
			$this->content .= $this->doc->header($LANG->getLL('title'));
			$this->content .= $this->doc->spacer(5);
			$this->content .= $LANG->getLL('important_links') . ' ';
			$this->content .= '<a href="http://www.developergarden.com" style="color:green;" target="_blank">Developer Garden</a> & ';
			$this->content .= '<a href="http://www.developercenter.telekom.com" style="color:#E20074;" target="_blank">Developer Center</a><br />';
			$this->content .= $this->doc->spacer(5);
			$this->content .= $this->doc->divider(5);

			$this->content .= '<span class="t3-icon t3-icon-status t3-icon-status-status t3-icon-status-permission-denied">&nbsp;</span><span style="vertical-align:bottom;">' . $LANG->getLL('access_denied') . '</span>';

			$this->content .= '</div></div></div>';
		}
	}

	/**
	 * Prints out the module HTML
	 *
	 * @return	void
	 */
	function printContent() {
		$this->content.=$this->doc->endPage();
		echo $this->content;
	}
	/**
	 * Adds a new Typo3 Flash message in queue and returns all messages
	 *
	 * @param	string		$text:	message's text
	 * @param	string		$caption:	message's caption
	 * @param	string		$type:	message's type (t3lib_FlashMessage::NOTICE, INFO, OK, WARNING, ERROR)
	 * @return	string		rendered message queue
	 */
	function addMessage($text, $caption, $type) {
		$flashMessage = t3lib_div::makeInstance(
			't3lib_FlashMessage',
			$text,
			$caption,
			$type
		);
		t3lib_FlashMessageQueue::addMessage($flashMessage);
		return '<div style="width:468px;">' . t3lib_FlashMessageQueue::renderFlashMessages() . '</div>';
	}

	/**
	 * Prepares error text from Telekom Client response
	 *
	 * @param	string		$name: name of function
	 * @param	object		$response: Telekom Client response
	 * @return	string		...
	 */
	function errorMaker($name, $response) {
		$errorMessage  = 'The invocation of ' . $name . ' was not successful.<br />';
		$errorMessage .= 'The error code is: ' . $response->getStatus()->getStatusCode() . '<br />';
		$errorMessage .= 'The error message is: ' . $response->getStatus()->getStatusMessage() . '<br />';
		if ($LANG->lang == 'de') {
			$errorMessage .= 'The error description is: ' . $response->getStatus()->getStatusDescriptionGerman();
		} else {
			$errorMessage .= 'The error description is: ' . $response->getStatus()->getStatusDescriptionEnglish();
		}
		return $errorMessage;
	}

	/**
	 * Generates the module content
	 *
	 * @return	void
	 */
	function moduleContent() {
		global $LANG;
		switch((string)$this->MOD_SETTINGS['function'])	{
			case 1:
				// fe_users
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'COUNT(uid)',
					'fe_users',
					''
				);
				$max = 0;
				$countFeUsers = 0;
				$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
				if ($row) {
					$countFeUsers = $row['COUNT(uid)'];
				}
				$GLOBALS['TYPO3_DB']->sql_free_result($res);

				// tx_sendsms_sms
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'sms_sent',
					'tx_sendsms_sms',
					''
				);

				$countSmsFeUsers = 0;
				$countVisitFeUsers = 0;
				while(($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))) {
					$countVisitFeUsers++;
					if ($row['sms_sent'] > 0) {
						$countSmsFeUsers++;
					}
				}
				$GLOBALS['TYPO3_DB']->sql_free_result($res);

				// tx_sendsms_statistics
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'COUNT(sms_id), sms_hour',
					'tx_sendsms_statistics',
					'',
					'sms_hour',
					'sms_hour'
				);
				$rows = array();
				$max = 0;
				$arr  = array();
				while(($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))) {
					$rows[] = $row;
					$max += $row['COUNT(sms_id)'];
				}
				$GLOBALS['TYPO3_DB']->sql_free_result($res);
				foreach ($rows as $row)	{
					$arr[$row['sms_hour']] = round($row['COUNT(sms_id)'] * 100 / $max);
				}

				$diagramm = t3lib_div::makeInstance('tx_sendsms_diagramm');
				$diagramm->value_text = '';

				$registry = t3lib_div::makeInstance('t3lib_Registry');
				$count = $registry->get('tx_' . $this->extKey, 'sms', 0);

				$content = '<b>' . $LANG->getLL('number_of_users') . '</b><br />' .
					'<br />' . $LANG->getLL('website_users') . ' ' . $countFeUsers .
					'<br />' . $LANG->getLL('one_sms') . ' ' . $countSmsFeUsers .
					'<br />' . $LANG->getLL('only_visit') . ' ' . ($countVisitFeUsers-$countSmsFeUsers) .
					'<br />' . $LANG->getLL('general_number_sms') . ' ' . $count .
					'<br />' . $LANG->getLL('average_sms') . ' ' . (($countSmsFeUsers > 0) ? $count/$countSmsFeUsers : 0 ) .
					'<h3 class="uppercase"></h3>'.
					'<div style="width=468px;text-align:center;position:absolute;"><strong>' .
					$LANG->getLL('diagram1') . '</strong>' . $diagramm->draw($arr) . '</div>';

				$this->content .= $this->doc->section($LANG->getLL('header1'), $content, 0, 1);
				break;
			case 2:
				$month = date('m');
				if ($_POST['month']) {
					$month = $_POST['month'];
				}
				$year = date('Y');
				if ($_POST['year'])	{
					if (is_numeric($_POST['year'])) {
						$year = $_POST['year'];
					}
				}

				$varDate = mktime(0, 0, 0, $month, 1, $year);
				$days = date('t', $varDate);

				// tx_sendsms_statistics
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'COUNT(sms_id), sms_day',
					'tx_sendsms_statistics',
					'sms_month=' . $month . ' and sms_year=' . $year,
					'sms_day',
					'sms_day'
				);
				$rows = array();
				$max = 0;
				while(($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))) {
					$rows[] = $row;
					if ($max < $row['COUNT(sms_id)']) {
						$max = $row['COUNT(sms_id)'];
					}
				}
				$GLOBALS['TYPO3_DB']->sql_free_result($res);

				$cy=11;
				$ly=20;
				$my=10;

				if ($max <= 100) {
					$my = 10;
				} else {
					$len = strlen((string) $max);
					$my = floor((floor($max / pow(10, $len - 1)) + 1) * pow(10, $len - 2));
				}

				$arr  = array();
				foreach ($rows as $row)	{
					$arr[$row['sms_day'] - 1] = $row['COUNT(sms_id)'];
				}

				$diagramm = t3lib_div::makeInstance('tx_sendsms_diagramm');
				$diagramm->x0 = 30;
				$diagramm->cx = $days + 1;
				$diagramm->lx = 14;
				$diagramm->kx = -7;
				$diagramm->kv = -2;
				$diagramm->my = $my;
				$diagramm->cy = $cy;
				$diagramm->ly = $ly;
				$diagramm->value_text = '';
				$diagramm->axis_y_text = 'sms';
				$diagramm->write_x0 = FALSE;
				$content='<div style="width=468px;text-align:center;position:absolute;"><strong>' . $LANG->getLL('diagram2') . '</strong>' .
					$diagramm->draw($arr) . '<br />' .
					'<form method="POST">' .
					'<select name=month>' . $this->selectedMonth($month) . '</select>&nbsp;' .
					'<input type="text" name="year" maxlength=4 size=4 value="' . $year . '" />&nbsp;' .
					'<input type="submit" value="' . $LANG->getLL('fuction2_button') . '"/>' .
					'</form>' .
					'</div>';

				$this->content .= $this->doc->section($LANG->getLL('header2'), $content, 0, 1);
				break;
			case 3:
				$r = array(
					0 => 0,
					1 => 0,
					2 => 0,
					3 => 0,
					4 => 0
				);
				// tx_sendsms_statistics
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'sms_length',
					'tx_sendsms_statistics',
					'1=1'
				);
				$max = 0;

				while(($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))) {
					$max++;
					if ($row['sms_length']<161) {
						$r[0]++;
					} elseif ($row['sms_length'] > 160 && $row['sms_length'] < 307) {
						$r[1]++;
					} elseif ($row['sms_length'] > 306 && $row['sms_length'] < 460) {
						$r[2]++;
					} elseif ($row['sms_length'] > 459 && $row['sms_length'] < 613) {
						$r[3]++;
					}  elseif ($row['sms_length'] > 612 && $row['sms_length'] < 766) {
						$r[4]++;
					}
				}
				$GLOBALS['TYPO3_DB']->sql_free_result($res);

				$arr = array();
				for ($i=0; $i<7; $i++) {
					$arr[$i] = round($r[$i] * 100 / $max);
				}

				$diagramm = t3lib_div::makeInstance('tx_sendsms_diagramm');
				$diagramm->cx = 6;
				$diagramm->lx = 85;
				$diagramm->kx = 43;
				$diagramm->kv = 35;
				$diagramm->axis_x_text = 'sms';
				$names = array(
					'1',
					'2',
					'3',
					'4',
					'5'
				);

				$content = '<div style="width=468px;text-align:center;position:absolute;"><strong>' . $LANG->getLL('diagram3') . '</strong>';
				$content .= $diagramm->draw($arr, $names, $r).'</div>';

				$this->content .= $this->doc->section($LANG->getLL('header3'), $content, 0, 1);
				break;
			case 4:
				// HTML Form Developer Center: login, password, proxy
				$content = $this->addMessage($LANG->getLL('validation_text'), $LANG->getLL('validation_capture'), t3lib_FlashMessage::INFO) .
					'<h3 class="uppercase">' . $LANG->getLL('dcaccount') . '</h3>' .
					'<form method="POST">' .
					'<label for="devcenterlogin">' . $LANG->getLL('dclogin') . '</label><br />' .
					'<input type="text" id="devcenterlogin" name="dclogin" value="'.$_POST['dclogin'].'"><br />' .
					'<br />' .
					'<label for="devcenterpassword">' . $LANG->getLL('dcpassword') . '</label><br />' .
					'<input type="password" id="devcenterpassword" name="dcpassword" value="'.$_POST['dcpassword'].'"><br />' .
					'<br />' .
					'<label for="devcenterproxy">Proxy (optional):</label><br />' .
					'<input type="text" id="devcenterproxy" name="dcproxy" value="'.$_POST['dcproxy'].'"><br />' .
					'<br />' .
					'<h3 class="uppercase">' . $LANG->getLL('header_validation') . '</h3>' .
					'<label for="phonenummber">' . $LANG->getLL('label_phone1') . '</label><br />' .
					'<input type="text" id="phonenummber" name="sendsmsphonenumber" value="'.$_POST['sendsmsphonenumber'].'">&nbsp;' .
					'<input type="submit" value="' . $LANG->getLL('button_request') . '" name="sendsmsrequestcode">' .
					'<br /><br />' .
					'<label for="validationcode">' . $LANG->getLL('label_code') . '</label><br />' .
					'<input type="text" id="validationcode" name="sendsmsvalidationcode" value="'.$_POST['sendsmsvalidationcode'].'">&nbsp;' .
					'<input type="submit" value="' . $LANG->getLL('button_validate') . '" name="sendsmsvalidate"><br />';
				// SMS Validation code request
				if ($_POST['sendsmsrequestcode']) {
					require_once(dirname(__FILE__) . '/../lib/sdk/smsvalidation/client/SmsValidationClient.php');
					require_once(dirname(__FILE__) . '/../lib/sdk/smsvalidation/data/SmsValidationStatusConstants.php');
					// Telekom user name
					$username = $_POST['dclogin'];
					// Telekom password
					$password = $_POST['dcpassword'];
					// Proxy (optional)
					$proxy = $_POST['dcproxy'];
					// Status
					$ok = TRUE;
					// Constructs the Telekom client using the user name and password.
					try {
						$client = new SmsValidationClient('production', $username, $password);
						if ($proxy) {
							$client->use_additional_curl_options(array(CURLOPT_PROXY => $proxy));
						}
					} catch(Exception $e) {
						$ok = FALSE;
						$content .= '<br />' .
							$this->addMessage($LANG->getLL('dcerror'), $LANG->getLL('err_request'), t3lib_FlashMessage::ERROR);
					}
					if ($ok == TRUE) {
						// Number, which is to be validated
						$number = $_POST['sendsmsphonenumber'];
						// The originator
						$originator = "SendSMSExt";
						// The result of the transmission of the SMS validation keyword
						$sendValidationKeywordResponse = NULL;
						try {
							// Sends the validation message to the specified number
							$sendValidationKeywordResponse = $client->sendValidationKeyword($number, NULL, $originator, NULL);
							// Test, if the invocation of sendValidationKeyword() was successful.
							if(!($sendValidationKeywordResponse->getStatus()->getStatusConstant() == SmsValidationStatusConstants::SUCCESS)) {
								throw new Exception($this->errorMaker('sendValidationKeyword()', $sendValidationKeywordResponse));
							} else {
								$content .= '<br />' .
									$this->addMessage('Ok', $LANG->getLL('ok_request'), t3lib_FlashMessage::OK);
							}
						} catch(Exception $e) {
							$content .= '<br />' .
								$this->addMessage($e->getMessage(), $LANG->getLL('err_request'), t3lib_FlashMessage::ERROR);
						}
					}
				}
				// Validate requested code
				if ($_POST['sendsmsvalidate']) {
					require_once(dirname(__FILE__).'/../lib/sdk/smsvalidation/client/SmsValidationClient.php');
					require_once(dirname(__FILE__).'/../lib/sdk/smsvalidation/data/SmsValidationStatusConstants.php');
					// Telekom user name
					$username = $_POST['dclogin'];
					// Telekom password
					$password = $_POST['dcpassword'];
					// Proxy (optional)
					$proxy = $_POST['dcproxy'];
					// Status
					$ok = TRUE;
					// Constructs the Telekom client using the user name and password.
					try {
						$client = new SmsValidationClient('production', $username, $password);
						if ($proxy) {
							$client->use_additional_curl_options(array(CURLOPT_PROXY => $proxy));
						}
					} catch(Exception $e) {
						$ok = FALSE;
						$content .= '<br />' .
							$this->addMessage($LANG->getLL('dcerror'), $LANG->getLL('err_validation'), t3lib_FlashMessage::ERROR);
					}
					if ($ok == TRUE) {
						// The number to be validated
						$number = $_POST['sendsmsphonenumber'];
						// The keyword contained in the validation message
						$key = $_POST['sendsmsvalidationcode'];
						// The result of the validation
						$validationResponse = NULL;
						try {
							// Validates the number using the validation key
							$validationResponse = $client->validate($number, $key);

							// Test, if the invocation of validate() was successful.
							if(!($validationResponse->getStatus()->getStatusConstant() == SmsValidationStatusConstants::SUCCESS)) {
								throw new Exception($this->errorMaker('validate()', $validationResponse));
							} else {
								$content .= '<br />' .
									$this->addMessage('Ok', $LANG->getLL('ok_validation'), t3lib_FlashMessage::OK);
							}
						} catch(Exception $e) {
							$content .= '<br />' .
								$this->addMessage($e->getMessage(), $LANG->getLL('err_validation'), t3lib_FlashMessage::ERROR);
						}
					}
				}
				// HTML Form: Invalidate phone number
				$content .= '<br />' .
					'<h3 class="uppercase">' . $LANG->getLL('header_invalidation') . '</h3>' .
					'<label for="phonenummber2">' . $LANG->getLL('label_phone2') . '</label><br />' .
					'<input type="text" id="phonenummber2" name="sendsmsphonenumber2" value="'.$_POST['sendsmsphonenumber2'].'">&nbsp;' .
					'<input type="submit" value="' . $LANG->getLL('button_invalidate') . '" name="sendsmsinvalidate">' .
					'<br />';
				// Invalidate phone number
				if ($_POST['sendsmsinvalidate']) {
					require_once(dirname(__FILE__) . '/../lib/sdk/smsvalidation/client/SmsValidationClient.php');
					require_once(dirname(__FILE__) . '/../lib/sdk/smsvalidation/data/SmsValidationStatusConstants.php');
					// Telekom user name
					$username = $_POST['dclogin'];
					// Telekom password
					$password = $_POST['dcpassword'];
					// Proxy (optional)
					$proxy = $_POST['dcproxy'];
					// Status
					$ok = TRUE;
					// Constructs the Telekom client using the user name and password.
					try {
						$client = new SmsValidationClient('production', $username, $password);
						if ($proxy) {
							$client->use_additional_curl_options(array(CURLOPT_PROXY => $proxy));
						}
					} catch(Exception $e) {
						$ok = FALSE;
						$content .= '<br />' .
							$this->addMessage($LANG->getLL('dcerror'), $LANG->getLL('err_invalidation'), t3lib_FlashMessage::ERROR);
					}
					if ($ok == TRUE) {
						// The number to be invalidated
						$number = $_POST['sendsmsphonenumber2'];
						// The result of the validation
						$invalidateResponse = NULL;
						try {
							// Validates the number using the validation key
							$invalidateResponse = $client->invalidate($number);
							// Test, if the invocation of invalidate() was successful.
							if(!($invalidateResponse->getStatus()->getStatusConstant() == SmsValidationStatusConstants::SUCCESS)) {
								throw new Exception($this->errorMaker('invalidate()', $invalidateResponse));
							} else {
								$content .= '<br />' .
									$this->addMessage('Ok', $LANG->getLL('ok_invalidation'), t3lib_FlashMessage::OK);
							}
						} catch(Exception $e) {
							$content .= '<br />' .
								$this->addMessage($e->getMessage(), $LANG->getLL('err_invalidation'), t3lib_FlashMessage::ERROR);
						}
					}
				}
				// HTML Form: Get list of validated numbers
				$content .= '<br />' .
					'<h3 class="uppercase">' . $LANG->getLL('header_validatedlist') . '</h3>' .
					'<br />' .
					'<input type="submit" value="' . $LANG->getLL('button_getlist') . '" name="sendsmsvalidatedlist">' .
					'</form>';
				// Get list of validated numbers
				if ($_POST['sendsmsvalidatedlist']) {
					require_once(dirname(__FILE__) . '/../lib/sdk/smsvalidation/client/SmsValidationClient.php');
					require_once(dirname(__FILE__) . '/../lib/sdk/smsvalidation/data/SmsValidationStatusConstants.php');
					// Telekom user name
					$username = $_POST['dclogin'];
					// Telekom password
					$password = $_POST['dcpassword'];
					// Proxy (optional)
					$proxy = $_POST['dcproxy'];
					// Status
					$ok = TRUE;
					// Constructs the Telekom client using the user name and password.
					try {
						$client = new SmsValidationClient('production', $username, $password);
						if ($proxy) {
							$client->use_additional_curl_options(array(CURLOPT_PROXY => $proxy));
						}
					} catch(Exception $e) {
						$ok = FALSE;
						$content .= '<br />' .
							$this->addMessage($LANG->getLL('dcerror'), $LANG->getLL('err_validatedlist'), t3lib_FlashMessage::ERROR);
					}
					if ($ok == TRUE) {
						$getValidatedNumbersResponse = NULL;
						try {
							// Validates the number using the validation key
							$getValidatedNumbersResponse = $client->getValidatedNumbers();
							// Test, if the invocation of getValidatedNumbers() was successful.
							if(!($getValidatedNumbersResponse->getStatus()->getStatusConstant() == SmsValidationStatusConstants::SUCCESS)) {
								throw new Exception($this->errorMaker('getValidatedNumbers()', $getValidatedNumbersResponse));
							} else {
								$list .= '<br/>';
								foreach ($getValidatedNumbersResponse->getNumbers() as $number) {
									$list .= $number->getNumber();
									if (!is_null($number->getValidUntil())) {
										$list .= ' until '.$number->getValidUntil();
									}
									$list .= '<br />';
								}
								$content .= '<br />' . $this->addMessage($list, $LANG->getLL('ok_validatedlist'), t3lib_FlashMessage::INFO);
							}
						} catch(Exception $e) {
							$content .= '<br />' . $this->addMessage($e->getMessage(), $LANG->getLL('err_validatedlist'), t3lib_FlashMessage::ERROR);
						}
					}
				}
				$this->content.=$this->doc->section($LANG->getLL('header4'), $content, 0, 1);
				break;
			case 5:
				$content = $this->addMessage($LANG->getLL('tools_text'), $LANG->getLL('tools_capture'), t3lib_FlashMessage::INFO) .
					'<br />' .
					'<form method="POST">' .
					'<input type="checkbox" id="count" name="count" value="ok" style="vertical-align:middle;" /> <label for="count">'.$LANG->getLL('setting1').'</label>' .
					'<br /><br />' .
					'<input type="submit" value="Start" />' .
					'</form><br />';
				// Update "count of sms" registry entry
				if ($_POST['count']) {
					$err = FALSE;
					if ($_POST['count'] == 'ok') {
						try {
							// tx_sendsms_sms
							$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
								'sum(sms_sent)',
								'tx_sendsms_sms',
								''
							);
							if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
								$registry = t3lib_div::makeInstance('t3lib_Registry');
								$registry->set('tx_' . $this->extKey, 'sms', $row['sum(sms_sent)']);
							}
							$GLOBALS['TYPO3_DB']->sql_free_result($res);
						} catch(Exception $e) {
							$err = TRUE;
							$content .= '<br />' . $this->addMessage($e->getMessage(), 'SMS counter', t3lib_FlashMessage::ERROR);
						}
						if (!$err) {
							$content .= '<br />' . $this->addMessage('Ok', 'SMS counter', t3lib_FlashMessage::OK);
						}
					}
				}
				$this->content.=$this->doc->section($LANG->getLL('header5'), $content, 0, 1);
				break;
			// switch end
		}
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sendsms/mod1/index.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sendsms/mod1/index.php']);
}

// Make instance:
$SOBE = t3lib_div::makeInstance('tx_sendsms_module1');
$SOBE->init();

// Include files?
foreach($SOBE->include_once as $INC_FILE)	include_once($INC_FILE);

$SOBE->main();
$SOBE->printContent();

?>