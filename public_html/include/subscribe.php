<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
 require_once(__DIR__ .'/../../_app/core.php');
 
 use cms\mailchimp\mailchimp;
 use crazedsanity\core\ToolBox;
 
 $x = new mailchimp();
 
// ToolBox::$debugPrintOpt = 1;
 
 $res = 'empty';
 $json = array();
// debugPrint($_POST, "POSTed data",1);
 if(!empty($_POST['widget-subscribe-form-email'])) {
	 $subscriberData = array(
		 'email'	=> $_POST['widget-subscribe-form-email'],
	 );
	 $res = $x->subscribe('864c8f2f5f62cdc08e9ab6043d569264-us1', 'd7342ab63c', $subscriberData);
//	 debugPrint($res, "result");
	 $json['message'] = "Subscribe success (". $res .")";
 }
 
 echo json_encode($json);
 exit;
 
// ToolBox::conditional_header('/?subscribed='. $res);
 
