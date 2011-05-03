<?php
/**
 * @author intportg
 * @package modules.comment.lib.workflowAction
 */
class comment_SelectValidatorsWorkflowaction extends comment_BaseCommentWorkflowaction
{
	/**
	 * This method will execute the action.
	 * @return boolean true if the execution end successfully, false in error case.
	 */
	public function execute()
	{	
		$comment = $this->getDocument();
		$actorsIds = $comment->getDocumentService()->getValidators($comment);
		$this->setCaseParameter('__NEXT_ACTORS_IDS', $actorsIds);
		return parent::execute();
	}
}