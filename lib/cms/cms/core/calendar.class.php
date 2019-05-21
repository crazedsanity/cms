<?php

namespace cms\cms\core;


use \Exception;
use \InvalidArgumentException;

class calendar extends core {
	
	protected $_isEditable=false;
	
	public $colorArray = array(
		'd65b5b', 'd57796', 'b75eb7', '855bd6', '4785c1',
		'5b85d6', '32d6c1', '43bf80', '16d421', '8fed00',
		'e4e417', 'ffd211', 'ffa224', 'ef7335', 'b59090',
		'a389a3', '7e90a1', '909db5', '79a7a1', 'a8a86c',
		'bda17c', 'e85560', 'be2445', 'b220aa', '462c9d',
		'2e54a6', 'c01abd', '22ae22', '57b723', '5bc011',
		'b7cf30', 'ea8612', 'ca7e26', 'cd6170', 'b02272',
		'8c229d', '248899', '666666', '209f77', 'e5aed0',
		'c62626', 'e691f0', 
	);
	
	public function __construct($db) {
		$this->db = $db;
		parent::__construct($db, 'calendars', 'calendar_id', 'title');
	}
	
	
	public function setIsEditable($newVal) {
		if($newVal === true) {
			$this->_isEditable = $newVal;
		}
		else {
			$this->_isEditable = false;
		}
		
		return $this->_isEditable;
	}
	
	
	function rgbToHsl( $hexColor ) {
		$r = round( hexdec( substr( $hexColor, 0, 2 ) ) / 255.0, 6 );
		$g = round( hexdec( substr( $hexColor, 2, 2 ) ) / 255.0, 6 );
		$b = round( hexdec( substr( $hexColor, 4, 2 ) ) / 255.0, 6 );


		$max = max( $r, $g, $b );
		$min = min( $r, $g, $b );

		$h = null;
		$s = null;
		$l = ( $max + $min ) / 2;
		$d = $max - $min;

		if($d == 0) {
			$h = $s = 0; // achromatic
		}
		else {
			$s = $d / ( 1 - abs( 2 * $l - 1 ) );
			switch($max) {
				case $r:
					$h = 60 * fmod( ( ( $g - $b ) / $d ), 6 ); 
						if ($b > $g) {
						$h += 360;
					}
					break;

				case $g: 
					$h = 60 * ( ( $b - $r ) / $d + 2 ); 
					break;

				case $b: 
					$h = 60 * ( ( $r - $g ) / $d + 4 ); 
					break;
			}                                
		}

		return array(
			'h' => $h,
			's' => $s,
			'l' => $l );
	}

	function hslToRgb( $hslColor ) {
		$c = ( 1 - abs( 2 * $hslColor['l'] - 1 ) ) * $hslColor['s'];
		$x = $c * ( 1 - abs( fmod( ( $hslColor['h'] / 60 ), 2 ) - 1 ) );
		$m = $hslColor['l'] - ( $c / 2 );

		if ( $hslColor['h'] < 60 ) {
			$r = $c;
			$g = $x;
			$b = 0;
		} else if ( $hslColor['h'] < 120 ) {
			$r = $x;
			$g = $c;
			$b = 0;            
		} else if ( $hslColor['h'] < 180 ) {
			$r = 0;
			$g = $c;
			$b = $x;                    
		} else if ( $hslColor['h'] < 240 ) {
			$r = 0;
			$g = $x;
			$b = $c;
		} else if ( $hslColor['h'] < 300 ) {
			$r = $x;
			$g = 0;
			$b = $c;
		} else {
			$r = $c;
			$g = 0;
			$b = $x;
		}

		$r = floor( ( $r + $m ) * 255.0 );
		$g = floor( ( $g + $m ) * 255.0 );
		$b = floor( ( $b + $m ) * 255.0 );

		return str_pad( dechex( $r ), 2, '0' ) . str_pad( dechex( $g ), 2, '0' ) . str_pad( dechex( $b ), 2, '0' );
	}

	/**
	 * this just rotates the hue by 180 deg in the hsl color space to find a 
	 * complementary color
	 * 
	 * @param type $hexColor
	 * @return type
	 */
	function getComplementaryColorHex( $hexColor ) {
		$hsl = $this->rgbToHsl( $hexColor );

		$hsl['h'] += 180;
		if ( $hsl['h'] > 360 ) {
			$hsl['h'] -= 360;
		}
		return $this->hslToRgb( $hsl );
	}

	
	/**
	 * this just bumps up the luminosity of the color in the hsl color space
	 * 
	 * @param type $hexColor
	 * @param type $percent
	 * @return type
	 */
	function getLightColorHex( $hexColor, $percent = 30 ) {
		$hsl = $this->rgbToHsl( $hexColor );

		$hsl['l'] += ( $percent / 100.0 );
		if ( $hsl['l'] > 1 ) $hsl['l'] = 1;
		return $this->hslToRgb( $hsl );
	}

	/**
	 * this just nudges down the luminosity of the color in the hsl color space
	 * 
	 * 
	 * @param type $hexColor
	 * @param type $percent
	 * @return type
	 */
	function getDarkColorHex( $hexColor, $percent = 30 ) {
		$hsl = $this->rgbToHsl($hexColor);

		$hsl['l'] -= ( $percent / 100.0 );
		if ( $hsl['l'] < 0 ) {
			$hsl['l'] = 0;
		}
		return $this->hslToRgb($hsl);
	}

	function generateCalStyles($colorArray=null) {
		if(!is_array($colorArray) || (is_array($colorArray) && count($colorArray) == 0)) {
			$colorArray = $this->colorArray;
		}
		
		$styles = '';
		foreach ( $colorArray as $primaryColor ) {

			$textColor = $this->getDarkColorHex( $primaryColor, 5 );
			$secondaryColor = $this->getLightColorHex( $primaryColor, 30 );

			$styles .= <<<CSS
				/* FC Events */
				.C{$primaryColor},
				.C{$primaryColor} a {
					border: 1px solid #{$primaryColor};	
				}
				.C{$primaryColor},
				.C{$primaryColor} .fc-event-time {
					background: #{$primaryColor};
				}
				.C{$primaryColor} .fc-event-title,
				.C{$primaryColor} a,
				.C{$primaryColor} a .fc-event-bg,
				.C{$primaryColor} .fc-event-title a,
				.colors .item.CC{$primaryColor} {
					background: #{$secondaryColor};
				}

				/* Calendar item list */
				.colors {
					margin-top:10px;
					width: 164px;
					margin-left: 26px;
				}
				.colors .item.CC{$primaryColor} .one {
					background: #{$secondaryColor};
					display:block;
					border: 1px solid #{$primaryColor};
					border-radius:3px;
					margin-top:4px;
					padding:5px;
					font-weight:bold;
				}
				.colors .item.CC{$primaryColor} .one a {
					color: #{$textColor};
				}

				.ui-menu { position: absolute; width: 100px; z-index:2; }

CSS;
		}
		
		return $styles;
	}
}