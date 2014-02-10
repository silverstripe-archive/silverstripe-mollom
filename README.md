# Mollom Module

## Maintainer Contact

* Saophalkun Ponlu
  <phalkunz (at) silverstripe (dot) com>
	
* Will Rossiter
  <will (at) silverstripe (dot) com>

## Requirements

* SilverStripe >= 3.1  
* Silverstripe SpamProtection module
* Mollom REST PHP Client <https://github.com/Mollom/MollomPHP>

## Documentation

This module provides a FormField and SpamProtector classes for integration with
the SpamProtection module in SilverStripe. Consult that module documentation for
more information on how to setup and enable Mollom on your Forms. 

To configure the use of this protector, use the Config API.

*mysite/_config/spamprotection.yml*
	---
	name: spamprotection
	---
	FormSpamProtection:
	  default_spam_protector: MollomSpamProtector

You will also need to sign up to [Mollom](http://mollom.com) to get API keys for
the website. Those should also been included through the Config API

*mysite/_config/spamprotection.yml*
	---
	name: spamprotection
	---
	FormSpamProtection:
	  default_spam_protector: MollomSpamProtector
	Mollom:
	  public_key: <key>
	  private_key: <key>