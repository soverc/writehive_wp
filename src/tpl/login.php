<div class="wrap">
	<h2><?php _e(Whv_Config::Get('variables', 'appName').' Account Login') ?></h2>
	<?php if ($this->checkForData('getError')) : ?>
		<div class="ui-widget">
			<div class="ui-state-error ui-corner-all" style="padding: 0 .7em;"> 
				<p><span class="ui-icon ui-icon-alert" style="float: left; margin-right: .3em;"></span> 
					<strong>Error:</strong> <?php _e($this->getError()) ?></p>
			</div>
		</div>
	<?php endif ?>
	<div class="article-preview">
		<form method="post">
			<h4><?php _e('Please enter yout account Information')?></h4>
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
	</div>
</div>
