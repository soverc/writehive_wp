<script type="text/javascript">
	var sAjaxUrl      = '<?php echo(admin_url('admin-ajax.php')); ?>';
	var sAjaxAction   = '<?php echo($this->getNameSpace()) ?>';

	// Add our custom validator
	jQuery.validator.addMethod('<?php echo($this->getNameSpace()) ?>', function(sValue, oElement) {
		
		// Check to see if the user wants to syndicate
		if (jQuery("input[name='<?php echo ($this->getNameSpace()) ?>[rdoSyndicate]']:checked").val() == 1) {
			
			// The user wishes to syndicate, 
			// validate the title
			if ((sValue == '') || (sValue == null) || (sValue == undefined)) {
				
				// If the value is not
				// set, return false
				return false;
			} else {
				
				// If the value is 
				// set, return true
				return true;
			}
		}
	});

	// Run actions when document is ready
	jQuery(document).ready(function($) {

		if ($('#publish').val() == 'Update') {

			// Disable the fields, this is
			// just in case the notification 
			// function fails 
			$('#<?php echo($this->getNameSpace()) ?>-post-data').find('input, textarea, button, select').attr('disabled','disabled');

			// Set the notification
			whvGenerateNotice($('#<?php echo($this->getNameSpace()) ?>-post-data'), '<?php echo(Whv_Config::Get('notificationMessages', 'cannotModifyPost')) ?>');
		}
		
		<?php if (!$this->checkForData('getError')) : ?>

			// Hide errors, because there are none
			$('#<?php echo($this->getNameSpace()) ?>-error-container').fadeOut('slow');
		<?php endif ?>

		// Setup validation
		/*
		$('#post').validate({

			// Validation rules
			rules: {
				'post_title': {
					<?php echo($this->getNameSpace()) ?>: true
				}, 

				'content': {
					<?php echo($this->getNameSpace()) ?>: true
				}, 

				'<?php echo($this->getNameSpace()) ?>[selCategory]': {
					<?php echo($this->getNameSpace()) ?>: true
				}, 

				'<?php echo($this->getNameSpace()) ?>[txtTagWordA]': {
					<?php echo($this->getNameSpace()) ?>: true
				}, 

				'<?php echo($this->getNameSpace()) ?>[txtTagWordB]': {
					<?php echo($this->getNameSpace()) ?>: true
				}
			}, 

			// Messages
			messages: {
				'post_title': {
					<?php echo($this->getNameSpace()) ?>: 'You must provide a Title.'
				}, 

				'content': {
					<?php echo($this->getNameSpace()) ?>: 'You must provide actual content! D\'oh!'
				}, 

				'<?php echo($this->getNameSpace()) ?>[selCategory]': {
					<?php echo($this->getNameSpace()) ?>: 'You must select a category.'
				}, 

				'<?php echo($this->getNameSpace()) ?>[txtTagWordA]': {
					<?php echo($this->getNameSpace()) ?>: 'We need a tag word.'
				}, 

				'<?php echo($this->getNameSpace()) ?>[txtTagWordB]': {
					<?php echo($this->getNameSpace()) ?>: 'We need another tag word.'
				}
			}, 

			errorPlacement: function(oError, oElement) {
	            oError.insertAfter(oElement);
	            oError.addClass('<?php echo($this->getNameSpace()) ?>-form-error');
	        }
		});
		*/

		// Create our pretty dropdowns
//		$('#<?php echo($this->getNameSpace()) ?>-selCategoryId').selectmenu();			// Category
//		$('#<?php echo($this->getNameSpace()) ?>-selSecondCategoryId').selectmenu();	// Second Category
		
		// Create our pretty button se
		// $('#<?php echo($this->getNameSpace()) ?>-syndicate-radios').buttonset();

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

		<?php if (Whv_Config::Get('variables', 'enableCostSelect')) : ?>
//			$('#<?php echo($this->getNameSpace()) ?>-selCost').selectmenu();
		<?php endif ?>
		
	});
</script>
<div id="<?php echo($this->getNameSpace()) ?>-post-data">	
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
	</div>
	
	<div class="misc-pub-section-last" id="<?php echo($this->getNameSpace()) ?>-syndicate">
		<span>Syndicate to <?php echo(Whv_Config::Get('variables', 'appName')) ?></span>
		<p id="<?php echo($this->getNameSpace()) ?>-syndicate-radios">
			<?php echo($this->generateHtmlFormField('radio', 'rdoSyndicate', 'rdoSyndicateYes', array(
				'value' => 1
			))) ?>
			<label for="<?php echo($this->getNameSpace())?>-rdoSyndicateYes">
				<span>Yes</span>
			</label>
			
			<?php echo($this->generateHtmlFormField('radio', 'rdoSyndicate', 'rdoSyndicateNo', array(
				'value'   => 0, 
				'checked' => 'checked'
			))) ?>
			<label for="<?php echo($this->getNameSpace())?>-rdoSyndicateNo">
				<span>No</span>
			</label>
		</p>
	</div>
	
	<div class="ui-widget" id="<?php echo($this->getNameSpace()) ?>-error-container" style="display:none">
		<div class="ui-state-error ui-corner-all" style="padding: 0 .7em;"> 
			<p><span class="ui-icon ui-icon-alert" style="float: left; margin-right: .3em;"></span>
				<?php if ($this->checkForData('getError') || $this->getError()) : ?> 
					<strong>Error:</strong> <?php _e($this->getError()) ?></p>
				<?php else : ?>
					<span id="<?php echo($this->getNameSpace()) ?>-error-text"></span>
				<?php endif ?>
		</div>
	</div>
</div>
