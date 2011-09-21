<li <?php comment_class(); ?> id="li-comment-<?php echo($oComment->comment_ID) ?>">
	<!-- Begin Comment -->
	<div id="comment-<?php echo($oComment->comment_ID) ?>">
		<div class="comment-author vcard">
			<?php // echo($this->renderAvatar($oComment, 40)) ?>
				<cite class="fn">
					<a href="<?php _e($oComment->comment_author_url) ?>"><?php _e($oComment->comment_author) ?></a> @ <a href="<?php _e($oComment->from_url) ?>">
                        <?php _e($oComment->from_blog) ?>
                    </a>
				    </cite>
				<span class="says">says:</span>
		</div>

		<div class="comment-meta commentmetadata">
			<!-- Permalink <a href="<?php echo(esc_url(get_comment_link($oComment->comment_ID))) ?>"><?php echo(esc_url(get_comment_link($oComment->comment_ID))) ?></a> -->
				<?php _e(date('F j, Y', strtotime($oComment->comment_date))) ?> at
				<?php _e(date('g:i a', strtotime($oComment->comment_date))) ?>
		</div><!-- .comment-meta .commentmetadata -->

		<!-- Comment Body -->
		<div class="comment-body"><?php _e($oComment->comment_content) ?></div>

		<!-- Comment Reply -->
		<div class="reply">
			<?php /*
				comment_reply_link( array_merge($args, array(
					'depth'     => $depth,
					'max_depth' => $args['max_depth']
				)))
			*/ ?>
		</div>
	</div><!-- End Comment -->
</li>
    
