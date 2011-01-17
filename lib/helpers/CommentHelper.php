<?php
/**
 * @deprecated (will be removed in 4.0)
 */
class comment_CommentHelper 
{
	/**
	 * @deprecated (will be removed in 4.0) use comment_CommentService::frontendValidation()
	 */
	static function validateComment($comment)
	{
		$comment->getDocumentService()->frontendValidation($comment);
	}
}