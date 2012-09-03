<?php
/**
 * @package modules.comment
 */
class comment_BaseCommentWorkflowaction extends workflow_BaseWorkflowaction
{
	/**
	 * Send a notification to the document author with the default sender. The notification replacements are returned by the callback function.
	 * @param string $codeName
	 * @param array $callback
	 * @param mixed $callbackParameter
	 * @return boolean
	 */
	protected function sendNotificationToAuthorCallback($codeName, $callback = null, $callbackParameter = null)
	{
		// Look for the document author.
		$userId = workflow_CaseService::getInstance()->getParameter($this->getWorkitem()->getCase(), '__DOCUMENT_AUTHOR_ID');
		if ($userId)
		{
			$user = users_persistentdocument_user::getInstanceById($userId);
			return $this->sendNotificationToUserCallback($codeName, $user, $callback, $callbackParameter);
		}
		else 
		{
			list($websiteId, $lang) = $this->getNotificationWebsiteIdAndLang($codeName);
			$notification = notification_NotificationService::getInstance()->getConfiguredByCodeName($codeName, $websiteId, $lang);
			if ($notification !== null)
			{
				$notification->setSendingModuleName('workflow');
				if (is_array($callback) && count($callback) == 2)
				{
					$notification->registerCallback($callback[0], $callback[1], $callbackParameter);
				}
				return $notification->send($this->getDocument()->getEmail());
			}
		}
		return false;
	}
	
	/**
	 * @param string $notificationCodeName
	 * @return array array(websiteId, lang) by default, workflow's document websiteId and original lang
	 */
	public function getNotificationWebsiteIdAndLang($notificationCodeName)
	{
		$document = $this->getDocument();
		return array($document->getWebsiteId(), $document->getLang());
	}
	
	/**
	 * @param task_persistentdocument_usertask $task
	 * @return array
	 */
	protected function getCommonNotifParameters($usertask)
	{
		$document = $this->getDocument();
		return $document->getDocumentService()->getNotificationParameters(array('comment' => $document));
	}
}