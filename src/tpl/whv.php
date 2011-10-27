<div class="wrap" id="poststuff">
	<h2><?php _e(Whv_Config::Get('variables', 'appName').' Account Details') ?></h2>
	<?php if ($this->checkForData('getError')) : ?>
		<div class="ui-widget">
			<div class="ui-state-error ui-corner-all" style="padding: 0 .7em;"> 
				<p><span class="ui-icon ui-icon-alert" style="float: left; margin-right: .3em;"></span> 
					<strong>Error:</strong> <?php _e($this->getError()) ?></p>
			</div>
		</div>
	<?php endif ?>
	
	<div class="stuffbox">
		<table width="400" cellpadding="2" cellspacing="5">
			<tr>
				<td width="100px" align="center" valign="top">
					<?php if (empty($this->getAccount()->display_pic)) : ?>
						<img height="100" src="<?php echo($this->getPluginWebPath().'/'.Whv_Config::Get('folders', 'images')) ?>/no-avatar.jpg">
					<?php else : ?>
						<img height="100" src="<?php echo($this->getAccount()->display_pic) ?>">
					<?php endif ?>
				</td>
				<td valign="top">
					<table width="100%">
						<tr>
							<td>
								<?php $acct = $this->getAccount(); ?>
								<?php if(!isset($acct->account_id)) $profLink = $acct->display_name;
								else $profLink = $acct->account_id.'_'.$acct->first_name.'_'.$acct->last_name.'.html'; ?>

								Public Profile: <a href="<?php echo(Whv_Config::Get('urls', 'baseUrl')) ?>/profile/<?php echo $profLink ?>" target="_blank"><?php echo($this->getAccount()->display_name) ?></a>
							</td>
						</tr><tr>
							<td>
                                <?php if (empty($this->getAccount()->first_name) || empty($this->getAccount()->last_name)) : ?>
                                    <?php // echo($this->getAccount()->display_name) ?>
                                <?php else : ?>
								    <?php echo($this->getAccount()->first_name) ?> <?php echo($this->getAccount()->last_name) ?>
                                <?php endif ?>
							</td>
						</tr>
<!-- 
						<tr>
							<td>
								<strong>Joined: </strong>
                                <?php if (empty($this->getAccount()->date_created) || ($this->getAccount()->date_created == '0000-00-00 00:00:00')) : ?>
                                    Unkown
                                <?php else : ?>
								    <?php echo(date('F jS, Y', strtotime($this->getAccount()->date_created))) ?>
								    <?php echo $this->getAccount()->date_created; ?>
                                <?php endif ?>
							</td>
						</tr>
-->
					</table>
				</td>
			</tr>
			<?php if (strlen($this->getAccount()->bio)): ?>
			<tr>
				<td colspan="2">
					<strong>About Me</strong>
					<hr />
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<?php echo($this->getAccount()->bio) ?>
				</td>
			</tr>
			<?php endif; ?>
		</table>
	</div>

	<?php
	if ( ini_get('magic_quotes_gpc') 
	):  ?>
	<div class="ui-widget" id="<?php echo($this->getNameSpace()) ?>-error-container">
		<div class="ui-state-error ui-corner-all" style="padding: 0 .7em;"> 
			<p><span class="ui-icon ui-icon-alert" style="float: left; margin-right: .3em;"></span>
			<span id="<?php echo($this->getNameSpace()) ?>-error-text"><b>magic_quotes_runtime</b> or <b>magic_quotes_gpc</b> is enabled.  You may notice strange results when syndicating content.</span>
		</div>
	</div>
	<?php endif; ?>

	<div class="stuffbox">
	<h3>We love feedback</h3>
		<p class="inside">
		Send us any feedback, comments, or questions at: <a href="http://support.writehive.com/anonymous_requests/new" target="_blank">http://support.writehive.com/</a>.
		</p>


	</div>
	
	<div class="stuffbox">
	<h3>What you can do with WriteHive</h3>
	<table width="500" cellpadding="7" cellspacing="5" border="2" style="border-style:solid">
		<tr>
			<td>
				<a href="<?php echo admin_url('admin.php?page=whv_existing');?>">Existing&nbsp;Content</a>
			</td>
			<td>
				This page shows all the posts you've written and allows you to send them to WriteHive.
			</td>
		</tr>

		<tr>
			<td>
				<a href="<?php echo admin_url('admin.php?page=whv_search');?>">Article&nbsp;Search</a>
			</td>
			<td>
				Find new articles to syndicate on your site.
			</td>
		</tr>

		<tr>
			<td>
				<a href="<?php echo admin_url('admin.php?page=whv_syndicated');?>">Syndicated&nbsp;Content</a>
			</td>
			<td>
				All the content that you've pulled from WriteHive.  Here is where you can delete syndicated contet from your site.
			</td>
		</tr>


	</table>
	</div>

	<div class="stuffbox">
		<h3>Account Settings</h3>
		<p class="inside">
		<a href="<?php echo admin_url('admin.php?page=whv_logout');?>">Plugin Logout</a> &mdash; Logout of just your WriteHive account.
		</p>
		<p class="inside">
		<a href="https://writehive.com/account/" target="_blank">Public Profile Page</a> &mdash; Update your public profile at WriteHive.com.  You will need to logout of this plugin to see any updates you make at WriteHive.com. 
		</p>

	</div>

</div>
