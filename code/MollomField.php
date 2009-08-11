<?php
/**
 * Mollom Form Field to include in your forms. 
 *
 * @package mollom
 */

class MollomField extends SpamProtectorField {
	
	static $alwaysShowCaptcha = false;
	
	// Map fields (by name) to Spam service's post fields for spam checking
	protected $fieldToPostTitle = "";
	
	// it can be more than one fields mapped to post content
	protected $fieldsToPostBody = array();
	
	protected $fieldToAuthorName = "";
	
	protected $fieldToAuthorUrl = "";
	
	protected $fieldToAuthorEmail = "";
	
	protected $fieldToAuthorOpenId = "";
	
	function setFieldMapping($fieldToPostTitle, $fieldsToPostBody, $fieldToAuthorName=null, $fieldToAuthorUrl=null, $fieldToAuthorEmail=null, $fieldToAuthorOpenId=null) {
		$this->fieldToPostTitle = $fieldToPostTitle;
		$this->fieldsToPostBody = $fieldsToPostBody;
		$this->fieldToAuthorName = $fieldToAuthorName;
		$this->fieldToAuthorUrl = $fieldToAuthorUrl;
		$this->fieldToAuthorEmail = $fieldToAuthorEmail;
		$this->fieldToAuthorOpenId = $fieldToAuthorOpenId;
	}
	
	function __construct($name, $title = null, $value = null, $form = null, $rightTitle = null) {
		parent::__construct($name, $title, $value, $form, $rightTitle);
		MollomServer::initServerList();
	}
	
	function Field() {
		$attributes = array(
			'type' => 'text',
			'class' => 'text' . ($this->extraClass() ? $this->extraClass() : ''),
			'id' => $this->id(),
			'name' => $this->Name(),
			'value' => $this->Value(),
			'title' => $this->Title(),
			'tabindex' => $this->getTabIndex(),
			'maxlength' => ($this->maxLength) ? $this->maxLength : null,
			'size' => ($this->maxLength) ? min( $this->maxLength, 30 ) : null 
		);
		
		$html = $this->createTag('input', $attributes);

		if($this->showCaptcha()) {
			$mollom_session_id = Session::get("mollom_session_id") ? Session::get("mollom_session_id") : null;
			$imageCaptcha = MollomServer::getImageCaptcha($mollom_session_id);
			$audioCaptcha = MollomServer::getAudioCaptcha($imageCaptcha['session_id']);
			
			Session::set("mollom_session_id", $imageCaptcha['session_id']);
				
			$captchaHtml = '<div class="mollom-captcha">';
			$captchaHtml .= '<span class="mollom-image-captcha">' . $imageCaptcha['html'] . '</span>';
			$captchaHtml .= '<span class="mollom-audio-captcha">' . $audioCaptcha['html'] . '</span>';
			$captchaHtml .= '</div>';
			
			return $html . $captchaHtml;
		}
		
		return null;
	}
	
	/**
	 * Return if we should show the captcha to the user. Checks for Molloms Request
	 * and if the user is currently logged in as then it can be assumed they are not spam
	 * 
	 * @return bool 
	 */
	private function showCaptcha() {
		if((Session::get('mollom_captcha_requested') || empty($this->fieldsToPostBody)) && !Member::currentUser()) {
			return true;
		}
		return (self::$alwaysShowCaptcha == false) ? false : true;
	}
	
