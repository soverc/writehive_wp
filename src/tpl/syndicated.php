<?php $sMetaProperty     = str_replace('{nameSpace}', $this->getNamespace(), Whv_Config::Get('wordPress', 'postMetaKeyData')) ?>
<script type="text/javascript">
var sAjaxUrl      = '<?php echo(admin_url('admin-ajax.php')); ?>';
var sAjaxAction   = '<?php echo($this->getNameSpace()) ?>';
var sOriginalHtml = '<?php _e(Whv_Config::Get('confirmationMessages', 'removeFromWordPress')) ?>';

jQuery(document).ready(function($) {
	$('#<?php echo($this->getNameSpace()) ?>-remove-article').dialog({
		title: 'Remove Article', 
		autoOpen: false, 
		modal: true, 
		height: 200, 
		width: 400, 
		draggable: false,
		resizable: false
	});

	$.removeArticle = function(iWordPressId) {

		$('#<?php echo($this->getNameSpace()) ?>-remove-article').html(sOriginalHtml);
		
		$('#<?php echo($this->getNameSpace()) ?>-remove-article').dialog('option', 'buttons', {
			'Yes': function() {
				$.ajax({
					type: 'post',
					url: sAjaxUrl, 
					dataType: 'json', 
					data: {
						action: sAjaxAction, 
						'<?php echo($this->getNameSpace()) ?>[sRoute]': 'ajaxRemoveArticle',
						'<?php echo($this->getNameSpace()) ?>[iWordPressId]': iWordPressId
					}, 
					
					success: function(oResponse) {

						if (oResponse.bSuccess === true) {
							$('#<?php echo($this->getNameSpace()) ?>-remove-article').html(oResponse.sMessage);
							$('#<?php echo($this->getNameSpace()) ?>-remove-article').dialog('option', 'buttons', {
								'Continue': function() {
									$(this).dialog('close');
									$('#<?php echo($this->getNameSpace()) ?>-article-' + iWordPressId).fadeOut('slow');
									$('#<?php echo($this->getNameSpace()) ?>-article-' + iWordPressId).slideUp('slow');
								}
							});

						} else {
							<?php echo($this->getNameSpace()) ?>GenerateError($('#<?php echo($this->getNameSpace()) ?>-remove-article'), oResponse.sError);
						}
					}
				});
			},

			'No': function() {
				$('#<?php echo($this->getNameSpace()) ?>-remove-article').dialog('close');
			}
		});

		$('#<?php echo($this->getNameSpace()) ?>-remove-article').dialog('open');
	}
});
</script>
<div class="wrap">
	<h2><?php _e(Whv_Config::Get('variables', 'appName').' Syndicated Content') ?></h2>
	<?php if ($this->checkForData('getError')) : ?>
		<div class="ui-widget">
			<div class="ui-state-error ui-corner-all" style="padding: 0 .7em;"> 
				<p><span class="ui-icon ui-icon-alert" style="float: left; margin-right: .3em;"></span> 
					<strong>Error:</strong> <?php _e($this->getError()) ?></p>
			</div>
		</div>
	<?php endif ?>
	
	<?php if ($this->checkForData('getArticles') && count($this->getArticles())) : ?>
		<table class="widefat post fixed" cellpadding="0">
			<thead>
				<tr>
					<th width="50%"><?php _e('Title') ?></th>
					<th><?php _e('Category') ?></th>
					<th><?php _e('Created By') ?></th>
					<th><?php _e('Submitted') ?></th>
					<th><?php _e('Actions') ?></th>
				</tr>
			</thead>
			<tbody>
			
			<?php foreach($this->getArticles() as $oArticle): ?>
                <?php if (!empty($oArticle->aSyndicationData) && $oArticle->aSyndicationData[str_replace('{nameSpace}', $this->getNameSpace(), Whv_Config::Get('wordPress', 'postMetaKeySyndicated'))]) : ?>
					<?php $synData = $oArticle->aSyndicationData[$sMetaProperty]; ?>
                    <tr id="<?php echo($this->getNameSpace()) ?>-article-<?php echo($oArticle->ID) ?>">
                        <td class="post-title">
                            <a href="<?php echo(get_bloginfo('url')) ?>/?p=<?php echo($oArticle->ID) ?>">
                                <?php echo esc_html($oArticle->aSyndicationData[$sMetaProperty]->title) ?>
                            </a>
                        </td><td>
                            <?php echo($synData->cat_label) ?>
                        </td><td>
                            <?php if ($synData->author_id == $this->getAccount()->id) : ?>
                                <?php _e('Me') ?>
                            <?php else : ?>
								<?php if(!isset($synData->account_id)) $profLink = $synData->author_name;
								else $profLink = $synData->account_id.'_'.$synData->author_name.'.html'; ?>
                                <a target="_blank" href="<?php echo(Whv_Config::Get('urls', 'baseUrl')) ?>/profile/<?php echo $profLink ?>"><?php echo($synData->author_name) ?></a>
                            <?php endif ?>
                        </td><td>
                            <?php echo(date('F jS, Y', strtotime($oArticle->aSyndicationData[$sMetaProperty]->date_created)))?>
                        </td><td>
                            <?php //echo($this->generateHtmlButton('btnViewArticle', "view-article-button-{$oArticle->ID}", 'View')) ?>
			    <!--
                            <script type="text/javascript">
                                jQuery(function() {
                                    jQuery('#<?php echo($this->getNameSpace()) ?>-view-article-button-<?php echo($oArticle->ID) ?>').button({
                                        icons: {
                                            primary: 'ui-icon-newwin'
                                        },
                                        text: false
                                    }).click(function() {
                                        window.open('<?php echo esc_html(get_bloginfo('url')) ?>/?p=<?php echo esc_html($oArticle->ID) ?>');
                                    })
                                });
                            </script>
