<?php

/**
 * Mollom Form Field.
 *
 * The actual form field which is inserted into your form fields via the 
 * spam protector class.
 *
 * @package spamprotection
 * @subpackage mollom
 */

class MollomField extends SpamProtectorField {

	static $always_show_captcha = false;
	
	static $force_check_on_members = false;
	
	/**
	 * Initiate mollom service fields
	 */
	protected $mollomFields =  array(
		'session_id' => '',
		'post_title' => '',
		'post_body' => '',
		'author_name' => '', 
		'author_url' => '',
		'author_mail' => '',
		'author_openid' => '',
		'author_id' => ''
	);

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

		if($this->showCaptcha() ) {
		
			$mollom_session_id = Session::get("mollom_session_id");
			$imageCaptcha = MollomServer::getImageCaptcha($mollom_session_id);
			$audioCaptcha = MollomServer::getAudioCaptcha($mollom_session_id);
			
			Session::set("mollom_session_id", $imageCaptcha['session_id']);
				
			$captchaHtml = '<div class="mollom-captcha">';
			$captchaHtml .= '<span class="mollom-image-captcha">' . $imageCaptcha['html'] . '</span>';
			$captchaHtml .= '<span class="mollom-audio-captcha">' . $audioCaptcha['html'] . '</span>';
			$captchaHtml .= '</div>';
			
			return $html . $captchaHtml;
		}
	}
	
	/**
	 * Return if we should show the captcha to the user. Checks for Molloms Request
	 * and if the user is currently logged in as then it can be assumed they are not spam
	 * 
	 * @return bool 
	 */
	private function showCaptcha() {
		if(Permission::check('ADMIN') || !MollomServer::verifyKey()) {
			return false; 
		}
		
		if ((Session::get('mollom_captcha_requested') || !$this->getFieldMapping()) && (!Member::currentUser() || self::$force_check_on_members)) {
			return true;
		} 
		
		return (bool)self::$always_show_captcha;
	}
	
	/**
	 * Return the Field Holder if Required
	 */
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
		
		// if the user has logged and there's no force check on member
		if(Member::currentUser() && !self::$force_check_on_members) {
			return true;
		}
		
		// Info from the session
		$session_id = Session::get("mollom_session_id");
		
		// get fields to check
		$spamFields = $this->getFieldMapping();
		
		// Check validate the captcha answer if the captcha was displayed
		if($this->showCaptcha()) {
			if(MollomServer::checkCaptcha($session_id, $this->Value())) {
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
				Session::set('mollom_captcha_requested', true);
				return false;
			}
		}

		// populate mollem fields
		foreach($spamFields as $key => $field) {
			if(array_key_exists($field, $this->mollomFields)) {
				$this->mollomFields[$field] = (isset($_REQUEST[$key])) ? $_REQUEST[$key] : "";
			}
		}

		$this->mollomFields['session_id'] = $session_id;
		$response = MollomServer::checkContent(
			$this->mollomFields['session_id'],
			$this->mollomFields['post_title'],
			$this->mollomFields['post_body'],
			$this->mollomFields['author_name'],
			$this->mollomFields['author_url'],
			$this->mollomFields['author_mail'],
			$this->mollomFields['author_openid'],
			$this->mollomFields['author_id']
		);
		
		Session::set("mollom_session_id", $response['session_id']);
	 	Session::set("mollom_user_session_id", $response['session_id']);
	
		// response was fine, let it pass through 
		if ($response['spam'] == 'ham') {
			$this->clearMollomSession();
			return true;
		} 
		// response is could be spam, or we just want to be sure.
		else if($response['spam'] == 'unsure') {
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
		else if($response['spam'] == 'spam') {
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
			$this->clearMollomSession();
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
