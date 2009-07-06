<?php

/**
 * SpamProtector that implements Mollom spam protection
 */
class MollomSpamProtector implements SpamProtector {
	
	protected $mollomField;
	
	/**
	 * Return the Field that we will use in this protector
	 * 
	 * @return string
	 */
	function getFieldName() {
		return 'MollomField';
	}
	
	/**
	 * Update a form with the Mollom Field Protection
	 * 
	 * @return bool 
	 */
	function updateForm($form, $before=null, $fieldsToSpamServiceMapping=null) {
		// check mollom keys before adding field to form
		MollomServer::initServerList();
		if (!MollomServer::verifyKey()) return false;
		
		$this->mollomField = new MollomField("MollomField", "Captcha", null, $form);

		if ($before && $form->Fields()->fieldByName($before)) {
			$form->Fields()->insertBefore($this->mollomField, $before);
		}
		else {
			$form->Fields()->push($this->mollomField);
		}
		
		return $form->Fields();
	}
	
	function setFieldMapping($fieldToPostTitle, $fieldsToPostBody=null, $fieldToAuthorName=null, $fieldToAuthorUrl=null, $fieldToAuthorEmail=null, $fieldToAuthorOpenId=null) {
		$this->mollomField->setFieldMapping($fieldToPostTitle, $fieldsToPostBody, $fieldToAuthorName, $fieldToAuthorUrl, $fieldToAuthorEmail, $fieldToAuthorOpenId);
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
					return Mollom::sendFeedback($object->SessionID, $feedback);
				}
			}
		}
		return false;
	}
}

?>