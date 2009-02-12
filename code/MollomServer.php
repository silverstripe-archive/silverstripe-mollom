<?php
/** 
 * This class is responsible for caching a list of Mollom servers
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
	
	static function getServerList() {
		if (self::getCachedServerList()) return self::getCachedServerList();
		
		$servers = Mollom::getServerList();
		foreach ($servers as $server) {
			$mollomServer = new MollomServer();
			$mollomServer->ServerURL = $server;
			$mollomServer->write();
			$mollomServer->destroy();
		}
		
		return $servers;
	}
	
	/**
	 * @param 	array of server urls
	 */
	static function initServerList() {
		Mollom::setServerList(self::getServerList()); 
	}
	
	static function getImageCaptcha($sessionId=null) {
		return Mollom::getImageCaptcha($sessionId);
	}
	
	static function getAudioCaptcha($sessionId=null) {
		return Mollom::getAudioCaptcha($sessionId);
	}
	
	static function checkCaptcha($sessionId, $solution) {
		return Mollom::checkCaptcha($sessionId, $solution);
	}
	
	static function checkContent($session_id=null, $postTitle=null, $postBody=null, $authorName=null, $authorUrl=null, $authorEmail=null, $authorOpenId=null) {
		return Mollom::checkContent($session_id, $postTitle, $postBody, $authorName, $authorUrl, $authorEmail, $authorOpenId);
	}
}
?>