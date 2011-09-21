<script type="text/javascript">
	var sAjaxUrl                = '<?php echo(admin_url('admin-ajax.php')); ?>';
	var sAjaxAction             = '<?php echo($this->getNameSpace()) ?>';
	var sDefaultCommentContent  = '<h3>Would you also like to syndicate the comments associated with this article?</h3><p><center>'; 
		sDefaultCommentContent += '<?php echo($this->generateHtmlDropDown('selComments', 'selComments', array('Yes' => 1, 'No'  => 0))) ?>';
		sDefaultCommentContent += '</center></p>';
	
	jQuery(document).ready(function($) {
		
		// Search form pretty button
		$('#<?php echo($this->getNameSpace()) ?>-btnSearch').button();

    <?php if (Whv_Config::Get('variables', 'enableWildcardSearch') == false) : ?>
		// Search form validation
		$('#form<?php echo($this->getNameSpace(2)) ?>ArticleSearch').validate({
			rules: {
				'<?php echo($this->getNameSpace()) ?>[txtCriteria]': {
					required: true, 
					minLength: 1, 
					maxLength: 150
				}
			}, 

			messages: {
				'<?php echo($this->getNameSpace()) ?>[txtCriteria]': {
					required: 'You must specify your search criteria.', 
					minLength: 'You must specify your search criteria.', 
					maxLength: 'The criteria provided is too long.'
				}
			},
			
			errorPlacement: function(oError, oElement) {
	            oError.insertAfter($('#<?php echo($this->getNameSpace()) ?>-btnSearch'));
	            oError.addClass('<?php echo($this->getNameSpace()) ?>-form-error');
	        }
				
		});
    <?php endif ?>
		
		// Article Preview Modal
		$('#<?php echo($this->getNameSpace()) ?>-article-preview').dialog({
			draggable: false,
			resizable: false,
			modal: true,
			autoOpen: false,
			width: 800,
			height: 600,
			buttons: {
				'Close': function() {
					$(this).dialog('close');
				}
			}
		});
		
		// Syndication Modal
		$('#<?php echo($this->getNameSpace()) ?>-syndicate-comments-too').dialog({
			title: 'One more thing ...',
			draggable: false,
			resizable: false,
			modal: true,
			autoOpen: false,
			width: 400,
			height: 250
		});

		$.displayArticle = function(iArticleId, sTitle) {
			var sContent = jQuery('#<?php echo($this->getNameSpace()) ?>-article-content-' + iArticleId).html();
			
			$('#<?php echo($this->getNameSpace()) ?>-article-preview').html(sContent);
			$('#<?php echo($this->getNameSpace()) ?>-article-preview').dialog('option', 'title', sTitle);
			$('#<?php echo($this->getNameSpace()) ?>-article-preview').dialog('open');
		}

		jQuery.syndicateArticle = function(oData) {
			oData.sRoute = 'ajaxSyndicateArticle';
			oOneighty    = {
				action: sAjaxAction, 
				<?php echo($this->getNameSpace()) ?>: oData
			};
			
			$.ajax({
				type: 'post',
				url: sAjaxUrl,
				async: true,
				dataType: 'json',
				data: oOneighty,
				success: function(oResponse){

					// Check for erorrs
					if (oResponse.bSuccess === true) {
						$('#<?php echo($this->getNameSpace()) ?>-syndicate-comments-too').html(oResponse.sMessage);
						$('#<?php echo($this->getNameSpace()) ?>-syndicate-comments-too').dialog('option', 'buttons', {
							'Finish': function() {
								$(this).dialog('close');
								$('#<?php echo($this->getNameSpace()) ?>-article-' + oData.iArticleId).fadeOut('slow');
								$('#<?php echo($this->getNameSpace()) ?>-article-' + oData.iArticleId).slideUp('slow');
								$('#<?php echo($this->getNameSpace()) ?>-syndicate-comments-too').html(sDefaultCommentContent);
							}
						});	
					} else {
						<?php echo($this->getNameSpace()) ?>GenerateError($('#<?php echo($this->getNameSpace()) ?>-syndicate-comments-too'), oResponse.sError);
					}
				}
			});
		}

		jQuery.syndicateComments = function(iArticleId, sTitle) {
			$('#<?php echo($this->getNameSpace()) ?>-syndicate-comments-too').dialog('option', 'buttons', {

				'Cancel': function() {
					$(this).dialog('close');
				}, 
				
				'Syndicate': function() {
					var oData = {
						iArticleId: iArticleId, 
						sTitle: sTitle, 
						bSyndicateComments: 1
					};
					
					$.syndicateArticle(oData);
				}
			});
			
			$('#<?php echo($this->getNameSpace()) ?>-syndicate-comments-too').dialog('open');
		}
	});
