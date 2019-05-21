<?php
require_once('../../_app/core.php');
require_once(ROOT . '/_app/classes/aviary.class.php');
/*
$clean['fileguid']='62c5986a-69c0-11e0-9d2c-12313916f267';
$clean['name']='doctor_2.jpg.egg';
$clean['imageurl']='http://www.aviary.com/getfile?fguid=62c5986a-69c0-11e0-9d2c-12313916f267&getegg=0';
$clean['thumbnail']='http://rookery9.aviary.com.s3.amazonaws.com/7683000/7683321_3a7d_sqr.jpg';
$clean['description']='';
$clean['tags']='';
$clean['userhash']='45';
$clean['tool']='phoenix';
*/

if (isset($clean['tool'])) {
	$aviary = new aviary($clean['tool'], $settings->get('aviary_id', 'cms')); ##Create new object
	$aviary->fullPath = MEDIA_DIR; ##Set to path where you want to save the data
	$file=$aviary->saveFile($clean,$aviary->fullPath); ##Insert array of data sent via POST from Aviary servers.	
	$fileName=str_replace($aviary->fullPath,'',$file);
	$editor=$aviary->getEditor();
	
	if(!isset($clean['userhash'])){
		$clean['userhash']=0;
	}
	
	
	if (isset($clean['userhash']) && $clean['userhash'] != '') {
		#update image
		#update media
		$finfo = new finfo(FILEINFO_MIME_TYPE);
		$filetype=$finfo->file($file);
		$filesize=filesize($file);
		$query=sprintf("INSERT INTO `media` 
			(`filename`,`filetype`,`filesize`) VALUES ('%s','%s','%s')",
			$fileName,$filetype,$filesize);
		if($clean['userhash']>0){
			#we use media_id from media table in userhash field. If userhash is greater than zero, we should be trying to update an image instead
			$query=sprintf("UPDATE `media` SET `filename`='%s', `filetype`='%s', `filesize`='%s' WHERE `media_id`=%d",
				$fileName,$filetype,$filesize,$clean['userhash']);
		}
		$db->query($query);
		$lastID=mysql_insert_id();
		
		if($lastID>0 && $lastID!=$clean['userhash']){
			#when new media is inserted
			$clean['userhash']=$lastID;	
		}
		
		
		#insert/update aviary_media
		$query=sprintf("INSERT INTO `aviary_media` 
			(`media_id`,`fileguid`,`tool`,`tmp_url`) VALUES (%d,'%s','%s','%s')
			ON DUPLICATE KEY UPDATE `tmp_url`='%s', `fileguid`='%s', `tool`='%s'",
			$clean['userhash'],$clean['fileguid'],$editor['properName'],$aviary->getFile(),$aviary->getFile(),$clean['fileguid'],$editor['properName']);
		$db->query($query);
		
	}
}
?>