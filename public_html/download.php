<?php


require_once(__DIR__ .'/../_app/core.php' );

/*
 * The media library now stores uploaded files using number-based files (on the 
 * server's filesystem), which makes a lot of logic way easier.  However, nobody 
 * really wants to download a PDF called "24.pdf", because they won't remember 
 * what that is; likewise, if this just echoes the contents out, saving it will 
 * create the file as "download.php.pdf"... not good.
 * 
 * By using a URL like '/download/general-cleaning.pdf', the filename they save 
 * (general-cleaning.pdf) will make more sense.
 */


use crazedsanity\core\ToolBox;
use cms\cms\core\media;
//ToolBox::$debugPrintOpt = 1;


debugPrint($_GET, "URL params");
debugPrint($_SERVER, "SERVER info");

//exit(__FILE__);
//
if(debugPrint($_GET, "GET vars")) {
	ini_set('display_errors', true);
}

debugPrint(PUBLIC_MEDIA_DIR, "path to public media files");
debugPrint(MEDIA_DIR, "full path to media files");


$pathPrefix =  preg_replace('~(/+)~', '/', MEDIA_DIR);
$fullPath = null;

$forceDownload = null;
$foundIt = false;
$headers = array();
$size = null;
$quotedFilename = null;

if(!empty($_GET['file'])) {
	// force download, if the file exists.
	$forceDownload = true;
	$pathInfo = pathinfo($_GET['file']);
	debugPrint($pathInfo, "path info");
	
	if(($pathInfo['dirname'] === '.' || empty($pathInfo['dirname'])) && !empty($pathInfo['extension'])) {
	
	
		$fullPath = preg_replace('~(/+)~', '/', $pathPrefix .'/'. $_GET['file']);
		debugPrint($fullPath, "full path to file");
		$checkFileExists = file_exists($fullPath);
		debugPrint($checkFileExists, "file exists check");
		$isFileCheck = is_file($fullPath);
		debugPrint($isFileCheck, "check if the path is a file");
		
		if($checkFileExists && $isFileCheck) {
			if(is_numeric($pathInfo['filename'])) {
				debugPrint($pathInfo, "filename is numeric");
				$mObj = new media($db);
				$fileData = $mObj->getByFilename($_GET['file']);
				debugPrint($fileData, "file data");

				$useName = generateSlug($fileData['display_filename']) .'.'. $pathInfo['extension'];
				if(empty($useName)) {
					$useName = $fileData['filename'];
				}
				$quoted = $useName;
			}
			else {
				$quoted = generateSlug($pathInfo['filename']) .".". $pathInfo['extension'];
			}
			
			$quotedFilename = sprintf('"%s"', addcslashes($quoted, '"\\'));
			
			
			if(debugPrint($quoted, "quoted filename")) {
				exit(__FILE__ ." - line #". __LINE__);
			}
			
			$headers[]	= 'Content-Description: File Transfer';
			$headers[]	= 'Content-Type: application/octet-stream';
			$headers[]	= 'Content-Transfer-Encoding: binary';
			$headers[]	= 'Content-Disposition: attachment; filename=' . $quoted; 
//			}
			$foundIt = true;
		}
	}
	
}
elseif(!empty($_GET['_realpath']) && preg_match('~^/download/~', $_SERVER['REQUEST_URI']) == 1) {
	
	debugPrint($_GET['_realpath'], "USING APACHE REWRITE");
	
	// don't force it (let the browser decide)
	$forceDownload = false;
	
	$fileBits = explode('__', $_GET['_realpath']);
	
	
	if(count($fileBits) == 2) {
		$fullPath = preg_replace('~(/+)~', '/', $pathPrefix .'/'. $fileBits[1]);
		debugPrint($fullPath, "full path to file");
		$checkFileExists = file_exists($fullPath);
		debugPrint($checkFileExists, "file exists check");
		$isFileCheck = is_file($fullPath);
		debugPrint($isFileCheck, "check if the path is a file");
		
		
		$mObj = new media($db);
		$fileData = $mObj->getByFilename($fileBits[1]);
		debugPrint($fileData, "file data");
		
		if($checkFileExists && $isFileCheck && is_array($fileData) && !empty($fileData['filetype'])) {
			$headers[] = 'Content-Type: '. $fileData['filetype'];
			$foundIt = true;
		}
		
		
		
		
//		$headers[]	= 'Content-Disposition: attachment; filename=' . $quoted; 
	}
	
//	debugPrint();
}



debugPrint($forceDownload, "force download value");
debugPrint($foundIt, "Found it value");

//exit(__FILE__ ." - line #". __LINE__);


if(!is_null($forceDownload) && $foundIt == true) {
	// got what we need.
	$size = filesize($fullPath);
	
			if(debugPrint("You're debugging, no headers for you.")) {
				debugPrint($headers, "headers that would have been sent");
				exit(__FILE__ ." - line #". __LINE__);
			}
			
	// push out the headers.
	foreach($headers as $header) {
		header($header);
	}
	
	header('Connection: Keep-Alive');
	header('Expires: 0');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Pragma: public');
	header('Content-Length: ' . $size);
	
	echo file_get_contents($fullPath);
}
else {
	// Nothing to do.  Tell 'em the file could not be found (sort of protects this script).
	header("HTTP/1.0 404 Not Found");
}
exit;

