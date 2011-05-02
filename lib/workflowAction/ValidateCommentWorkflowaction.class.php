<?php
/**
 * @author intportg
 * @package modules.comment.lib.workflowAction
 */
class comment_ValidateCommentWorkflowaction extends workflow_BaseWorkflowaction
{
	/**
	 * This method will execute the action.
	 * @return boolean true if the execution end successfully, false in error case.
	 */
	function execute()
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
	 * @see workflow_BaseWorkflowaction::updateTaskInfos()
	 *
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
	
	/**
	 * @param String $notificationCodeName
	 * @return array array(websiteId, lang) by default, workflow's document websiteId and original lang
	 */
	public function getNotificationWebsiteIdAndLang($notificationCodeName)
	{
		$document = $this->getDocument();
		return array($document->getWebsiteId(), $document->getLang());
	}
}