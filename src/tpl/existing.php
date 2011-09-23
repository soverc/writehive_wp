<script type="text/javascript">
	var sAjaxUrl             = '<?php echo(admin_url('admin-ajax.php')); ?>';
	var sAjaxAction          = '<?php echo($this->getNameSpace()) ?>';

	// Run actions when document is ready
	jQuery(document).ready(function($) {
		
		<?php if (!$this->checkForData('getError')) : ?>

			// Hide errors, because there are none
			$('#<?php echo($this->getNameSpace()) ?>-error-container').fadeOut('slow');
		<?php endif ?>

		// Create our dialog
		$('#<?php echo($this->getNameSpace()) ?>-syndication-options').dialog({
			title: '<?php echo(Whv_Config::Get('variables', 'appName')) ?> Article Details', 
			modal: true, 
			resizable: false, 
			draggable: false, 
			autoOpen: false, 
			height: 600, 
			width: 280,
            buttons: {
                'Cancel': function() {
                    $(this).dialog('close')
                },

                'Syndicate': function() {
			        $('#<?php echo($this->getNameSpace()) ?>-syndication-form').submit();
                }
            }
		});

		// Create our syndication plugin
		$.syndicateExistingPost = function(iWordPressId) {

			if (!iWordPressId) {
				alert("Cannot find ID for this button.  Report this incident to the plugin  maintainer.");
				return false;
			}
			// Set the post ID
			$('#<?php echo($this->getNameSpace()) ?>-hdnWordPressId').val(iWordPressId);

			// Open the dialog
			$('#<?php echo($this->getNameSpace()) ?>-syndication-options').dialog('open');
		};
		
		// Create our pretty dropdowns
//		$('#<?php echo($this->getNameSpace()) ?>-selCategoryId').selectmenu();			// Category
//		$('#<?php echo($this->getNameSpace()) ?>-selSecondCategoryId').selectmenu();	// Second Category
		
		// Create our pretty button set
		$('#<?php echo($this->getNameSpace()) ?>-syndicate-radios').buttonset();

		<?php if (Whv_Config::Get('variables', 'enablePrivacySettings')) : ?>

			// Create our pretty privacy
			// settings button set
			$('#<?php echo($this->getNameSpace()) ?>-privacy-settings-radios').buttonset();
		<?php endif ?>

		<?php if (Whv_Config::Get('variables', 'enableAllowFree')) : ?>

			// Create our pretty allow
			// free button set
			$('#<?php echo($this->getNameSpace()) ?>-allow-free-radios').buttonset();
		<?php endif ?>

		<?php if (Whv_Config::Get('variables', 'enableSubCategories')) : ?>
			// Hide subcategories, because no
			// category has been selected
			$('#<?php echo($this->getNameSpace()) ?>-subcategory').fadeOut('slow');
			$('#<?php echo($this->getNameSpace()) ?>-second-subcategory').fadeOut('slow');
	
			// Load subcategories 
			$('#<?php echo($this->getNameSpace()) ?>-selCategoryId').change(function() {
	
				// Hide errors, this is just in case
				// there are leftover errors
				$('#<?php echo($this->getNameSpace()) ?>-error-container').fadeOut('slow');
	
				// Run the AJAX
				$.ajax({
					type: 'post',
					url: sAjaxUrl, 
					dataType: 'json', 
					data: {
						action: sAjaxAction, 
						<?php echo($this->getNameSpace()) ?>: {
							sRoute: 'ajaxGetSubcategoriesSelect', 
							iCategoryId: $(this).val(), 
							bSecondSubCategory: 0
						}
					}, 
					
					success: function(oResponse) {
	
						// Check for success
						if (oResponse.bSuccess === true) {
	
							// If successful, set the html to the 
							// dropdown and show it
							$('#<?php echo($this->getNameSpace())?>-subcategory').html('<span>Subcategory</span><p>' + oResponse.sHtml + '</p>');
	
							// Show the dropdown
							$('#<?php echo($this->getNameSpace()) ?>-subcategory').fadeIn('slow');

							// Make it pretty
//							$('#<?php echo($this->getNameSpace()) ?>-selSubcategoryId').selectmenu();
						} else {
	
							// If unsuccessful set the error
							$('#<?php echo($this->getNameSpace()) ?>-error-text').html('<strong>Error: </strong><br><p>' + oResponse.sError + '<p>');
	
							// Show the error
							$('#<?php echo($this->getNameSpace()) ?>-error-container').fadeIn('slow');
						}
					}
				});
			});
	
			// Repeat the process
			$('#<?php echo($this->getNameSpace()) ?>-selSecondCategoryId').change(function() {
	
				$('#<?php echo($this->getNameSpace()) ?>-error-container').fadeOut('slow');
				
				$.ajax({
					type: 'post',
					url: sAjaxUrl, 
					dataType: 'json', 
					data: {
						action: sAjaxAction, 
						<?php echo($this->getNameSpace()) ?>: {
							sRoute: 'ajaxGetSubcategoriesSelect', 
							iCategoryId: $(this).val(), 
							bSecondSubCategory: 1
						}
					}, 
					
					success: function(oResponse) {
	
						if (oResponse.bSuccess === true) {
							$('#<?php echo($this->getNameSpace())?>-second-subcategory').html('<span>Second Subcategory</span><p>' + oResponse.sHtml + '</p>');
							$('#<?php echo($this->getNameSpace()) ?>-second-subcategory').fadeIn('slow');
//							$('#<?php echo($this->getNameSpace()) ?>-selSecondSubcategoryId').selectmenu();
						} else {
							$('#<?php echo($this->getNameSpace()) ?>-error-text').append(oResponse.sError);
							$('#<?php echo($this->getNameSpace()) ?>-error-container').fadeIn('slow');
						}
					}
				});
			});
		<?php endif ?>

		<?php //if (Whv_Config::Get('variables', 'enableCostSelect')) : ?>
		//	$('#<?php echo($this->getNameSpace()) ?>-selCost').selectmenu();
		<?php //endif ?>
		
	});
