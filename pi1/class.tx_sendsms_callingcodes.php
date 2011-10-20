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
 *   44: class  tx_sendsms_callingcodes
 *   84:     function removeSpecialSymbols($text)
 *  105:     function test($number, $c, $arr)
 *  124:     function getCallingCode($number)
 *  156:     function test2($code, $c, $arr)
 *  173:     function getTarifzone($code)
 *
 * TOTAL FUNCTIONS: 5
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */
class  tx_sendsms_callingcodes {
	// Lists with calling codes, are used to find a calling code in phone number
	protected  $fourNumerals = array (3906, 1246, 1264, 1268, 1345, 1441, 1473, 1649, 1767, 1784, 1809, 1829, 1868, 1869, 1876);
	protected  $threeNumerals = array (210, 211, 212, 213, 214, 215, 216, 217, 218, 219, 220, 221, 222, 223, 224, 225, 226, 227, 228, 229, 230, 231, 232, 233, 234, 235, 236, 237, 238, 239, 240, 241, 242, 243, 244, 245, 246, 247, 248, 249, 250, 251, 252, 253, 254, 255, 256, 257, 258, 260, 261, 262, 263, 264, 265, 266, 267, 268, 269, 290, 291, 297, 298, 299,
								350, 351, 352, 353, 354, 355, 356, 357, 358, 359, 370, 371, 372, 373, 374, 375, 376, 377, 378, 379, 380, 381, 382, 383, 384, 385, 386, 387, 389,
								420, 421, 423,
								500, 501, 502, 503, 504, 505, 506, 507, 508, 509, 590, 591, 592, 593, 594, 595, 596, 597, 598, 599,
								670, 672, 673, 674, 675, 676, 678, 679, 680, 681, 682, 683, 685, 686, 687, 688, 689, 690, 691, 692,
								850, 852, 853, 855, 856, 880, 886,
								960, 961, 962, 963, 964, 965, 966, 967, 968, 970, 971, 972, 973, 974, 975, 976, 977, 992, 993, 994, 995, 996, 998);
	protected  $twoNumerals = array (20, 27,
							  30, 31, 32, 33, 34, 36, 39,
							  40, 41, 43, 44, 45, 46, 47, 48, 49,
							  51, 52, 53, 54, 55, 56, 57, 58,
							  61, 62, 63, 64, 65 ,66,
							  81, 82, 83, 84, 86,
							  91, 92, 93, 94, 95, 98);
	protected  $oneNumeral = array (1, 7);
	// All prices: http://www.telekom.de/dlp/agb/pdf/39014.pdf
	// Tarifberiech 1. 0,105 €
	protected  $zone1 = array (93, 20, 376, 244, 1264, 1268, 240, 54, 374, 297, 251, 994, 973, 880, 1246, 501, 229, 1441, 975, 591, 387, 267, 55,
						673, 359, 226, 257, 56, 886, 506, 253, 1767, 1809, 1829, 593, 503, 298, 679, 689, 241, 220, 995, 233, 350, 590,
						299, 502, 224, 592, 504, 98, 964, 972, 1876, 81, 967, 962, 855, 237, 1, 238, 1345, 254, 57, 269, 243, 242, 850, 82,
						53, 965, 996, 856, 371, 218, 423, 370, 352, 853, 261, 60, 960, 223, 356, 596, 222, 262, 52, 377, 976, 264, 977, 599,
						505, 227, 234, 47, 968, 92, 970, 507, 595, 51, 974, 262, 7, 250, 260, 685, 966, 221, 248, 232, 263, 252, 94, 1869, 1784,
						249, 597, 268, 963, 992, 255, 228, 235, 993, 1649, 256, 380, 598, 998, 58, 84, 375, 236, 357);
	// Tarifberiech 2. 0,127 €
	// Kasachstan (Mobilfunk: 6xx, Ortsvorwahlen: 7xx => 76 & 77)
	protected  $zone2 = array (76, 77, 355, 213, 61, 45, 225, 372, 358, 30, 1473, 44, 91, 62, 353, 39, 385, 961, 265, 212, 230, 389, 373, 382, 258, 687, 64, 63, 48, 351,
						40, 381, 65, 421, 386, 27, 46, 41, 66, 420, 216, 36, 971);
	// Tarifberiech 3. 0,165 €
	protected  $zone3 = array (32, 33, 852, 354, 31, 43, 378, 34, 90);
	// Tarifberiech 4. 0,202 €
	protected  $zone4 = array (86, 3906);
	/**
	 * Removes all special symbols from phone number
	 *
	 * @param	string		$text: phone number how user had entered
	 * @return	string		phone number without special symbols, only figures
	 */
	protected function removeSpecialSymbols($text) {
		$retValue = null;
		if (strlen($text) > 2) {
			if (substr($text, 0, 1) == '+') {
				return substr($text, 1, strlen($text) - 1);
			} elseif (substr($text, 0, 2) == '00') {
				return substr($text, 2, strlen($text) - 2);
			} elseif (substr($text, 0, 1) == '0') {
				return null;
			}
		}
		return $retValue;
	}
	/**
	 * Searches in "numerals" arrays
	 *
	 * @param	string		$number: phone number, only figures
	 * @param	int			$c: calling codes length
	 * @param	array		$arr: callign codes array
	 * @return	boolean		found or not
	 */
	protected function test($number, $c, $arr) {
		$found = false;
		$n = (int) substr($number, 0, $c);
		for ($i = 0; $i < count($arr); $i++) {
			if ($arr[$i] == $n) {
				$found = true;
				break;
			}
		}
		return $found;
	}
	/**
	 * Searches Callign code in phone nummer
	 * If first sign is 0, returns 49
	 * Returns null, if nothing has been found
	 *
	 * @param	string		$number: phone number, only figures
	 * @return	string		calling code or null
	 */
	public function getCallingCode($number) {
		$retValue = null;
		$n = $this->removeSpecialSymbols($number);
		if (!is_null($n)) {
			if (strlen($n)>4) {
				if ($this->test($n, 4, $this->fourNumerals)) {
					$retValue = substr($n, 0, 4);
				} elseif ($this->test($n, 1, $this->oneNumeral)) {
					$retValue = substr($n, 0, 1);
				} elseif ($this->test($n, 2, $this->twoNumerals)) {
					$retValue = substr($n, 0, 2);
				} elseif ($this->test($n, 3, $this->threeNumerals)) {
					$retValue = substr($n, 0, 3);
				}
			}
		} else {
			if (strlen($number)>1) {
				if (substr($number,0,1)=='0') {
					return '49';
				}
			}
		}
		return $retValue;
	}
	/**
	 * Searches Tarifzones index
	 *
	 * @param	string		$code: calling code
	 * @param	int			$c: index of Tarifzone
	 * @param	array		$arr: Tarifzone array
	 * @return	int			calling code, if it has been found
	 */
	protected function test2($code, $c, $arr) {
		$n = (int)$code;
		for ($i = 0; $i < count($arr); $i++) {
			if ($arr[$i] == $n) {
				$found = true;
				return $c;
				break;
			}
		}
		return null;
	}
	/**
	 * Returns Tarifzone
	 *
	 * @param	string		$code: Calling code
	 * @return	int			index of Tarifzone, 1-4
	 */
	public function getTarifzone($code) {
		$n = (int)$code;
		if ($n == 49) {
			return 0;
		}
		$retValue = 0;
		if (!is_null($this->test2($n, 4, $this->zone4))) {
			return 4;
		}
		if (!is_null($this->test2($n, 3, $this->zone3))) {
			return 3;
		}
		if (!is_null($this->test2($n, 2, $this->zone2))) {
			return 2;
		}
		if (!is_null($this->test2($n, 1, $this->zone1))) {
			return 1;
		}
		return null;
	}
}
if (!defined ('PATH_typo3conf')) die ('Resistance is futile');
?>