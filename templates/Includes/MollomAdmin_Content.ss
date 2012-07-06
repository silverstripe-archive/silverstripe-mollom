<div class="cms-content center cms-tabset $BaseCSSClasses" data-layout-type="border" data-pjax-fragment="Content">

	<div class="cms-content-header north">
		<% with EditForm %>
			<div class="cms-content-header-info">
				<% include BackLink_Button %>
				<h2 id="page-title-heading">
				<% with Controller %>
					<% include CMSBreadcrumbs %>
				<% end_with %>
				</h2>
			</div>
		<% end_with %>
	</div>

	<div class="cms-content-fields center cms-panel-padded">
		<% if not PublicKey %>
			<p class="message bad"><% _t('Mollom.NOKEY', 'You have not configured your key. Please add it to mysite/_config.php.') %></p>
		<% else_if not isKeyVerified %>
			<p class="message bad"><% _t('Mollom.KEYWRONG', 'Your key is not valid. Please try another key.') %></p>
		<% else %>
			<p><a class="ui-button ss-ui-button" href='http://mollom.com/terms-of-service' target='_blank'><% _t('Mollom.TERMS',"View Mollom's Terms of Use") %></a></p>
			<div class="mollom-graph-container">
				<object classid=\"clsid:D27CDB6E-AE6D-11cf-96B8-444553540000\" codebase=\"http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,0,0\" width=\"100%\" height=\"430px\" id=\"movie\">
					<param name=\"movie\" value=\"http://mollom.com/statistics.swf?key=$PublicKey\">
					<embed src=\"http://mollom.com/statistics.swf?key=$PublicKey\" quality=\"high\" width=\"100%\" height=\"430px\" name=\"movie\" type=\"application/x-shockwave-flash\" pluginspage=\"http://www.macromedia.com/go/getflashplayer\"> 
				</object>
			</div>
		<% end_if %>
	</div>
	
</div>