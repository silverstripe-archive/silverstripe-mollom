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

## Installation

Extract all files into the 'mollom' folder under your Silverstripe root, or install using composer

```bash
composer require silverstripe/mollom dev-master
```

If the mollom module causes an error in composer it may be necessary to explicitly add the
git url to your composer.json

*composer.json*

```json
{
	"repositories": [
        {
            "type": "git",
            "url": "https://github.com/Mollom/MollomPHP.git"
        }
    ]
}
```

## Configuration

This module provides a FormField and SpamProtector classes for integration with
the SpamProtection module in SilverStripe. Consult that module documentation for
more information on how to setup and enable Mollom on your Forms. 

To configure the use of this protector, use the Config API. You will also
need to sign up to [Mollom](http://mollom.com) to get API keys for
the website. Those should also been included through the Config API

*mysite/_config/spamprotection.yml*

```yaml
---
name: spamprotection
---
FormSpamProtectionExtension::
  default_spam_protector: MollomSpamProtector
Mollom:
  public_key: <key>
  private_key: <key>
```
