<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace cms\mailchimp;

/**
 * Description of mailchimp
 *
 * @author danf
 */
class mailchimp {
	
	const STATUS_OK = true;
	const STATUS_ALREADY_SUBSCRIBED = 1;
	
	/**
	 * Send subscription request to Mailchimp.
	 * 
	 * @param string $apiKey
	 * @param string $listID
	 * @param array $subscriberData
	 * 
	 * @return (bool) true:			Subscribed successfully
	 * @return (string) (mixed):	An error occurred (string contains more information).
	 */
	public static function subscribe($apiKey, $listID, array $subscriberData) {
		
		$returnCode = null;
		
		$fname = $subscriberData['fname'];
		$lname = $subscriberData['lname'];
		$email = $subscriberData['email'];
		
		
		unset($subscriberData['fname'],$subscriberData['lname'], $subscriberData['email']);
		$subscriberData['FNAME'] = $fname;
		$subscriberData['LNAME'] = $lname;
		
		
		if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
			
			debugPrint($email, __METHOD__ ." - email");

			// MailChimp API URL
			$memberID = md5(strtolower($email));
			$dataCenter = substr($apiKey, strpos($apiKey, '-') + 1);
			$url = 'https://' . $dataCenter . '.api.mailchimp.com/3.0/lists/' . $listID . '/members/' . $memberID;
			debugPrint($url, __METHOD__ ." - url");
			

			// member information
			$json = json_encode([
				'email_address' => $email,
				'status' => 'subscribed',
				'merge_fields' => $subscriberData,
			]);
			debugPrint($json, __METHOD__ ." - JSON");

			// send a HTTP POST request with curl
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_USERPWD, 'user:' . $apiKey);
			curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_TIMEOUT, 10);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
			$result = curl_exec($ch);
			debugPrint($result, __METHOD__ ." - result from cURL");
			$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			debugPrint($httpCode, __METHOD__ ." - code returned from Mailchimp");
			curl_close($ch);
			

			// store the status message based on response code
			if ($httpCode == 200) {
				$returnCode = true;
			} else {
				switch ($httpCode) {
					case 214:
						$returnCode = 'You are already subscribed.';
						break;
					default:
						$returnCode = 'Some problem occurred, please try again.';
						break;
				}
			}
		} else {
			$returnCode = 'invalid email address';
		}
		
		return $returnCode;
	}

}
