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
 *   43: class  tx_sendsms_diagramm
 *   81:     function filledRectangle($x1 ,$y1 ,$x2, $y2, $color)
 *   95:     function line($x1 ,$y1 ,$x2, $y2, $color)
 *  114:     function text($size, $x1 ,$y1, $text, $color)
 *  126:     function draw($arr, $names = null, $values = null)
 *
 * TOTAL FUNCTIONS: 4
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */
class  tx_sendsms_diagramm {
	/** diagram's width (main div) */
	public $width = 468;
	/** diagram's height (main div) */
	public $height = 250;
	/** space from left */
	public $x0 = 35;
	/** space from bottom */
	public $y0 = 25;
	/** count of columns */
	public $cx = 24;
	/** count of intervals on y-axis */
	public $cy = 11;
	/** column's width */
	public $lx = 18;
	/** width of intervals on y-axis */
	public $ly = 20;
	/** x-axis name's shift coefficient */
	public $kx = 0;
	/** column's values shift coefficient */
	public $kv = 0;
	/** y-axis scale */
	public $my = 10;
	/** axes's name's font size */
	public $font_size = '9px';
	/** x-axis's name's font size */
	public $font_size_x = '9px';
	/** y-axis's name's font size */
	public $font_size_y = '9px';
	/** column's values font size */
	public $font_size_v = '9px';
	/** column's inner values font size */
	public $font_size_n = '9px';
	/** background color */
	public $colorBG = 'rgb(192,192,192)';
	/** column's color */
	public $colorColumn = 'rgb(0,0,80)';
	/** axes's color */
	public $colorLine = 'rgb(80,0,0)';
	/** text color (axes, values) */
	public $colorText = 'rgb(0,0,0)';
	/** inner text color */
	public $colorText2 = 'rgb(255, 255, 255)';
	/** x-axis' name */
	public $axis_x_text = '';
	/** y-axis' name */
	public $axis_y_text = '%';
	/** x-axis' name */
	public $value_text = '%';

	/**
	 * Draws a filled rectangle
	 *
	 * @param	int		$x1: x left top corner
	 * @param	int		$y1: y left top corner
	 * @param	int		$x2: x right bottom corner
	 * @param	int		$y2: y right bottom corner
	 * @param	stirng		$color: css color
	 * @return	string		div
	 */
	protected function filledRectangle($x1 ,$y1 ,$x2, $y2, $color) {
		return '<div style="position:absolute;left:'.$x1.'px;top:'.$y1.'px;width:'.($x2-$x1).'px;height:'.($y2-$y1).'px;background:'.$color.';"></div>';
	}

	/**
	 * Draws a line on diagram, only vertical or horizontal 
	 *
	 * @param	int		$x1: x left top corner
	 * @param	int		$y1: y left top corner
	 * @param	int		$x2: x right bottom corner
	 * @param	int		$y2: y right bottom corner
	 * @param	string		$color: css color
	 * @return	string		thin div, 1px
	 */
	protected function line($x1 ,$y1 ,$x2, $y2, $color) {
		if ($x1==$x2) {
			return '<div style="position:absolute;left:'.$x1.'px;top:'.$y1.'px;width:1px;height:'.($y2-$y1).'px;background:'.$color.';"></div>';
		}
		if ($y1==$y2) {
			return '<div style="position:absolute;left:'.$x1.'px;top:'.$y1.'px;width:'.($x2-$x1).'px;height:1px;background:'.$color.';"></div>';
		}
	}

	/**
	 * Writes a text on diagram, only horizontal
	 *
	 * @param	string		$size: font-size (css)
	 * @param	int		$x1: x left top corner
	 * @param	int		$y1: y left top corner
	 * @param	int		$text: text
	 * @param	int		$color: text color
	 * @return	string		div with text
	 */
	protected function text($size, $x1 ,$y1, $text, $color)	{
		return '<div style="position:absolute;left:'.$x1.'px;top:'.$y1.'px;"><span class="tx_sendsms_diagramm-text" style="color:'.$color.';font-size:'.$size.';">'.$text.'</span></div>';
	}

