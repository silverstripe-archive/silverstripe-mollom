<?php
/** 
 * This class is responsible for caching a list of Mollom servers.
 * Call to Mollom class's method should go through Mollom::doCall
 *
 * @package spamprotection
 * @subpackage mollom
 */

class MollomServer extends DataObject {
	static $db = array(
		'ServerURL' => 'Varchar(255)'
	);
	
	static function getCachedServerList() {
		$list = DataObject::get("MollomServer");
		
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
		return self::doCall("verifyKey", null);
	}
	
	/**
	 * @param 	array of server urls
	 */
	static function initServerList() {
		return self::doCall("setServerList", array(self::getServerList())); 
	}
	
	static function getImageCaptcha($data) {
		return self::doCall("getImageCaptcha", $data);
	}
	
	static function getAudioCaptcha($data = array()) {
		return self::doCall("getAudioCaptcha", $data);
	}
	
	static function checkCaptcha($data = array()) {
		return self::doCall("checkCaptcha", $data);
	}
	
	static function checkContent($data = array()) {
		return self::doCall("checkContent", $data);
	}
	
	/**
	 * All mollom service method calls should go through this method 
	 * in order to try-and-catch and handle exceptions on one place 
	 * @param 	string 		name of the function 
	 * @param 	array 		array of the function's parameter 
	 * @return 	mixed 
	 */
	protected static function doCall($name, $params=null) {
		try {
			return call_user_func_array(array('Mollom',$name), $params);
		}
		catch (Exception $e) {
			$errCode = $e->getCode();
			switch ($errCode) {
				// if the cached serverlist is outdated
				case 1100:
					// delete cached server list first - in database
					singleton('MollomServer')->clearCachedServerList();
					// use default server list 
					Mollom::setServerList(Mollom::getServerList());
					break;
					
				default:
					throw new Exception( $e->getMessage() , $errCode);
			}			
		}
	}	
}