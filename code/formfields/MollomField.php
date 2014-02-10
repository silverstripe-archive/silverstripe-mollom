<?php

/**
 * Mollom Form Field.
 *
 * The {@link FormField} which is inserted into your form fields via the 
 * spam protector class.
 *
 * @package mollom
 */

class MollomField extends FormField {

	/**
	 * @config
	 *
	 * @see setAlwaysShowCaptcha
	 */
	private static $always_show_captcha = false;
	
	/**
	 * @config
	 *
	 * @see setForceCheckOnMembers
	 */
	private static $force_check_on_members = false;
	
	/**
	 * @var string
	 */
	protected $template = 'MollomField';

	/**
	 * @return string
	 */
	public function getCaptchaAudioFile() {
		return MOLLOM_DIR . '/mollom-captcha-player.swf';
	}

	/**
	 * Returns the captcha url if we need to get it from the user
	 *
	 * @return string
	 */
	public function getCaptcha() {
		if($this->getShowCaptcha()) {
			Requirements::css(MOLLOM_DIR . '/css/mollom.css');

			$result = $this->getMollom()->createCaptcha(array(
				'type' => 'image'
			));

			if(is_array($result)) {
				Session::set('mollom_session_id', $result['id']);

				return $result['url'];
			}
		}

		return null;
	}

	/**
	 * Return if we should show the captcha to the user.
	 * 
	 * @return boolean
	 */
	public function getShowCaptcha() {
		if(Permission::check('ADMIN')) {
			return false; 
		}

		$requested = Session::get('mollom_captcha_requested');
		$noMapping = ($this->getForm() && !$this->getForm()->hasSpamProtectionMapping());

		if($requested || !$noMapping) {
			if(!Member::currentUser() || !Config::inst()->get('MollomField', 'force_check_on_members')) {
				return true;
			}
		}

		return (bool) Config::inst()->get('MollomField', 'always_show_captcha');
	}
	
	/**
	 * @return MollomSpamProtector
	 */
	public function getMollom() {
		if(!$this->_mollom) {
			$this->_mollom  = Injector::inst()->create('MollomSpamProtector');
			$this->_mollom ->publicKey = Config::inst()->get('Mollom', 'public_key');
			$this->_mollom ->privateKey = Config::inst()->get('Mollom', 'private_key');

			if(Config::inst()->get('Mollom', 'dev')) {
				$this->_mollom->server = 'dev.mollom.com';
			}
		}

		return $this->_mollom;
	}

	/**
	 * Validate the captcha information
	 *
	 * @param Validator $validator
	 *
	 * @return boolean
	 */
	public function validate($validator) {	
		if(Permission::check('ADMIN')) {
			$this->clearMollomSession();

			return true;
		}	
		
		if(Member::currentUser() && !Config::inst()->get('MollomField', 'force_check_on_members')) {
			$this->clearMollomSession();

			return true;
		}
		
		$session_id = Session::get("mollom_session_id");
		$mapped = $this->getForm()->getSpamMappedData();
		$data = array();

		// prepare submission
		foreach(array('authorName', 'authorUrl', 'authorMail', 'authorIp', 'authorId') as $k) {
			if(isset($mapped[$k])) {
				$data[$k] = $mapped[$k];
			}
		}

		if($session_id) {
			// session ID exists so has been checked by captcha
			$data['id'] = $session_id;
			$data['solution'] = $this->Value();

			$result = $this->getMollom()->checkCaptcha($data);

			if(is_array($result)) {
				if($result['solved']) {
					$this->clearMollomSession();

					return true;
				} else {
					$this->requestMollom();
					
					$validator->validationError(
						$this->name, 
						_t(
							'MollomCaptchaField.CAPTCHAREQUESTED', 
							"Please answer the captcha question",
							"Mollom Captcha provides words in an image, and expects a user to type them in a textfield"
						), 
						"warning"
					);

					return false;
				}
			}
		} else {
			$contentMap = array(
				'id' => 'id',
				'title' => 'postTitle',
				'body' => 'postBody'
			);

			foreach($contentMap as $k => $v) {
				if(isset($mapped[$k])) {
					$data[$v] = $mapped[$k];
				}
			}

			$result = $this->getMollom()->checkContent($data);
			
			// Mollom can do much more useful things.
			// @todo handle profanityScore, qualityScore, sentimentScore, reason
			if(is_array($result)) {
				switch($result['spamClassification']) {
					case 'ham':
						$this->clearMollomSession();

						return true;
				
					case 'unsure':
						$this->requestMollom();

						// we're unsure so request the captcha.
						$validator->validationError(
							$this->name, 
							_t(
								'MollomCaptchaField.CAPTCHAREQUESTED', 
								"Please answer the captcha question",
								"Mollom Captcha provides words in an image, and expects a user to type them in a textfield"
							), 
							"warning"
						);

						return false;
					
					case 'spam':
						$this->clearMollomSession();
						$this->requestMollom();

						$validator->validationError(
							$this->name, 
							_t(
								'MollomCaptchaField.SPAM', 
								"Your submission has been rejected because it was treated as spam.",
								"Mollom Captcha provides words in an image, and expects a user to type them in a textfield"
							), 
							"error"
						);

						return false;
					break;
				}
			}
		}
		
		return true;
	}
	
	/**
	 * @return void
	 */
	private function requestMollom() {
		Session::set('mollom_captcha_requested', true);
	}

	/**
	 * Helper to quickly clear all the mollom session settings. For example 
	 * after a successful post.
	 *
	 * @return void
	 */
	private function clearMollomSession() {
		Session::clear('mollom_session_id');
		Session::clear('mollom_captcha_requested');
	}
}