	/**
	 * Returns diagram
	 *
	 * @param	array		$arr: An array with data for diagram
	 * @param	array		$names: An array with names for axis X
	 * @param	array		$values: An array with data displaying above the columns
	 * @return	string		diagram's html code 
	 */
	public function draw($arr, $names = null, $values = null) {
		// Public variables to local variables (I don't whant to write always '$this->')
		$width = $this->width;
		$height = $this->height;
		$x0 = $this->x0;
		$y0 = $this->y0;
		$cx = $this->cx;
		$cy = $this->cy;
		$lx = $this->lx;
		$ly = $this->ly;
		$kx = $this->kx;
		$kv = $this->kv;
		$my = $this->my;
		$font_size = $this->font_size;
		$font_size_x = $this->font_size_x;
		$font_size_y = $this->font_size_y;
		$font_size_v = $this->font_size_v;
		$font_size_n = $this->font_size_n;
		$colorBG = $this->colorBG;
		$colorColumn = $this->colorColumn;
		$colorLine = $this->colorLine;
		$colorText = $this->colorText;
		$colorText2 = $this->colorText2;
		$axis_x_text = $this->axis_x_text;
		$axis_y_text = $this->axis_y_text;
		$value_text = $this->value_text;
		// Diagram begin
		$diagramm = '<div class="tx_sendsms_diagramm-border" style="border:1px solid black;width:' . $width . 'px;">';
		$diagramm.= '<div class="tx_sendsms_diagramm-inhalt" style="position:relative;left:0px;top:0px;width:'.$width.'px;height:'.$height.'px;background:'.$colorBG.';">';
		// Columns
		for($x = 0; $x < $cx; $x++) {
			// Writes names (captions) on x-axis
			$diagramm .= $this->line($x0 + $x * $lx, $height - $y0, $x0 + $x * $lx, $height - $y0 + 5, $colorLine);
			if ($names) {
				$diagramm .= $this->text($font_size_n, $kx + $x0 - 3 + $x * $lx, $height - $y0 + 5,  $names[$x], $colorText);
			} else {
				if ($x < 10) {
					$diagramm .= $this->text($font_size_x, $kx + $x0 - 3 + $x * $lx, $height - $y0+5,  $x, $colorText);
				} else {
					$diagramm .= $this->text($font_size_x, $kx + $x0 - 6 + $x * $lx, $height - $y0+5,  $x, $colorText);
				}
			}
			// Draws a new column (if it exists)
			if ($arr[$x]) {
				$diagramm .= $this->filledRectangle($x0 + 2 + $x * $lx, ceil($height - $y0 - $arr[$x] * $ly / $my), $x0 - 2 + $lx + $x * $lx, $height - $y0, $colorColumn);
				$k = 0;
				switch (strlen($arr[$x] . $value_text)) {
					case 1:
						$k = 7;
						break;
					case 2:
						$k = 4;
						break;
					case 3:
						$k = 2;
						break;
					case 4:
						$k = 0;
						break;
				}
				$diagramm .= $this->text($font_size_v, $kv + $k + $x0 - 2 + $x*$lx, ceil($height - $y0 - $arr[$x] * $ly / $my) - 15, $arr[$x] . $value_text, $colorText);
			}
			// Writes value over column
			if ($values) {
				if ($values[$x]) {
					$k = 0;
					switch (strlen($values[$x])) {
						case 1:
							$k = 3;
							break;
						case 2:
							$k = 1;
							break;
						case 3:
							$k = 0;
							break;
						case 4:
							$k = 0;
							break;
					}
					$diagramm .= $this->text($font_size_n, $k + $kv + $k + $x0 - 2 + $x * $lx, ceil($height - $y0 - $arr[$x] * $ly / $my), $values[$x], $colorText2);
				}
			}
		}
		// Horizontal axis
		$diagramm .= $this->line($x0, $height - $y0, $x0 + ($cx - 1) * $lx, $height - $y0, $colorLine);
		// Vertical axis
		$diagramm .= $this->line($x0, $y0, $x0, $height - $y0, $colorLine);
		for($x=0; $x < $cy; $x++) {
			$diagramm .= $this->line($x0 - 5, $height - $y0 - $ly * $x, $x0, $height - $y0 - $ly * $x, $colorLine);
			$diagramm .= $this->text($font_size_y, $x0 - 28, $height - 33 - $ly * $x, $x * $my, $colorText);
		}
		// Axes' names
		$diagramm .= $this->text($font_size, $width - 25, $height - 20,  $axis_x_text, $colorText);
		$diagramm .= $this->text($font_size, 18, 6,  $axis_y_text, $colorText);
		// Diagram's end
		$diagramm .= '</div>';
		$diagramm .= '</div>';
		// Ok
		return $diagramm;
	}
}
?>