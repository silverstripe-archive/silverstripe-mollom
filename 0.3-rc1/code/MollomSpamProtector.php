<?php
/**
 * Mollom Spam Protector that implements spam protection.
 * 
 * @package spamprotection
 * @subpackage mollom
 */

class MollomSpamProtector implements SpamProtector {

	/**
	 * Return the Field that we will use in this protector
	 * 
	 * @return MollomField
	 */
	function getFormField($name = "MollomField", $title = "Captcha", $value = null, $form = null, $rightTitle = null) {
		
		// load servers. Needs to be called before validKeys() 
		MollomServer::initServerList();
		
		return new MollomField($name, $title, $value, $form, $rightTitle);
	}
	
	/**
	 * Send Feedback about a Object to the Mollom Service. Note that Mollom does not
	 * want to know about ham (or valid entries) so the only valid feedback is what
	 * level of spam it is, Which we currently do not support.
	 * 
	 * @param DataObject The DataObject which you want to send feedback about
	 * @param String Feedback information
	 * 
	 * @return bool Whether feedback was sent
	 */
	function sendFeedback($object = null, $feedback = "") {	
		if($object) {
			if($object->hasField('SessionID')) {
				if(in_array($feedback, array('spam', 'profanity', 'low-quality', 'unwanted'))) {
					MollomServer::initServerList();
					return MollomServer::sendFeedback($object->SessionID, $feedback);
				}
			}
		}
		return false;
	}
}