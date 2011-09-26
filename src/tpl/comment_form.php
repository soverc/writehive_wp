<style type="text/css">
	#oneighty_comment_form .required {
		display: block;
		color: #ff0000;
	}

	.oneighty_ajax_success {
		display: block;
		color: #00ff00;
	}

	.oneighty_ajax_error {
		display: block;
		color: #ff0000;
	}

	#oneighty_comment_syndication_form {
		display: none;
	}
</style>
<script type="text/javascript" src="<?php _e($mp_defs['plugin_path']) ?>/assets/js/validate.jquery.js"></script>
<script type="text/javascript">
	wp_user  = 0;
	token    = '';
	user_obj = null;
	mpjaxer  = 'oneighty_jaxer';

	jQuery(document).ready(function($){

        $('#<?php echo($this->getNameSpace()) ?>-login-method').buttonset({
            icons: {
                primary: 'ui-icon-check',
                secondary: '.ui-icon-check'
            }
        });

		setTimeout(function(){
			reload_comments();
		}, 60000);

		checkLogin();

		// Validate our Comment
		jQuery('#oneighty_comment_form').validate();

		jQuery('#oneighty_login_div').dialog({
			draggable: false,
			resizable: false,
			modal: true,
			autoOpen: false,
			width: 400,
			height: 325,
			title: 'Please log in ...',
			buttons: {
				'Login': function() {
					if (doLogin() == true) {
						jQuery(this).dialog('close');
					}
				},

				'Close': function() {
					jQuery(this).dialog('close');
				}
			}
		});

		// Submit our Comment
		jQuery('#oneighty_submit_comment').click(function() {
			var oneighty_comment_data = {
				action: mpjaxer,
				route: 'oneighty_submit_comment',
				article_id: jQuery('#oneighty_comment_post_id').val(),
			 	author_id: jQuery('#oneighty_author_id').val(),
				author_name: jQuery('#oneighty_author_name').val(),
				author_email: jQuery('#oneighty_author_email').val(),
				author_url: jQuery('#oneighty_author_url').val(),
				author_ip: '<?php _e($_SERVER['REMOTE_ADDR'])?>',
				content: jQuery('#oneighty_comment_content').val()
			};

			jQuery.ajax({
				url: '<?php echo(admin_url('admin-ajax.php'))?>',
				data: oneighty_comment_data,
				dataType: 'json',
				type:'post',
				async: false,
				success: function(response) {
					if (response.supplemental.message_type == 'error') {
						oneighty_message(response.data, false);
					} else {
						oneighty_message(response.data, true);
					}
				},

				error: function(){
				},

				complete: function() {
					var comment_count = parseInt(jQuery('#oneighty_comments_count').html()) + 1;

					jQuery('#oneighty_comment_syndication_form').slideUp();
						reload_comments_once();
							jQuery('#oneighty_comments_count').html(comment_count);
				}
			});
		});

		jQuery('#oneighty_leave_comment').click(function(){
			if (token == '') {
				jQuery('#oneighty_login_div').dialog('open');
			} else {

			}
		});
	});

	function oneighty_message(text, good) {
		if (good == true) {
			jQuery('#oneighty_ajax_messages').html('<br /><strong>' + text + '</strong><br /><br />');
				jQuery('#oneighty_ajax_messages').slideDown();
					jQuery('#oneighty_ajax_messages').attr('class', 'oneighty_ajax_success');
		} else {
			jQuery('#oneighty_ajax_messages').html('<br /><strong>' + text + '</strong><br /><br />');
				jQuery('#oneighty_ajax_messages').slideDown();
					jQuery('#oneighty_ajax_messages').attr('class', 'oneighty_ajax_error');
		}
	}

	function reload_comments() {
		setTimeout(function(){
			jQuery.ajax({
				url: '<?php echo(admin_url('admin-ajax.php')) ?>',
				data: {
					action: mpjaxer,
					route: 'oneighty_comments_grab',
					article_id: '<?php echo($this->getArticle()->id) ?>'
				},
				dataType: 'html',
				type: 'post',
				async: false,
				success: function(comments) {
					jQuery('#oneighty_comments_list').focus();
					jQuery('#oneighty_comments_list').html(comments);
				}
			});
		}, 60000);
	}

	function reload_comments_once() {
		jQuery.ajax({
			url: '<?php echo(admin_url('admin-ajax.php')) ?>',
			data: {
				action: mpjaxer,
				route: 'oneighty_comments_grab',
				article_id: '<?php echo($this->getArticle()->id) ?>'
			},
			dataType: 'html',
			type: 'post',
			async: false,
			success: function(comments) {
				jQuery('#oneighty_comments_list').focus();
				jQuery('#oneighty_comments_list').html(comments);
			}
		});
	}

	function doLogin() {
		var toReturn = false;
		jQuery.ajax({
			url: '<?php echo(admin_url('admin-ajax.php')) ?>',
			data: {
				action: mpjaxer,
				route: 'oneighty_user_login',
				article_id: '<?php echo($this->getArticle()->id) ?>',
				uname: jQuery('#mp_user_name').val(),
				passwd: jQuery('#mp_user_pass').val(),
				method: jQuery("input[name='mp_user_login_method']:checked").val()
			},
			dataType: 'json',
			type: 'post',
			async: false,
			success: function(robj) {
				if (robj.success == true) {
					user_obj = robj.user;
					token    = robj.user.token;
					toReturn = true;
				} else {
					jQuery('#mp_user_error').val(robj.error);
					toReturn = false;
				}
			}
		});
	return(toReturn);
	}

	function checkLogin() {
		var toReturn = false;

		if (token == '') {
			jQuery.ajax({
				url: '<?php echo(admin_url('admin-ajax.php')) ?>',
				data: {
					action: mpjaxer,
					route: 'oneighty_user_auth',
					article_id: '<?php echo($this->getArticle()->id) ?>'
				},
				dataType: 'json',
				type: 'post',
				async: false,
				success: function(robj) {
					if (robj.success == true) {
						user_obj = robj.user;
						token    = robj.user.token;
						toReturn = true;
					} else {
						toReturn = false;
					}
				}
			});
		}
		else if (token != '') {
			toReturn = true;
		}

		if (toReturn == true) {
			jQuery('#oneighty_author_name').val(user_obj.name);
			jQuery('#oneighty_author_email').val(user_obj.email);
			jQuery('#oneighty_author_url').val(user_obj.url);
			jQuery('#oneighty_author_id').val(user_obj.id);
				jQuery('#oneighty_leave_comment').attr('style', 'display:none;');
				jQuery('#oneighty_comment_syndication_form').slideDown();
		}

		setTimeout(function() {
			checkLogin();
		}, 1000);
	return(toReturn);
	}
