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
}

?>