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


			<?php foreach($this->getSearchResults() as $oArticle) : ?>
			<?php if (empty ($oArticle->article_id)) continue; ?>

				<div style="border-right:1px solid #ccc; width:45%; float:left; margin-bottom:1em;margin-right:2em;padding:0.5em 0.5em 1em 0.5em;">
					<div style="min-height:2em; padding-bottom:0.2em;">
					<h3 style="margin:0.1em;"><?php echo(htmlspecialchars($oArticle->title, ENT_QUOTES)) ?></h3>
						<?php echo ($oArticle->cat_label)? ($oArticle->cat_label) : ''; ?>
						<?php echo ($oArticle->subcat_label && $oArticle->subcat_label)? ' | ' : ''; ?>
						<?php echo ($oArticle->subcat_label)? ($oArticle->subcat_label) : ''; ?>

					</div>
					<div style="height:10em; overflow:hidden;">
					<?php if ($oArticle->display_pic): ?>
					<img style="float:left;padding:0.2em 0.7em;" height="60" src="<?php echo($oArticle->display_pic) ?>">
					<?php else: ?>
					<img style="float:left;padding:0.2em 0.7em;" height="60" src="<?php echo(Whv_Config::Get('urls', 'baseUrl')) ?>/media/icons/default/user_icon.png">
					<?php endif; ?>
					<?php echo  $oArticle->content; ?>
					</div>

					<div style="min-height:2em; padding:0.1em;font-size:9pt;color:#606060">
					Syndication count:<?php echo($oArticle->syndications) ;?>
					</div>
					<div style="min-height:2em; padding-bottom:0.2em;">
<?php echo($this->generateHtmlButton(
	'btnSyndicateArticle', 
	"syndicate-article-button-{$oArticle->article_id}", 
	'Syndicate', 
	array('style'=>'width:8.2em;')));
?>

<?php echo($this->generateHtmlButton(
	'btnViewArticle', 
	"view-article-button-{$oArticle->article_id}", 
	'Preview', 
	array('style'=>'width:6.2em;')));
?>

					</div>
				</div>
			<?php endforeach; ?>

<script type="text/javascript">
//<!--
<?php foreach($this->getSearchResults() as $oArticle) : ?>
jQuery(function() {
	jQuery('#<?php echo($this->getNameSpace()) ?>-view-article-button-<?php echo($oArticle->article_id) ?>').button({
		text: true
	}).click(function() {
		jQuery.displayArticle('<?php echo($oArticle->article_id) ?>', "<?php echo(esc_html($oArticle->title)) ?>");
	});

	jQuery('#<?php echo($this->getNameSpace()) ?>-syndicate-article-button-<?php echo($oArticle->article_id) ?>').button({
		icons: {
			primary: 'ui-icon-circle-arrow-s'
		}, 
		text: true
	}).click(function() {
		jQuery.syndicateComments('<?php echo($oArticle->article_id) ?>');
	});
});

<?php endforeach ?>
//  -->
</script>

			<?php foreach($this->getSearchResults() as $oArticle) : ?>
		<div id="<?php echo($this->getNameSpace()) ?>-article-content-<?php echo($oArticle->article_id) ?>" style="display:none;">
			<?php echo  $oArticle->content; ?>
		</div>
			<?php endforeach ?>

	<?php endif ?>
</div>
<div id="<?php echo($this->getNameSpace()) ?>-article-preview"></div>
<div id="<?php echo($this->getNameSpace()) ?>-syndicate-comments-too" style="display:none;">
	<p>
		Are you sure you wish to syndicate this article?
	</p><p><center>
<?php echo($this->generateHtmlDropDown('selComments', 'selComments', array(
	'Yes' => 1, 
	'No'  => 0
))) ?>
	</center></p>
</div>
