<?php
/**
 * @deprecated
 */
class comment_CommentHelper 
{
	/**
	 * @param comment_persistentdocument_comment $comment
	 * @deprecated use comment_CommentService::frontendValidation()
	 */
	static function validateComment($comment)
	{
		$comment->getDocumentService()->frontendValidation($comment);
	}
}