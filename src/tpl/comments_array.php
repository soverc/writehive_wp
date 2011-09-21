<?php 
	$aComments = array();

	if (count($this->getComments())) {
		foreach ($this->getComments() as $oCmnt) {
			$oComment = new stdClass();
				$oComment->comment_ID           = (property_exists($oCmnt, 'id')           ? $oCmnt->id                                                            : null);
				$oComment->comment_post_ID      = (property_exists($oCmnt, 'article_id')   ? $oCmnt->article_id                                                    : null);
				$oComment->comment_author       = (property_exists($oCmnt, 'author_name')  ? $oCmnt->author_name                                                   : null);
				$oComment->comment_author_email = (property_exists($oCmnt, 'author_email') ? $oCmnt->author_email                                                  : null);
				$oComment->comment_author_url   = (property_exists($oCmnt, 'author_name')  ? stripslashes(Config::Get('urls', 'baseUrl')."/{$oCmnt->author_name}") : Config::Get('variables', 'appName'));
				$oComment->comment_author_IP    = (property_exists($oCmnt, 'author_ip')    ? $oCmnt->author_ip                                                     : null);
				$oComment->comment_date         = (property_exists($oCmnt, 'date_created') ? $oCmnt->date_created                                                  : null);
                $oComment->formatted_date       = (property_exists($oCmnt, 'date_created') ? date('F j, Y', strtotime($oCmnt->date_created))                       : null);
                $oComment->formatted_time       = (property_exists($oCmnt, 'date_created') ? date('g:i a', strtotime($oCmnt->date_created))                        : null);
                $oComment->comment_date_gmt     = (property_exists($oCmnt, 'date_created') ? gmdate('Y-m-d H:i:s', strtotime($oCmnt->date_created))                : null);
				$oComment->comment_parent       = (property_exists($oCmnt, 'parent_id')    ? $oCmnt->parent_id                                                     : 0);
				$oComment->user_id              = (property_exists($oCmnt, 'author_id')    ? $oCmnt->$oCmnt->author_id                                             : 0);
				$oComment->from_blog            = (property_exists($oCmnt, 'from_blog')    ? $oCmnt->from_blog                                                     : Config::Get('variables', 'appName'));
				$oComment->from_url             = (property_exists($oCmnt, 'from_url')     ? $oCmnt->from_url                                                      : '#');
                $oComment->comment_karma        = 0;
				$oComment->comment_approved     = 1;
				$oComment->comment_agent        = null;
				$oComment->comment_type         = null;
			$aComments[] = $oComment;
		}
    }
