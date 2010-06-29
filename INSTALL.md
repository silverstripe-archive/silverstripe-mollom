# SilverStripe Mollom Module

## Installation

1. Unzip this file (mollom-0.3.tar.gz) inside your SilverStripe installation directory.
It should be at the same level as 'cms' and 'sapphire' modules.

2. Ensure the directory name for the module is 'mollom'. 

3. Visit your SilverStripe site.

4. Add db/build to the end of the website URL. For example: http://localhost:8888/mysite/db/build.

5. We now need to setup some basic features to get the module up and running. Open up _config.php
inside Mollom module directory ('mollom/_config.php') with your favourite text editor.
Read the instructions below to setup the initial configuration of the module.


## Setting up the Mollom API key

Copy the following code into your mysite/_config.php file, and eplace the strings
"enter-your-mollom-public-key" and "enter-your-mollom-private-key" 
with the public key and private key you obtained from Mollom (http://mollom.com/)

	MollomServer::setPublicKey("enter-your-mollom-public-key");
	MollomServer::setPrivateKey("enter-your-mollom-private-key");
	SpamProtectorManager::set_spam_protector('MollomSpamProtector');

### What does this do?

This tell 'SpamProtection' module that you want to use 'MollomField' as a spam
protection module across your site, and set up the public and private keys that
are required for the module to interact with Mollom service. 

## Setting up a form with a mollom field 

Suppose you create a contact form in a page type called 'ContactPage.php' (in 'mysite/code')

	/**
	 * Sample contact form
	 */
	function ContactForm() {
		$fields = new FieldSet(
	      	new TextField("Name", "Name"),
			new EmailField("Email",  "Email"),
			new TextField("Website", "Website"),
			new TextareaField("Content", "Message")
	  	);

		$actions = new FieldSet(
	      	new FormAction('doContactForm', 'Submit')
	  	);

	  	$form = new Form($this, "ContactForm", $fields, $actions);
	
		// form fields and mollom fields mapping
		$fieldMap = array('Name' => 'author_name', 'Email' => 'author_email', 'Website' => 'author_url', 'Content' => 'post_body')
	
		// Update the form to add the protecter field to it
		$protector = SpamProtectorManager::update_form($form, null, $fieldMap);
	
		return $form;
	}

### What does this do?

This setup a contact form with a mollom field. When page first loaded, it displays
a normal form without the protector field. 

After user fills the form and submits it, the content of the form is sent to Mollom
service for spam checking. If Mollom service verifies that the submission is not a
spam, the control will pass on to doContactForm() function. If the service says the
submission is a spam, user will be redirected to the homepage without further 
processing with the form data. On the other hand, if the service is not sure whether
the submission is a spam or a ham, user will be presented with Captcha image and
audio and a text field for issuing the Captcha solution. 