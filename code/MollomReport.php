<?php

/** 
 * Report Interface for Mollom Status
 */
class MollomReport extends SSReport {
	
	protected $title = "Mollom";
	
	protected $description = "Mollom Usage and Statistics";
	
	function getReportField() {
		$URL = "http://mollom.com/statistics.swf?key=". Mollom::getPublicKey();
		return new LiteralField('MollomStatus', "
			<object classid=\"clsid:D27CDB6E-AE6D-11cf-96B8-444553540000\" codebase=\"http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,0,0\" width=\"100%\" height=\"100%\" id=\"movie\">
				<param name=\"movie\" value=\"$URL\">
				<embed src=\"$URL\" quality=\"high\" width=\"100%\" height=\"100%\" name=\"movie\" type=\"application/x-shockwave-flash\" pluginspage=\"http://www.macromedia.com/go/getflashplayer\"> 
			</object>
		");
	}
}
?>