	function FieldHolder() {
		return ($this->showCaptcha()) ? parent::FieldHolder() : null;
	}

	
	/**
	 * This function first gets values from mapped fields and then check these values against
	 * Mollom web service and then notify callback object with the spam checking result. 
	 * @return 	boolean		- true when Mollom confirms that the submission is ham (not spam)
	 *						- false when Mollom confirms that the submission is spam 
	 * 						- false when Mollom say 'unsure'. 
	 *						  In this case, 'mollom_captcha_requested' session is set to true 
	 *       				  so that Field() knows it's time to display captcha 			
	 */
	function validate($validator) {
		// If the user is ADMIN let them post comments without checking
		if(Permission::check('ADMIN')) {
			$this->clearMollomSession();
			return true;
		}
		
		// Check captcha solution if user has submitted a solution
		if((Session::get('mollom_captcha_requested') && trim($this->Value()) != '') || empty($this->fieldsToPostBody) ) {
			$mollom_session_id = Session::get("mollom_session_id") ? Session::get("mollom_session_id") : null;
			if ($mollom_session_id && MollomServer::checkCaptcha($mollom_session_id, $this->Value())) {
				$this->clearMollomSession();
				return true;
			}
			else {
				$validator->validationError(
					$this->name, 
					_t(
						'MollomCaptchaField.INCORRECTSOLUTION', 
						"You didn't type in the correct captcha text. Please type it in again.",
						PR_MEDIUM,
						"Mollom Captcha provides words in an image, and expects a user to type them in a textfield"
				), 
					"validation", 
					false
				);
				return false;
			}
		}

		$postTitle = null;
		$postBody = null;
		$authorName = null;
		$authorUrl = null;
		$authorEmail = null;
		$authorOpenId = null;
		
		/* Get form content */
		if (isset($_REQUEST[$this->fieldToPostTitle])) $postTitle = $_REQUEST[$this->fieldToPostTitle];
		
		if (!is_array($this->fieldsToPostBody)) {
			$postBody = $_REQUEST[$this->fieldsToPostBody];
		}
		else {
			$fieldsToCheck = array_intersect( $this->fieldsToPostBody, array_keys($_REQUEST) );	
			foreach ($fieldsToCheck as $fieldName) {
				$postBody .= $_REQUEST[$fieldName] . " ";
			}
		}
		
		if (isset($_REQUEST[$this->fieldToAuthorName])) $authorName = $_REQUEST[$this->fieldToAuthorName];
		
		if (isset($_REQUEST[$this->fieldToAuthorUrl])) $authorUrl = $_REQUEST[$this->fieldToAuthorUrl];
		
		if (isset($_REQUEST[$this->fieldToAuthorEmail])) $authorEmail = $_REQUEST[$this->fieldToAuthorEmail];
		
		if (isset($_REQUEST[$this->fieldToAuthorOpenId])) $authorOpenId = $_REQUEST[$this->fieldToAuthorOpenId];
		
		$mollom_session_id = Session::get("mollom_session_id") ? Session::get("mollom_session_id") : null;
		
		// check the submitted content against Mollom web service
		$response = MollomServer::checkContent($mollom_session_id, $postTitle, $postBody, $authorName, $authorUrl, $authorEmail, $authorOpenId);

		// save the session ids in the session as we use them in the form returned
		Session::set("mollom_session_id", $response['session_id']);
		Session::set("mollom_user_session_id", $response['session_id']);
		// response was fine, let it pass through 
		if ($response['spam'] == 'ham') {
			$this->clearMollomSession();
			return true;
		} 
		// response is SPAM. Stop and throw an error
		else if ($response['spam'] == 'unsure') {
			$validator->validationError(
				$this->name, 
				_t(
					'MollomCaptchaField.CAPTCHAREQUESTED', 
					"Please answer the captcha question",
					PR_MEDIUM,
					"Mollom Captcha provides words in an image, and expects a user to type them in a textfield"
				), 
				"warning"
			);
			
			Session::set('mollom_captcha_requested', true);
			return false;
		}
		// Mollom has detected spam!
		else {
			$this->clearMollomSession();
			$validator->validationError(
				$this->name, 
				_t(
					'MollomCaptchaField.SPAM', 
					"Your submission has been rejected because it was treated as spam.",
					PR_MEDIUM,
					"Mollom Captcha provides words in an image, and expects a user to type them in a textfield"
				), 
				"error"
			);
			Session::set('mollom_captcha_requested', true);
			return false;
		}
		return true;
	}
	
	/**
	 * Helper to quickly clear all the mollom session settings. For example after a successful post
	 */
	private function clearMollomSession() {
		Session::clear('mollom_session_id');
		Session::clear('mollom_captcha_requested');
	}
}
?>