</script>
<a href="#oneighty_comment_syndication_form" id="oneighty_leave_comment">Leave a Comment ...</a>
<div id="oneighty_login_div">

        <div id="<?php echo($this->getNameSpace()) ?>-login-method">
			<?php echo($this->generateHtmlFormField('radio', 'rdoLogon', 'rdoLogonWordPress', array(
				'value'   => 'wordpress',
                'checked' => 'checked'
			))) ?>
			<label for="<?php echo($this->getNameSpace())?>-rdoLogonWordPress">
				<span><img src="<?php echo("{$this->getPluginWebPath()}/".Whv_Config::Get('folders', 'images')."/wordpress-logo.png") ?>" width="115" height="25"></span>
			</label>

			<?php echo($this->generateHtmlFormField('radio', 'rdoLogon', 'rdoLogonOneighty', array(
				'value'   => $this->getNameSpace(),
			))) ?>
			<label for="<?php echo($this->getNameSpace())?>-rdoLogonOneighty">
				<span><img src="<?php echo("{$this->getPluginWebPath()}/".Whv_Config::Get('folders', 'images')."/{$this->getNameSpace()}-logo.png") ?>" width="115" height="25"></span>
			</label>
		</div>

		<div>
			<label>Username : </label>
				<br />
					<input type="text" id="mp_user_name" />
		</div>

		<div>
			<label>Password : </label>
				<br />
					<input type="password" id="mp_user_pass" />
		</div>

</div>
<div id="oneighty_comment_syndication_form" style="display:none;">
	<div id="oneighty_ajax_messages" style="display:none;"></div>
	<form action="" method="post" id="oneighty_comment_form">
		<!-- Author Name -->
		<p>
	   		<label for="oneighty_author">
	        	<small>Name (required)</small>
			</label>
				<br />
					<input name="oneighty_author_name" id="oneighty_author_name" value="" size="22" tabindex="1" type="text" class="required">
		</p>

		<!-- Author Email Address -->
	   	<p>
	   		<label for="oneighty_email">
	        	<small>Mail (will not be published) required)</small>
	   		</label>
				<br />
	    			<input name="oneighty_author_email" id="oneighty_author_email" value="" size="22" tabindex="2" type="text" class="required">
	   	</p>

		<!-- Author URL -->
	   	<p>
	        <label for="oneighty_url">
	        	<small>Website</small>
	        </label>
				<br />
					<input name="oneighty_author_url" id="oneighty_author_url" value="" size="22" tabindex="3" type="text" class="required">
	   	</p>

	   	<!-- Allowed Tags -->
	   	<p>
	    	<small><strong>XHTML:</strong> You can use these tags:....</small>
	   	</p>

		<!-- Comment Body -->
	   	<p>
	    	<textarea name="oneighty_comment_content" id="oneighty_comment_content" cols="100" rows="10" tabindex="4" class="required"></textarea>
	   	</p>

	   	<!-- Form Submisstion -->
	   	<p>
	   		<input name="oneighty_submit_comment" id="oneighty_submit_comment" tabindex="5" value="Submit Comment" type="button">
	       	<input name="oneighty_comment_post_id" id="oneighty_comment_post_id" value="<?php echo($this->getArticle()->sOneightyId) ?>" type="hidden">
	   	</p>
	</form>
</div>