</script>
<div class="wrap">
	<h2><?php _e(Whv_Config::Get('variables', 'appName').' Syndicate Existing Content') ?></h2>
	<?php if ($this->checkForData('getError')) : ?>
		<div class="ui-widget">
			<div class="ui-state-error ui-corner-all" style="padding: 0 .7em;"> 
				<p><span class="ui-icon ui-icon-alert" style="float: left; margin-right: .3em;"></span> 
					<strong>Error:</strong> <?php _e($this->getError()) ?></p>
			</div>
		</div>
	<?php endif ?>
	
	<table class="widefat post fixed" cellpadding="0">
		<thead>
			<tr>
				<th width="50%"><?php _e('Title') ?></th>
				<th><?php _e('Created') ?></th>
				<th><?php _e('Actions') ?></th>
			</tr>
		</thead>
		<tbody>
            <?php if (count($this->getPosts())) : ?>
                <?php foreach ($this->getPosts() as $oPost) : ?>
                    <tr id="wp-post-<?php echo($oPost->ID) ?>">
                        <td><?php echo(esc_html($oPost->post_title)) ?><span id="<?php echo($this->getNameSpace()) ?>-wordpress-post-id" style="display:none"><?php echo($oPost->ID) ?></span></td>
                        <td><?php echo(date('F jS, Y', strtotime($oPost->post_date))) ?></td>
                        <td>
				<?php if (isset($oPost->isSyndicated) && $oPost->isSyndicated == 1) {
                            	echo($this->generateHtmlButton('btnSyndicateArticle', "synddone-{$oPost->ID}", 'Syndicated', array('class'=>'disabled synd-btn-off'))); 
				} else { ?>
                            <?php echo($this->generateHtmlButton('btnSyndicateArticle', "syndicate-article-button-{$oPost->ID}", 'Syndicate Article', array('class'=>'synd-btn', 'data-synd-id'=>$oPost->ID))) ?>
				<?php } ?>
                        </td>
                    </tr>
                <?php endforeach ?>
            <?php else : ?>
                <tr>
                    <td colspan="3">
                        <div class="ui-widget" id="">
                            <div class="ui-state-error ui-corner-all" style="padding: 0 .7em;">
                                <p>
                                    <span class="ui-icon ui-icon-alert" style="float: left; margin-right: .3em;"></span>
                                    <strong>Error:</strong> <?php _e(Whv_Config::Get('errorMessages', 'noExistingContent')) ?>
                                </p>
                            </div>
                        </div>
                    </td>
                </tr>
            <?php endif ?>
		</tbody>
	</table>
