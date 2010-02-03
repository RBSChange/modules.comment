<?php
/**
 * comment_LoadCommentsForDocumentAction
 * @package modules.comment.actions
 */
class comment_LoadCommentsForDocumentAction extends f_action_BaseJSONAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		$cs = comment_CommentService::getInstance();
		$website = website_WebsiteModuleService::getInstance()->getCurrentWebsite();
		$result = array();
		$document = $this->getDocumentInstanceFromRequest($request);
		$offset = $request->getParameter('startIndex');
		$offset = ($offset < 0) ? 0 : $offset;
		$limit = $request->getParameter('pageSize');
		$limit = ($limit < 0) ? 0 : $limit;
		$result['startIndex'] = $offset;
		$result['pageSize'] = $limit;
		$result['total'] = $cs->getCountByTargetId($document->getId(), $website->getId());
		
		$commentsInfo = array();
		if ($result['total'] > 0)
		{
			$comments = $cs->getByTargetId($document->getId(), $offset, $limit, $website->getId());
			if (count($comments) > 0)
			{
				$ps = f_permission_PermissionService::getInstance();
				$package = 'modules_' . $document->getPersistentModel()->getOriginalModuleName();
				$permission = $package . '.Validate.comment';
				$canValidate = $ps->hasPermission(users_UserService::getInstance()->getCurrentBackEndUser(), $permission, $document->getId());
				
				$dateTimeFormat = f_Locale::translateUI('&modules.uixul.bo.datePicker.calendar.dataWriterTimeFormat;');
				foreach ($comments as $comment)
				{	
					$status = $comment->getPublicationstatus();
					$commentInfo = array();
					$commentInfo['commentId'] = $comment->getId();
					$commentInfo['status'] = $status;
					$commentInfo['statusLabel'] = $this->getStatusLabel($status);
					$taskData = array();
					if ($status == 'WORKFLOW')
					{
						foreach (TaskHelper::getPendingTasksForCurrentUserByDocumentId($comment->getId()) as $task)
						{
							$taskData[] = array($task->getId(), $task->getLabel(), $task->getDialogName());
						}
					}
					$commentInfo['tasks'] = $taskData;
					$commentInfo['creationdate'] = date_DateFormat::format($comment->getUICreationdate(), $dateTimeFormat);			
					$commentInfo['authorName'] = $comment->getAuthorName();
					$commentInfo['email'] = $comment->getEmail();
					$commentInfo['authorwebsiteurl'] = $comment->getAuthorwebsiteurl();
					$commentInfo['rating'] = $comment->getRating();
					$commentInfo['relevancy'] = $comment->getRelevancy();
					$commentInfo['contents'] = $comment->getContentsAsHtml();
					$commentInfo['canValidate'] = $canValidate;
					$commentsInfo[] = $commentInfo;
				}
			}
		}		
		$result['comments'] = $commentsInfo;
		
		return $this->sendJSON($result);
	}
	
	/**
	 * @var Array<String, String>
	 */
	private $statusLabels = array();
	
	/**
	 * @param String $status
	 * @return String
	 */
	private function getStatusLabel($status)
	{
		if (!isset($this->statusLabels[$status]))
		{
			$this->statusLabels[$status] = f_Locale::translate('&modules.comment.bo.doceditor.status.' . ucfirst(strtolower($status)) . ';');
		}
		return $this->statusLabels[$status];
	}
}