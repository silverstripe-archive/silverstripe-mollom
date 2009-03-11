<?php
/** 
 * This class is responsible for caching a list of Mollom servers
 * @NOTE: Call to Mollom class's method should go through Mollom::doCall
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
		if (self::getCachedServerList()) return self::getCachedServerList();
		
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
		self::doCall("setServerList", array(self::getServerList())); 
	}
	
	static function getImageCaptcha($sessionId=null) {
		return self::doCall("getImageCaptcha", array($sessionId));
	}
	
	static function getAudioCaptcha($sessionId=null) {
		return self::doCall("getAudioCaptcha", array($sessionId));
	}
	
	static function checkCaptcha($sessionId, $solution) {
		return self::doCall("checkCaptcha", array($sessionId, $solution));
	}
	
	static function checkContent($session_id=null, $postTitle=null, $postBody=null, $authorName=null, $authorUrl=null, $authorEmail=null, $authorOpenId=null) {
		return self::doCall("checkContent", array($session_id, $postTitle, $postBody, $authorName, $authorUrl, $authorEmail, $authorOpenId));
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
			return call_user_func_array( 'Mollom::' . $name, $params);
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
?>