</div>
<div id="<?php echo($this->getNameSpace()) ?>-syndication-options">
    <form id="<?php echo($this->getNameSpace()) ?>-syndication-form" name="<?php echo($this->getNameSpace()) ?>-syndication-form" action="" method="post">
        <div class="misc-pub-section" id="<?php echo($this->getNameSpace()) ?>-category">
            <span>Category</span>
            <p><?php echo($this->generateCategoriesDropdown(false)) ?></p>
        </div>

        <div class="misc-pub-section" id="<?php echo($this->getNameSpace()) ?>-second-category">
            <span>Second Category</span>
            <p><?php echo($this->generateCategoriesDropdown(true)) ?></p>
        </div>

        <?php if (Whv_Config::Get('variables', 'enableSubCategories')) : ?>
            <div class="misc-pub-section" id="<?php echo($this->getNameSpace()) ?>-subcategory">
                <span>Subcategory</span>
            </div>

            <div class="misc-pub-section" id="<?php echo($this->getNameSpace()) ?>-second-subcategory">
                <span>Second Subcategory</span>
            </div>
        <?php endif ?>

        <?php if (Whv_Config::Get('variables', 'enableGroups')) : ?>
            <div class="misc-pub-section" id="<?php echo($this->getNameSpace()) ?>-group-id">
                <span>Group</span>
                <p><?php echo($this->generateGroupsDropdown()) ?></p>
            </div>
        <?php endif ?>

        <?php if (Whv_Config::Get('variables', 'enablePrivacySettings') and $this->checkForData('getGroups')) : ?>
            <div class="misc-pub-section" id="<?php echo($this->getNameSpace()) ?>-privacy-settings">
                <span>Private to Group</span>
                <p id="<?php echo($this->getNameSpace()) ?>-privacy-settings-radios">
                    <?php echo($this->generateHtmlFormField('radio', 'rdoPrivate', 'rdoPrivateYes', array(
                        'value' => 1
                    ))) ?>
                    <label for="<?php echo($this->getNameSpace()) ?>-rdoPrivateYes">
                        <span>Yes</span>
                    </label>

                    <?php echo($this->generateHtmlFormField('radio', 'rdoPrivate', 'rdoPrivateNo', array(
                        'value'   => 0,
                        'checked' => 'checked'
                    ))) ?>
                    <label for="<?php echo($this->getNameSpace()) ?>-rdoPrivateNo">
                        <span>No</span>
                    </label>
                </p>
            </div>
        <?php endif ?>

        <?php if (Whv_Config::Get('variables', 'enableAllowFree')) : ?>
            <div class="misc-pub-section" id="<?php echo($this->getNameSpace()) ?>-allow-free">
                <span>Allow this article to be used for free</span>
                <p id="<?php echo($this->getNameSpace()) ?>-allow-free-radios">
                    <?php echo($this->generateHtmlFormField('radio', 'rdoAllowFree', 'rdoAllowFreeYes', array(
                        'value'   => 1,
                        'checked' => 'checked'
                    ))) ?>
                    <label for="<?php echo($this->getNameSpace()) ?>-rdoAllowFreeYes">
                        <span>Yes</span>
                    </label>

                    <?php echo($this->generateHtmlFormField('radio', 'rdoAllowFree', 'rdoAllowFreeNo', array(
                        'value'   => 0
                    ))) ?>
                    <label for="<?php echo($this->getNameSpace()) ?>-rdoAllowFreeNo">
                        <span>No</span>
                    </label>
                </p>
            </div>
        <?php endif ?>

        <?php if (Whv_Config::Get('variables', 'enableCostSelect')) : ?>
            <div class="misc-pub-section" id="<?php echo($this->getNameSpace()) ?>-cost">
                <span>Cost</span>
                <p><?php echo($this->generateCostDropdown()) ?></p>
            </div>
        <?php endif ?>

        <div class="misc-pub-section" id="<?php echo($this->getNameSpace()) ?>-tag-words">
            <span>Tag Words</span>
            <p>1:&nbsp;&nbsp;<?php echo($this->generateHtmlFormField('text', 'txtTagWordA', 'txtTagWordA')) ?></p>
            <p>2:&nbsp;&nbsp;<?php echo($this->generateHtmlFormField('text', 'txtTagWordB', 'txtTagWordB')) ?></p>
            <p>3:&nbsp;&nbsp;<?php echo($this->generateHtmlFormField('text', 'txtTagWordC', 'txtTagWordC')) ?></p>
            <p>4:&nbsp;&nbsp;<?php echo($this->generateHtmlFormField('text', 'txtTagWordD', 'txtTagWordD')) ?></p>
            <p><?php echo($this->generateHtmlFormField('hidden', 'hdnWordPressId', 'hdnWordPressId')) ?></p>
        </div>

        <div class="ui-widget" id="<?php echo($this->getNameSpace()) ?>-error-container">
            <div class="ui-state-error ui-corner-all" style="padding: 0 .7em;">
                <p><span class="ui-icon ui-icon-alert" style="float: left; margin-right: .3em;"></span>
                    <?php if ($this->checkForData('getError') || $this->getError()) : ?>
                        <strong>Error:</strong> <?php _e($this->getError()) ?></p>
                    <?php else : ?>
                        <span id="<?php echo($this->getNameSpace()) ?>-error-text"></span>
                    <?php endif ?>
            </div>
        </div>
    </form>
</div>


<script type="text/javascript">
//$(document).ready(function() {
jQuery(function() {
	jQuery('.synd-btn').each(function(index) {
	jQuery(this).button({
		icons: {
			primary: 'ui-icon-arrowsbottom-1-s'
		},
		text: true
	}).click(function() {
		jQuery.syndicateExistingPost(jQuery(this).attr('data-synd-id'));
	});
	});

	jQuery('.synd-btn-off').button({
		text: true
	});
});
</script>