-->

                            <?php if (Whv_Config::Get('variables', 'ownerCanEditArticle') and ($oArticle->aSyndicationData[$sMetaProperty]->author_id == $this->getAccount()->id)) : ?>
                                <?php //echo($this->generateHtmlButton('btnEditArticle', "edit-article-button-{$oArticle->ID}", 'Edit')) ?>
				<!--
                                <script type="text/javascript">
                                    jQuery(function() {
                                        jQuery('#<?php echo($this->getNameSpace()) ?>-edit-article-button-<?php echo($oArticle->ID) ?>').button({
                                            icons: {
                                                primary: 'ui-icon-pencil'
                                            },
                                            text: false
                                        }).click(function() {
                                            jQuery.editArticle(<?php echo($oArticle->ID) ?>);
                                        });
                                    })
                                </script>
-->
                            <?php endif ?>

                            <?php echo($this->generateHtmlButton('btnRemoveArticle', "remove-article-button-{$oArticle->ID}", 'Remove')) ?>
                            <script type="text/javascript">
                                jQuery(function() {
                                    jQuery('#<?php echo($this->getNameSpace()) ?>-remove-article-button-<?php echo($oArticle->ID) ?>').button({
                                        icons: {
                                            primary: 'ui-icon-circle-close'
                                        },
                                        text: true
                                    }).click(function() {
                                        jQuery.removeArticle(<?php echo($oArticle->ID) ?>);
                                    });
                                })
                            </script>
                        </td>
                    </tr>
                <?php endif ?>
			<?php endforeach; ?>
			
			</tbody>
		</table>
	<?php else : ?>	
		<h3><?php _e(Whv_Config::Get('errorMessages', 'noSyndicatedContent')) ?></h3>
	<?php endif ?>
</div>
<div id="<?php echo($this->getNameSpace()) ?>-remove-article"><?php _e(Whv_Config::Get('confirmationMessages', 'removeFromWordPress')) ?></div>
