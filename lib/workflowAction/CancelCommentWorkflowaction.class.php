<?php
/**
 * @author intportg
 * @package modules.comment.lib.workflowAction
 */
class comment_CancelCommentWorkflowaction extends workflow_CancelContentWorkflowaction
{
	/**
	 * This method will execute the action.
	 * @return boolean true if the execution end successfully, false in error case.
	 */
	function execute()
	{		
		// Send the notification.
		$notificationService = notification_NotificationService::getInstance();
		$notification = $notificationService->getByCodeName('modules_comment/commentRejected');
		if ($notification !== null && $notification->isPublished())
		{
			$document = $this->getDocument();
			
			$replacements = array();
			$replacements['documentId'] = $document->getId();
			$replacements['documentLabel'] = $document->getLabel();
			
			$date = date_Calendar::getInstance($document->getCreationdate());
			$uiDate = date_Converter::convertDateToLocal($date);		
			$replacements['documentCreationDate'] = date_DateFormat::smartFormat($uiDate);
			
			$replacements['targetId'] = $document->getTargetId();
			$replacements['targetLabel'] = $document->getTarget()->getLabel();
			
			$currentUser = users_UserService::getInstance()->getCurrentBackEndUser();
			if ($currentUser !== null)
			{
				$replacements['currentUserId'] = $currentUser->getId();
				$replacements['currentUserFullname'] = $currentUser->getFullname();
			}	
			
			$replacements['validationComment'] = $this->getCaseParameter('__LAST_COMMENTARY');
			
			$recipients = new mail_MessageRecipients();
			$recipients->setTo($document->getEmail());
			
			$notificationService->send($notification, $recipients, $replacements, 'comment');
		}
		
		return parent::execute();
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