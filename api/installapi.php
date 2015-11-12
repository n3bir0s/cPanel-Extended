<?php
//  CyanDark Incorporated
//  Copyright (c) 2012-2016 CyanDark, Inc. All Rights Reserved.
//
//  This software is furnished under a license and may be used and copied
//  only  in  accordance  with  the  terms  of such  license and with the
//  inclusion of the above copyright notice.  This software  or any other
//  copies thereof may not be provided or otherwise made available to any
//  other person.  No title to and  ownership of the  software is  hereby
//  transferred.
//
//  You may not reverse  engineer, decompile, defeat  license  encryption
//  mechanisms, or  disassemble this software product or software product
//  license. CyanDark may terminate this license if you don't comply with
//  any of the  terms  and conditions  set  forth in our end user license
//  agreement (EULA).  In such event, licensee  agrees to return licensor
//  or  destroy all copies  of  software  upon termination of the license

function softaculous_scripts(){
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, 'http://api.softaculous.com/scripts.php?in=serialize');
	curl_setopt($ch, CURLOPT_VERBOSE, 1);

	// Turn off the server and peer verification (TrustManager Concept).
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

	// Follow redirects
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);

	// Check the Header
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

	// Get response from the server.
	$file = curl_exec($ch);

	// Did we get the file ?
	if($file === false){
		die('Could not load list of scripts. cURL Error : '.curl_error($ch));
	}

	curl_close($ch);
	$scripts = unserialize($file);
	return $scripts;
}


class Soft_Install{
	// The Login URL
	var $login = '';
	var $debug = 0;

	// THE POST DATA
	var $data = array();

	function install($sid){

		define('SOFTACULOUS', 1);

		// Include the Scripts List
		//@include('http://www.softaculous.com/scripts.php');

		$scripts = softaculous_scripts();

		// You should place the cscripts.php here with this file.
		// We should also include the Custom Scripts if any
		if(file_exists('cscripts.php')){
			include('cscripts.php');
			$scripts += $cscripts;
		}

		if(empty($scripts[$sid])){
			return false;
		}

		$tmp_login = parse_url($this->login);

		// This is to set the cookie for Directadmin
		if($tmp_login['port'] == '2222'){

			$cmd_login = $tmp_login['scheme'].'://'.$tmp_login['host'].':'.$tmp_login['port'].'/CMD_LOGIN';

			$post = array('username' => $tmp_login['user'],
					'password' => $tmp_login['pass'],
					'referer' => '/');

			$res = $this->curl_call($cmd_login, 1, $post);

			// Did we login ?
			if($res === false){
				die('Could not login to the remote server. cURL Error : '.$res);
				return false;
			}

			$res = explode("\n", $res);

			// Find the cookies
			foreach($res as $k => $v){
				if(preg_match('/^'.preg_quote('set-cookie:', '/').'(.*?)$/is', $v, $mat)){
					$this->cookie= trim($mat[1]);
				}
			}
		}

		// Add the ?
		if(!strstr($this->login, '?')){
			$this->login = $this->login.'?';
		}

		// Login PAGE
		if($scripts[$sid]['type'] == 'js'){
			$this->login = $this->login.'&act=js&soft='.$sid;
		}elseif($scripts[$sid]['type'] == 'perl'){
			$this->login = $this->login.'&act=perl&soft='.$sid;
		}elseif($scripts[$sid]['type'] == 'java'){
			$this->login = $this->login.'&act=java&soft='.$sid;
		}else{
			$this->login = $this->login.'&act=software&soft='.$sid;
		}

		$this->login = $this->login.'&autoinstall='.rawurlencode(base64_encode(serialize($this->data)));

		if(!empty($this->debug)){
			return $this->data;
		}

		$resp = $this->curl_call($this->login, 0, array(), $this->cookie);

		if($resp != 'installed') {
			return $resp;
		}

		return true;

	}

	function curl_call($url, $header = 1, $post = array(), $cookie = ''){

		global $globals;

		// Set the curl parameters.
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_VERBOSE, 1);

		// Turn off the server and peer verification (TrustManager Concept).
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

		if($this->panel == 'isp'){
			//curl_setopt($ch, CURLOPT_SSLVERSION,3); // We have removed this because of a vulnerability in SSLv3
			curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:2.0.1) Gecko/20100101 Firefox/4.0.1');
			curl_setopt($ch, CURLOPT_REFERER, $url);
		}

		// Follow redirects
		//curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE); // This does not work with DA hence we have commented this

		if(!empty($post)){
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
		}

		// Is there a Cookie
		if(!empty($cookie)){
			curl_setopt($ch, CURLOPT_COOKIESESSION, true);
			curl_setopt($ch, CURLOPT_COOKIE, $cookie);
		}

		if($header){
			curl_setopt($ch, CURLOPT_HEADER, 1);
		}
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		// Get response from the server.
		$resp = curl_exec($ch);
		if($resp === false){
			$ch_err = curl_error($ch);
			curl_close($ch);
			return $ch_err;
		}
		curl_close($ch);
		return $resp;
	}
}

?>
