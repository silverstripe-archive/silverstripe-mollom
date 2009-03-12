<?php

/** 
 * Report Interface for Mollom Status
 */
class MollomReport extends SSReport {
	
	protected $title = "Mollom";
	
	protected $description = "Mollom Usage and Statistics";
	
	function getCMSFields() {
		$URL = "http://mollom.com/statistics.swf?key=". Mollom::getPublicKey();
		$msg = "";
		// check a key has been configured
		MollomServer::initServerList();
		
		// check to see what error we should report
		if(Mollom::getPublicKey()) {
			// check it is valid
			if(Mollom::verifyKey()) {
				$msg = _t('Mollom.KEYEXISTS', 'Your key exists and is working');
			}
			else {
				$msg = _t('Mollom.KEYWRONG', 'Your key is not valid. Please try another key');
			}
		}
		else {
			$msg = _t('Mollom.NOKEY', 'You have not configured your key. Please add it to mysite/_config.php.');
		}
		
		// Link to the list of conditions
		$terms = "<a href='http://mollom.com/terms-of-service' target='_blank'>". _t('Mollom.TERMS', 'View Molloms Terms of Use'). "</a>";
		
		$fields = new FieldSet(
			new LiteralField('MollomKeyWorks',
				'<h3>'. _t('Mollom.KEYSTATUS','Mollom Key Status') .'</h3><p>'. $msg .'</p><p>'. $terms .'</p>'
			),
			new LiteralField('MollomStatus', "
				<object classid=\"clsid:D27CDB6E-AE6D-11cf-96B8-444553540000\" codebase=\"http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,0,0\" width=\"100%\" height=\"100%\" id=\"movie\">
					<param name=\"movie\" value=\"$URL\">
					<embed src=\"$URL\" quality=\"high\" width=\"100%\" height=\"100%\" name=\"movie\" type=\"application/x-shockwave-flash\" pluginspage=\"http://www.macromedia.com/go/getflashplayer\"> 
				</object>")
		);
		return $fields;
	}
}
?>