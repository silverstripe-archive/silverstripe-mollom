<?php

/**
 * SpamProtector that implements Mollom spam protection
 */
class MollomSpamProtector {
	protected $mollomField;
	
	function updateForm($form, $before=null, $callbackObject=null, $fieldsToSpamServiceMapping=null) {
		$this->mollomField = new MollomField("MollomField", "Captcha", null, $form);
		$this->mollomField->setCallbackObject($callbackObject);
		
		if ($before && $form->Fields()->fieldByName($before)) {
			$form->Fields()->insertBefore($this->mollomField, $before);
		}
		else { 
			$form->Fields()->push($this->mollomField);
		}
	}
	
	function setFieldMapping($fieldToPostTitle, $fieldsToPostBody, $fieldToAuthorName=null, $fieldToAuthorUrl=null, $fieldToAuthorEmail=null, $fieldToAuthorOpenId=null) {
		$this->mollomField->setFieldMapping($fieldToPostTitle, $fieldsToPostBody, $fieldToAuthorName, $fieldToAuthorUrl, $fieldToAuthorEmail, $fieldToAuthorOpenId);
	}
	
	/**
	 * Mark Item as Spam. 
	 * 
	 * @todo {@link Mollom::sendFeedback()} second parameter accepts
	 * different request types. spam, profanity, low-quality, unwanted yet we 
	 * only tag them as SPAM.
	 */
	function markAsSpam() {
		$sessionID = Session::get("mollom_session_id") ? Session::get("mollom_session_id") : null;
		MollomServer::initServerList();
		return Mollom::sendFeedback($sessionID, 'spam');
	}
}

?>