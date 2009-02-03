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
}
?>