<?php
/** 
 * Backend Report Interface for Mollom Status
 *
 * @package spamprotection
 * @subpackage mollom
 */

class MollomReport extends SS_Report {
	
	protected $title = "Mollom";
	
	protected $description = "Mollom Usage and Statistics";
	
	/**
	 * Returns empty field set and use echo() to return output instead. 
	 *
	 * Using form fields and fieldset can done but we need to introduce FieldGroup (according to the ReportAdmin form template, {@see ReportAdminForm.ss}).
	 * On top of that, we need to css to style the output profile in order to produce a readable output. 
	 */
	function getCMSFields() {
		$URL = "http://mollom.com/statistics.swf?key=". MollomServer::getPublicKey();
		$errMsg = "";
		
		MollomServer::initServerList();
		
		if(MollomServer::getPublicKey()) {
			if(!MollomServer::verifyKey()) {
				$errMsg = _t('Mollom.KEYWRONG', 'Your key is not valid. Please try another key');
			}
		}
		else {
			$errMsg = _t('Mollom.NOKEY', 'You have not configured your key. Please add it to mysite/_config.php.');
		}

		echo "<h3>Mollom Key Status</h3>";
		
		if (empty($errMsg)) {
			echo "
				<p><a href='http://mollom.com/terms-of-service' target='_blank'>". _t('Mollom.TERMS', 'View Molloms Terms of Use'). "</a></p>
			
				<object classid=\"clsid:D27CDB6E-AE6D-11cf-96B8-444553540000\" codebase=\"http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,0,0\" width=\"100%\" height=\"100%\" id=\"movie\">
					<param name=\"movie\" value=\"$URL\">
					<embed src=\"$URL\" quality=\"high\" width=\"100%\" height=\"100%\" name=\"movie\" type=\"application/x-shockwave-flash\" pluginspage=\"http://www.macromedia.com/go/getflashplayer\"> 
				</object>
			";
		}
		else {
			echo "<p>$errMsg</p>";
		}
		
		return new FieldSet();
	}
}
