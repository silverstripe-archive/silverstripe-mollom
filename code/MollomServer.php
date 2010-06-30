<?php
/** 
 * This class is responsible for caching a list of Mollom servers.
 * Call to Mollom class's method should go through Mollom::doCall
 *
 * @package spamprotection
 * @subpackage mollom
 */

class MollomServer extends DataObject {
	
	/**
	 * The name of the third-party library that communicate with Mollom with server
	 */
	static $library_class = "Mollom"; 
	
	/**
	 * The allowed maximum number of occurrences of a single call {@see self::doCall()}
	 */
	static $max_occurrences_num = 5;
	
	static $db = array(
		'ServerURL' => 'Varchar(255)'
	);
	
	static function getCachedServerList() {
		/**
		 * The order of servers is important http://mollom.com/api/getServerList
		 */
		$list = DataObject::get("MollomServer", '', '"ID" ASC');
		
		if ($list) {
			$serverArray = array();
			foreach ($list as $server) {
				$serverArray[] = $server->ServerURL;
			}
			return $serverArray;
		}
		
		return false;
	}
	
	protected function clearCachedServerList() {
		$list = DataObject::get("MollomServer");
		if ($list) {
			foreach ($list as $server) {
				$server->delete();
			}
		}
	}
	
	static function getServerList() {
		if($list = self::getCachedServerList()) return $list;
		
		$servers = self::doCall("getServerList", null);
		
		if (!$servers) return null;
		
		foreach ($servers as $server) {
			$mollomServer = new MollomServer();
			$mollomServer->ServerURL = $server;
			$mollomServer->write();
			$mollomServer->destroy();
		}
		
		return $servers;
	}
	
	static function verifyKey() {
		$valid = false; 
		
		try { 
			if(!self::getPublicKey() || !self::getPrivateKey()) return false;
			$valid =  self::doCall("verifyKey", null);
		}
		catch(Exception $e) {
			$valid = false; 
		}
		
		return $valid;
	}
	
	/**
	 * @param 	array of server urls
	 */
	static function initServerList() {
		return self::doCall("setServerList", array(self::getServerList())); 
	}
	
	static function getPublicKey() {
		return self::doCall("getPublicKey"); 
	}
	
	static function getPrivateKey() {
		return self::doCall("getPrivateKey"); 
	}
	
	static function setPublicKey($key) {
		$data = array($key); 
		self::doCall("setPublicKey", $data); 
	}
	
	static function setPrivateKey($key) {
		$data = array($key);
		self::doCall("setPrivateKey", $data); 
	}
	
	static function getImageCaptcha($sessionId) {
		$data = array($sessionId); 
		return self::doCall("getImageCaptcha", $data);
	}
	
	static function getAudioCaptcha($sessionId) {
		$data = array($sessionId); 
		return self::doCall("getAudioCaptcha", $data);
	}
	
	static function checkCaptcha($sessionId, $solution) {
		$data = array($sessionId, $solution);
		return self::doCall("checkCaptcha", $data);
	}

	static function checkContent($sessionId = null, $postTitle = null, $postBody = null, $authorName = null, $authorUrl = null, $authorEmail = null, $authorOpenId = null, $authorId = null) {
		$data = func_get_args();
		return self::doCall("checkContent", $data);
	}
	
	static function sendFeedback($sessionID, $feedback) {
		$data = array($sessionID, $feedback); 
		return self::doCall("sendFeedback", $data);
	}
	
	/**
	 * All mollom service method calls should go through this method 
	 * in order to try-and-catch and handle exceptions on one place 
	 * @param 	string 		name of the function 
	 * @param 	array 		array of the function's parameter 
	 * @param	int 		the number of occurrences of a method call
	 * @return 	mixed 
	 */
	protected static function doCall($name, $params=null, $occurrences = 0) {
		$lib = self::$library_class;
		
		if(!$params || !is_array($params)) $params = array($params);
		
		try {
			return call_user_func_array(array($lib, $name), $params);
		}
		catch (Exception $e) {
			$errCode = $e->getCode();
			switch ($errCode) {
				// if the cached serverlist is outdated
				case 1100:
					// delete cached server list first - in database
					singleton('MollomServer')->clearCachedServerList();
					
					// use default server list 
					$defaultServerList = call_user_func(array($lib, 'getServerList'));
					call_user_func_array(array($lib, 'setServerList'), array($defaultServerList));
					
					if(self::$max_occurrences_num > $occurrences) {
						// call the function with the default server list set above
						return self::doCall($name, $params, ++$occurrences);
					}
					
					break;
					
				default:
					throw new Exception( $e->getMessage() , $errCode);
			}			
		}
	}	
}