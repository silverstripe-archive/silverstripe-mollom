<?php
/** 
 * Backend Report Interface for Mollom Status
 *
 * @package spamprotection
 * @subpackage mollom
 */

class MollomAdmin extends LeftAndMain {
	
	static $url_segment = "mollom";
	
	static $menu_title = "Mollom";

	static $menu_priority = -0.5;

	function init() {
		MollomServer::initServerList();

		return parent::init();
	}

	function getPublicKey() {
		return MollomServer::getPublicKey();
	}

	function isKeyVerified() {
		return MollomServer::verifyKey();
	}
}
