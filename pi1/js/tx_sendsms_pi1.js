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
function morePhones() {
	var phone = document.getElementById("tx_sendsms_pi1_phone");
	var inputSize = document.getElementById("tx_sendsms_pi1_inputsize");
	var inputSizeMin = document.getElementById("tx_sendsms_pi1_inputsize_min");
	var inputSizeMax = document.getElementById("tx_sendsms_pi1_inputsize_max");
	if (phone.size == inputSizeMax.value) {
		phone.size = inputSizeMin.value;
		inputSize.value = inputSizeMin.value;
	} else {
		phone.size = inputSizeMax.value;
		inputSize.value = inputSizeMax.value;
	}
}

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
function trim(text) {
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
	var smsText = document.getElementById("tx_sendsms_pi1_text");
	var phones = document.getElementById("tx_sendsms_pi1_phone");
	var recipients = R(phones.value);
	var labelChars = document.getElementById("tx_sendsms_pi1_text_chars");
	var labelSms = document.getElementById("tx_sendsms_pi1_text_sms");
	var labelSms = document.getElementById("tx_sendsms_pi1_text_sms");
	var smsLeft = document.getElementById("tx_sendsms_pi1_text_sms_left");
	var maxSms = document.getElementById("tx_sendsms_pi1_text_max_sms");
	var len = getChars(smsText.value);
	if(len == 0) {
		labelChars.innerHTML = c;
		labelSms.innerHTML = recipients;
		if (recipients > smsLeft.value) {
			labelSms.style.color = "red";
			labelChars.style.color = "red";
		} else {
			labelSms.style.color = "";
			labelChars.style.color = "";
		}
		return;
	}
	var sms;
	var max = maxSms.value;
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
	labelChars.innerHTML = c + len;
	labelSms.innerHTML = sms * recipients;
	return;
}