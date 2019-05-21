<?php



require_once(__DIR__ .'/../../_app/core.php');

//if(preg_match('~/~', $clean['i']) === 1) {
//	error_reporting(E_ALL);
//	ini_set('display_errors', true);
//	$clean['i'] = basename($clean['i']);
//}
	/**
	 * PhpThumb Library Example File
	 *
	 * This file contains example usage for the PHP Thumb Library
	 *
	 * PHP Version 5 with GD 2.0+
	 * PhpThumb : PHP Thumb Library <http://phpthumb.gxdlabs.com>
	 * Copyright (c) 2009, Ian Selby/Gen X Design
	 *
	 * Author(s): Ian Selby <ian@gen-x-design.com>
	 *
	 * Licensed under the MIT License
	 * Redistributions of files must retain the above copyright notice.
	 *
	 * @author Ian Selby <ian@gen-x-design.com>
	 * @copyright Copyright (c) 2009 Gen X Design
	 * @link http://phpthumb.gxdlabs.com
	 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
	 * @version 3.0
	 * @package PhpThumb
	 * @subpackage Examples
	 * @filesource
	 */
	if ( !empty( $_GET['i'] ) ) {
	$clean['i'] = basename($_GET['i']);
	$mysql['i']=@mysql_escape_string($_GET['i']);

	if($_GET['x']){
		$clean['x']=$_GET['x'];
		$mysql['x']=@mysql_escape_string($_GET['x']);
	} else {
			$clean['x'] = 100;
		$mysql['x']=100;
		}
	if($_GET['y']){
		$clean['y']=$_GET['y'];
		$mysql['y']=@mysql_escape_string($_GET['y']);
	} else {
			$clean['y'] = 100;
		$mysql['y']=100;
		}

		$filename 	= array_reverse( explode( '.', $clean['i'] ) );
		$ext 		= $filename[0];
		$filename 	= array_reverse( $filename );
		array_pop( $filename );
		$filename 	= implode( '.', $filename );

	$clean['thumb'] = 'th.'.$filename.'-'.$clean['x'].'x'.$clean['y'].'.'.$ext;
} else {
		exit();
	}
	
	
		
if($ext == 'pdf') {
	// check if there's an existing thumbnail
	$originalFile = MEDIA_DIR .'/'. $clean['i'];
	if(file_exists($originalFile)) {
		$thumbFile = preg_replace('~pdf$~', 'jpg', $clean['i']);
		$thumbPath = MEDIA_DIR .'/'. $thumbFile;
		if(!file_exists($thumbPath)) {
			// create the thumbnail
			$cmd = 'convert "'. $originalFile .'[0]" -colorspace RGB -geometry 300x300 -flatten "'. $thumbPath .'"';
			$res = exec($cmd, $output);
			$clean['i'] = $thumbFile;
		}
		else {
			$clean['i'] = $thumbFile;
		}
	}
	else {
		exit;
	}
}


$path = MEDIA_DIR . '/' . $clean['i'];
try {
	$thumb = PhpThumbFactory::create($path);
} catch (Exception $e) {
	applicationLog('thumb.php', $ex->getMessage());
//	echo $e;
	exit();
}

if ( isset( $_GET['adaptive'] ) ) {
	$thumb->adaptiveResize( $clean['x'], $clean['y'] );
}
else {
	$thumb->resize( $clean['x'], $clean['y'] );
}

$thumb->show();

