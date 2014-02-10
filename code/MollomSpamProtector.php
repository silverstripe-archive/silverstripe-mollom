<?php

require_once(MOLLOM_PATH . '/vendor/mollom/mollom/mollom.class.inc');

/**
 * A customized version of {@link Mollom} to run on SilverStripe. See the 
 * MollomPHP page (https://github.com/mollom/MollomPHP) for more information
 * about the abstracted methods.
 *
 * @package mollom
 */

class MollomSpamProtector extends Mollom implements SpamProtector {

	/**
	 * @var array
	 */
	public $configurationMap = array(
		'publicKey' => 'public_key',
		'privateKey' => 'private_key',
	);

	/**
	 * Load configuration for a given variable such as privateKey. Since 
	 * SilverStripe uses YAML conventions, look for those variables 
	 *
	 * @param string $name
	 * 
	 * @return mixed
	 */
	public function loadConfiguration($name) {
		return Config::inst()->get('Mollom', $this->configurationMap[$name]);
	}

	/**
	 * Save configuration value for a given variable
	 *
	 * @param string $name
	 * @param mixed $value
	 *
	 * @return void
	 */
	public function saveConfiguration($name, $value) {
		return Config::inst()->update('Mollom', $this->configurationMap[$name], $value);
	}
	
	/**
	 * Delete a configuration value.
	 *
	 * SilverStripe does not provide 'delete' as such, but let's just save null
	 * as the value for the session.
	 *
	 * @param string $name
	 */
	public function deleteConfiguration($name) {
		return $this->saveConfiguration($name, null);
	}
	
	/**
	 * Helper for Mollom to know this current client instance.
	 *
	 * @return array
	 */
	public function getClientInformation() {
		$info = new SapphireInfo(); 
		$useragent = 'SilverStripe/' . $info->Version();

		$data = array(
			'platformName' => $useragent,
			'platformVersion' => $info->Version(),
			'clientName' => 'MollomPHP',
			'clientVersion' => 'Unknown',
		);

		return $data;
	}

	/** 
	 * Send the request to Mollom. Must return the result in the format 
	 * prescribed by the Mollom base class.
	 *
	 * @param string $method
	 * @param string $server
	 * @param string $path,
	 * @param string $data
	 * @param array $headers
	 */
	protected function request($method, $server, $path, $query = NULL, array $headers = array()) {
		// if the user has turned on debug mode in the Config API, change the 
		// server to the dev version
		if(Config::inst()->get('Mollom', 'dev')) {
			$server = 'dev.mollom.com';
		}

		$ch = curl_init();

		// CURLOPT_HTTPHEADER expects all headers as values:
		// @see http://php.net/manual/function.curl-setopt.php
		foreach ($headers as $name => &$value) {
			$value = $name . ': ' . $value;
		}

		// Compose the Mollom endpoint URL.
		$url = $server . '/' . $path;
		
		if (isset($query) && $method == 'GET') {
			$url .= '?' . $query;
		}
		
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		
		// Prevent API calls from taking too long.
		// Under normal operations, API calls may time out for Mollom users without
		// a paid subscription.
		curl_setopt($ch, CURLOPT_TIMEOUT, $this->requestTimeout);

		if ($method == 'POST') {
			curl_setopt($ch, CURLOPT_POST, TRUE);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
		}
		else {
			curl_setopt($ch, CURLOPT_HTTPGET, TRUE);
		}

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_HEADER, TRUE);

		// Execute the HTTP request.
		if ($raw_response = curl_exec($ch)) {
			// Split the response headers from the response body.
			list($raw_response_headers, $response_body) = explode("\r\n\r\n", $raw_response, 2);

			// Parse HTTP response headers.
			// @see http_parse_headers()
			$raw_response_headers = str_replace("\r", '', $raw_response_headers);
			$raw_response_headers = explode("\n", $raw_response_headers);
			$message = array_shift($raw_response_headers);
			$response_headers = array();
			
			foreach ($raw_response_headers as $line) {
				list($name, $value) = explode(': ', $line, 2);
				// Mollom::handleRequest() expects response header names in lowercase.
				$response_headers[strtolower($name)] = $value;
			}

			$info = curl_getinfo($ch);
			$response = array(
				'code' => $info['http_code'],
				'message' => $message,
				'headers' => $response_headers,
				'body' => $response_body,
			);
		}
		else {
			$response = array(
				'code' => curl_errno($ch),
				'message' => curl_error($ch),
				'body' => null
			);
		}

		curl_close($ch);

		return (object) $response;
	}

	/**
	 * Return the Field that we will use in this protector.
	 * 
	 * @return MollomField
	 */
	public function getFormField($name = "MollomField", $title = "Captcha", $value = null) {		
		$field = new MollomField($name, $title, $value);

		return $field;
	}
}