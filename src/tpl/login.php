<div class="wrap" id="poststuff">
	<h2><?php _e(Whv_Config::Get('variables', 'appName').' Account Login') ?></h2>
	<?php if ($this->checkForData('getError')) : ?>
		<div class="ui-widget">
			<div class="ui-state-error ui-corner-all" style="padding: 0 .7em;"> 
				<p><span class="ui-icon ui-icon-alert" style="float: left; margin-right: .3em;"></span> 
					<strong>Error:</strong> <?php _e($this->getError()) ?></p>
			</div>
		</div>
	<?php endif ?>

	<div class="stuffbox">
		<h3>Already have an account?</h3>
		<form method="post">
				<table cellpadding="2" cellspacing="5" class="editform">
					<tr>
						<td align="left" valign="top" nowrap><?php _e('Username')?></td>
					</tr><tr>
						<td align="left" valign="top" nowrap>
							<?php echo($this->generateHtmlFormField('text', 'txtDisplayName', 'txtDisplayName', array(
								'size'      => 30, 
								'maxlength' => 50, 
								'value'     => ''
							))) ?>
						</td>
					</tr><tr>
						<td align="left" valign="top" nowrap><?php _e('Password')?></td>
					</tr><tr>
						<td align="left" valign="top" nowrap>
							<?php echo($this->generateHtmlFormField('password', 'txtPasswd', 'txtPasswd', array(
								'size'      => 30, 
								'maxlength' => 50, 
								'value'     => ''
							))) ?>
						</td>
					</tr><tr>
						<td align="right" valign="top" nowrap>
							<?php echo($this->generateHtmlFormField('submit', 'btnSubmit', 'btnSubmit', array(
								'class' => 'button-primary', 
								'value' => 'Login'
							))) ?>
						</tr>
					</tr>
				</table>
		</form>
	&nbsp;Haven't created an account yet?  Visit <a href="http://writehive.com" target="_blank">http://writehive.com</a> to get one.
	</div>




	<div class="stuffbox">
	<h3>Welcome to WriteHive</h3>
	<iframe src="https://www.writehive.com/plugininfo.html" style="width:100%" height="500"></iframe>

<!--
	<p class="inside">WriteHive is the easiest way to share your works with other WordPress bloggers.</p>
	<p class="inside">Once you login, you can share any posts that you've written with everybody.  Likewise, you can pull great content from WriteHive and include it in your site.</p>
-->
	</div>
</div>
