<div class="wrap">
	<h2><?php _e(Whv_Config::Get('variables', 'appName').' Account Details') ?></h2>
	<?php if ($this->checkForData('getError')) : ?>
		<div class="ui-widget">
			<div class="ui-state-error ui-corner-all" style="padding: 0 .7em;"> 
				<p><span class="ui-icon ui-icon-alert" style="float: left; margin-right: .3em;"></span> 
					<strong>Error:</strong> <?php _e($this->getError()) ?></p>
			</div>
		</div>
	<?php endif ?>
	
	<div class="article-preview">
		<table width="400px" cellpadding="2" cellspacing="5">
			<tr>
				<td width="100px" align="center" valign="top">
                    <?php if (empty($this->getAccount()->display_pic)) : ?>
                        <img src="<?php echo($this->getPluginWebPath().'/'.Whv_Config::Get('folders', 'images')) ?>/no-avatar.jpg">
                    <?php else : ?>
					    <img src="<?php echo(Whv_Config::Get('urls', 'baseUrl')) ?>/application/files/images/avatars/<?php echo($this->getAccount()->display_pic) ?>">
                    <?php endif ?>
				</td>
				<td valign="top">
					<table width="100%">
						<tr>
							<td>
								<a href="<?php echo(Whv_Config::Get('urls', 'baseUrl')) ?>/user/<?php echo($this->getAccount()->display_name) ?>" target="_blank"><?php echo($this->getAccount()->display_name) ?></a>
							</td>
						</tr><tr>
							<td>
                                <?php if (empty($this->getAccount()->first_name) || empty($this->getAccount()->last_name)) : ?>
                                    <?php echo($this->getAccount()->display_name) ?>
                                <?php else : ?>
								    <?php echo($this->getAccount()->first_name) ?> <?php echo($this->getAccount()->last_name) ?>
                                <?php endif ?>
							</td>
						</tr><tr>
							<td>
								<strong>API Key: </strong>
								<?php echo($this->getAccount()->account_key) ?>
							</td>
						</tr><tr>
							<td>
								<strong>Joined: </strong>
                                <?php if (empty($this->getAccount()->date_created)) : ?>
                                    Unkown
                                <?php else : ?>
								    <?php echo(date('F jS, Y', strtotime($this->getAccount()->date_created))) ?>
                                <?php endif ?>
							</td>
						</tr><tr>
							<td>
								<strong>Email: </strong>
								<?php echo($this->getAccount()->email_address) ?>
							</td>
						</tr>
					</table>
				</td>
			</tr><tr>
				<td colspan="2">
					<strong>About Me</strong>
					<hr />
				</td>
			</tr><tr>
				<td colspan="2">
					<?php echo($this->getAccount()->bio) ?>
				</td>
			</tr>
		</table>
	</div>

	<?php
	if ( ini_get('magic_quotes_runtime') 
		|| ini_get('magic_quotes_gpc') 
	):  ?>
	<div class="ui-widget" id="<?php echo($this->getNameSpace()) ?>-error-container">
		<div class="ui-state-error ui-corner-all" style="padding: 0 .7em;"> 
			<p><span class="ui-icon ui-icon-alert" style="float: left; margin-right: .3em;"></span>
			<span id="<?php echo($this->getNameSpace()) ?>-error-text"><b>magic_quotes_runtime</b> or <b>magic_quotes_gpc</b> is enabled.  You may notice strange results when syndicating content.</span>
		</div>
	</div>
	<?php endif; ?>
</div>
