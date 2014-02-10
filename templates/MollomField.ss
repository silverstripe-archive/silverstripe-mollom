<% if Captcha %>
	<% if not $isReadonly %>
		<div class="mollom-captcha">
			<div class="mollom-image-captcha">
				<img src="$Captcha" alt="Type the characters you see in this picture." />
      			<input type="text" name="$Name" size="10" value="" autocomplete="off" />
			</div>
			<!--
			<div class="mollom-audio-captcha">
				<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="//download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,0,0" width="110" height="50">
					<param name="allowFullScreen" value="false" />
					<param name="movie" value="$CaptchaAudioFile" />
					<param name="loop" value="false" />
					<param name="menu" value="false" />
					<param name="quality" value="high" />
					<param name="wmode" value="transparent" />
					<param name="bgcolor" value="#ffffff" />
					<embed src="$CaptchaAudioFile" loop="false" menu="false" quality="high" wmode="transparent" bgcolor="#ffffff" width="110" height="50" align="baseline" allowScriptAccess="sameDomain" allowFullScreen="false" type="application/x-shockwave-flash" pluginspage="http://www.adobe.com/go/getflashplayer_de" />
				</object>
			</div>
			-->
		</div>
	<% else %>
		<!-- Readonly form, Mollom is hidden and we don't need to validate ever -->
	<% end_if %>
<% else %>
	<!-- Mollom Captcha is not required at this stage, but we still validate against the API -->
	<input type="hidden" name="$Name" size="10" value="" autocomplete="off" />
<% end_if %>
