<?php
/**
 * @author intportg
 * @package modules.comment.lib.workflowAction
 */
class comment_ValidateCommentWorkflowaction extends comment_BaseCommentWorkflowaction
{
	/**
	 * This method will execute the action.
	 * @return boolean true if the execution end successfully, false in error case.
	 */
	public function execute()
	{
		$decision = $this->getDecision();
		if ($decision)
		{
			$this->setExecutionStatus($decision);
			return true;
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * @param task_persistentdocument_usertask $task
	 */
	public function updateTaskInfos($task)
	{
		$commentary = $this->getCaseParameter('START_COMMENT');
		
		$author = $this->getCaseParameter('workflowAuthor');
		if (!empty($author))
		{
			$task->setDescription($author);
		}
		if (!empty($commentary))
		{
			$task->setCommentary($commentary);
		}	
	}
}