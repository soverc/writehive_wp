<?php global $oWriteCrowd; ?>
<?php if ($oWriteCrowd->checkForData('getError')) : ?>
    <div class="ui-widget">
        <div class="ui-state-error ui-corner-all" style="padding: 0 .7em;">
            <p><span class="ui-icon ui-icon-alert" style="float: left; margin-right: .3em;"></span>
                <strong>Error:</strong><br>
                <?php _e($oWriteCrowd->getError()) ?>
            </p>
        </div>
    </div>
<?php endif ?>
<div id="comments">
	<?php
		// You can start editing here -- including this comment!
	?>
		<h3 id="comments-title">
			<span id="oneighty_comments_count"><?php _e(count($comments)) ?></span> Comment(s)
		</h3>

		<?php if ( get_comment_pages_count() > 1 && get_option( 'page_comments' ) ) : // Are there comments to navigate through? ?>
			<div class="navigation">
				<div class="nav-previous"><?php previous_comments_link( __( '<span class="meta-nav">&larr;</span> Older Comments', 'twentyten' ) ); ?></div>
				<div class="nav-next"><?php next_comments_link( __( 'Newer Comments <span class="meta-nav">&rarr;</span>', 'twentyten' ) ); ?></div>
			</div> <!-- .navigation -->
		<?php endif; // check for comment navigation ?>

			<ol class="commentlist" id="oneighty_comments_list">
				<?php foreach ($comments as $comment) : ?>
					<?php $oWriteCrowd->renderComment($comment) ?>
				<?php endforeach ?>
			</ol>

		<?php if ( get_comment_pages_count() > 1 && get_option( 'page_comments' ) ) : // Are there comments to navigate through? ?>
			<div class="navigation">
				<div class="nav-previous"><?php previous_comments_link( __( '<span class="meta-nav">&larr;</span> Older Comments', 'twentyten' ) ); ?></div>
				<div class="nav-next"><?php next_comments_link( __( 'Newer Comments <span class="meta-nav">&rarr;</span>', 'twentyten' ) ); ?></div>
			</div><!-- .navigation -->
		<?php endif; // check for comment navigation ?>
<?php comment_form(); ?>
	<?php //$oWriteCrowd->renderCommentForm() ?>
</div><!-- #comments -->
