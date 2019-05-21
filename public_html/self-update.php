<?php

ob_start();
/* 
 * NOTE: this adds a potentially unnecessary external dependency, which could 
 * possibly break the script (e.g. if the "readConfig()" function gets removed, 
 * or if one of the scripts required by core.php becomes broken).
 */
require_once(__DIR__ ."/../_app/core.php");

if(!function_exists('readConfig')) {
	function readConfig() {

		$useIni = false;
		if(file_exists(__DIR__ . '/../config/siteconfig-dev.ini')) {
			$useIni = parse_ini_file(__DIR__ . '/../config/siteconfig-dev.ini',true);
		}
		elseif(file_exists(__DIR__ . '/../config/siteconfig.ini')) {
			$useIni = parse_ini_file(__DIR__ . '/../config/siteconfig.ini', true);
		}

		return $useIni;
	}
}

$iniData = readConfig();
ob_end_clean();
ob_implicit_flush();


if($iniData['self-update']['key'] === $_GET['key']) {

	header("Cache-Control: no-cache");
	header("Cache-Control: private");
	header("Pragma: no-cache");
	header("Content-type: text/plain");

	error_reporting(-1);
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);

	$command = "cd ../ && /usr/bin/git status 2>&1 && echo '======================' && /usr/bin/git pull 2>&1";

	// get the home directory and ensure no weirdness.
	$homeDir = getenv("HOME");
	if(preg_match('~^/root~', $homeDir)) {
		$command = "export HOME=/var/www ; ". $command;
	}
	else {
		$homeDir = shell_exec('pwd');
	}


	$fullOutput = array();
	$exitCode = null;
	exec($command, $fullOutput, $exitCode);

	$fullHostname = strtolower(shell_exec("hostname"));
	$bits = explode('.', $fullHostname);
	$hostname = $bits[0];


	//$output = shell_exec("cd ../ && git pull");
	$details = "";
	$details .= "============= [ START ] ============\n";
	$details .= "using hostname: ". $hostname ."\n";
	$details .= "COMMAND: ". $command ."\n";
	$details .= "EXIT CODE=(". $exitCode .")\n";
	$details .= "--------------------------\n";
	$details .= "result of pull: ". implode("\n", $fullOutput) ."\n";
	$details .= "============ [ FINISH ] ============\n";

	echo $details;
	
	// if there's a configured email address, send info to them.
	if(!empty($iniData['self-update']['email'])) {
		$subject = "self-update[". $exitCode ."] - ". $hostname .": ". $homeDir;
		$extraHeaders = 'From: autodeploy@cms.com' . "\r\n" .
				'X-Mailer: PHP/'. phpversion();
		$emailRes = mail($iniData['self-update']['email'], $subject, $details, $extraHeaders);
		
		echo "EMAIL RESULT: ". $emailRes;
	}
}
else {
	// They didn't send enough information: don't let 'em know this page exists!
	header("HTTP/1.0 404 Not Found");
}