</script>
<div class="wrap">
	<h2><?php _e(Whv_Config::Get('variables', 'appName').' Article Search') ?></h2>
	<?php if ($this->checkForData('getError')) : ?>
		<div class="ui-widget">
			<div class="ui-state-error ui-corner-all" style="padding: 0 .7em;"> 
				<p><span class="ui-icon ui-icon-alert" style="float: left; margin-right: .3em;"></span> 
					<strong>Error:</strong> <?php _e($this->getError()) ?></p>
			</div>
		</div>
	<?php endif ?>
	
    <form method="post" name="form<?php echo($this->getNameSpace(2)) ?>ArticleSearch" id="form<?php echo($this->getNameSpace(2)) ?>ArticleSearch">
        <?php echo($this->generateHtmlFormField('text', 'txtCriteria', 'txtCriteria', array(
		'style'     => 'height:2.5em;',
		'size'      => 75, 
		'maxlength' => 150, 
		'value'     => ''
	))) ?>

        <?php echo($this->generateHtmlFormField('submit', 'btnSearch', 'btnSearch', array( 
            'value' => 'Search'
        ))) ?>
    </form>
    
    <?php if ($this->checkForData('getSearchResults')) : ?>
        <h2><?php _e('Search Results') ?></h2>
        <table class="widefat post fixed" cellpadding="0">
            <thead>
                <tr>
                    <th width="42%"><?php _e('Article Name') ?></th>
                    <th width="10%"><?php _e('Syndications') ?></th>
                    <th width="8%"><?php _e('Comments') ?></th>
                    <th width="10%"><?php _e('Date Created') ?></th>
                    <th width="6%"><?php _e('Cost') ?></th>
                    <th width="24%"><?php _e('Actions') ?></th>
                </tr>
            </thead>
            <tbody>

            <?php foreach($this->getSearchResults() as $oArticle) : ?>
                <tr id="<?php echo($this->getNameSpace()) ?>-article-<?php echo($oArticle->id) ?>">
                    <td class="post-title">
                        <strong><?php echo($oArticle->title) ?></strong>
<?php //var_dump(array_keys(get_object_vars($oArticle))); ?>
			<?php echo ($oArticle->cat_label)? ($oArticle->cat_label) : ''; ?>
			<?php echo ($oArticle->subcat_label && $oArticle->subcat_label)? ' | ' : ''; ?>
			<?php echo ($oArticle->subcat_label)? ($oArticle->subcat_label) : ''; ?>

                        <div id="<?php echo($this->getNameSpace()) ?>-article-content-<?php echo($oArticle->id) ?>" style="display:none;">
                            <?php echo($oArticle->content) ?>
                        </div>
                    </td>
                    <td><?php echo($oArticle->syndications) ?></td>
                    <td><?php echo($oArticle->comments) ?></td>
                    <td><?php echo(date('M jS, Y', strtotime($oArticle->date_created))) ?></td>
                    <td>
                        <?php if (0.00 == $oArticle->cost) : ?>
                            Free
                        <?php else : ?>
                            <?php _e($oArticle->cost) ?>
                        <?php endif ?>
                    </td>
                    <td>
			<?php echo($this->generateHtmlButton(
				'btnViewArticle', 
				"view-article-button-{$oArticle->id}", 
				'Preview', 
				array('style'=>'width:6em;')));
			?>
			<?php echo($this->generateHtmlButton(
				'btnSyndicateArticle', 
				"syndicate-article-button-{$oArticle->id}", 
				'Syndicate', 
				array('style'=>'width:8em;')));
			?>
                        <script type="text/javascript">
                            jQuery(function() {
								jQuery('#<?php echo($this->getNameSpace()) ?>-view-article-button-<?php echo($oArticle->id) ?>').button({
									//icons: {
									//	primary: 'ui-icon-newwin'
									//}, 
									text: true
								}).click(function() {
									jQuery.displayArticle(<?php echo($oArticle->id) ?>, "<?php echo($oArticle->title) ?>");
								});
								
                                jQuery('#<?php echo($this->getNameSpace()) ?>-syndicate-article-button-<?php echo($oArticle->id) ?>').button({
									icons: {
										primary: 'ui-icon-circle-arrow-s'
									}, 
									text: true
								}).click(function() {
									jQuery.syndicateComments(<?php echo($oArticle->id) ?>);
								});
                            })
                        </script>
                    </td>
                </tr>
            <?php endforeach ?>
            
            </tbody>
            <tfoot></tfoot>
        </table>
    <?php endif ?>
</div>
<div id="<?php echo($this->getNameSpace()) ?>-article-preview"></div>
<div id="<?php echo($this->getNameSpace()) ?>-syndicate-comments-too">
	<p>
        Are you sure you wish to syndicate this article?
	</p><p><center>
		<?php echo($this->generateHtmlDropDown('selComments', 'selComments', array(
			'Yes' => 1, 
			'No'  => 0
		))) ?>
	</center></p>
</div>
