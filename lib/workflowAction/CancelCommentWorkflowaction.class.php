<?php
/**
 * @author intportg
 * @package modules.comment.lib.workflowAction
 */
class comment_CancelCommentWorkflowaction extends comment_BaseCommentWorkflowaction
{
	/**
	 * This method will execute the action.
	 * @return boolean true if the execution end successfully, false in error case.
	 */
	public function execute()
	{		
		// Update the document's status.
		$document = $this->getDocument();
		$document->getDocumentService()->cancel($document->getId());

		// Send the notification.
		$specificParams = array();
		$currentUser = users_UserService::getInstance()->getCurrentUser();
		if ($currentUser !== null)
		{
			$specificParams['currentUserId'] = $currentUser->getId();
			$specificParams['currentUserFullname'] = $currentUser->getFullname();
		}	
		$specificParams['validationComment'] = $this->getCaseParameter('__LAST_COMMENTARY');

		$callback = array($document->getDocumentService(), 'getNotificationParameters');
		$params = array('comment' => $document, 'specificParams' => $specificParams);
		$this->sendNotificationToAuthorCallback('modules_comment/commentRejected', $callback, $params);

		$this->setExecutionStatus(workflow_WorkitemService::EXECUTION_SUCCESS);
		return true;
	}
}