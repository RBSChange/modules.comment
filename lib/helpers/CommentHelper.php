<?php
class comment_CommentHelper 
{
	/**
	 * Please always call this method if you want to validate a new comment
	 * 
	 * @param comment_persistentdocument_comment $comment
	 */
	static function validateComment($comment)
	{
		if ($comment->getPersistentModel()->hasWorkflow())
		{
			$comment->getDocumentService()->createWorkflowInstance($comment->getId(), array());
		}
		else
		{
			$comment->activate();
		}
	}
}