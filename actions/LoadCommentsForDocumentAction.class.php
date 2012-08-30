<?php
/**
 * @package modules.comment
 */
class comment_LoadCommentsForDocumentAction extends change_JSONAction
{
	/**
	 * @param change_Context $context
	 * @param change_Request $request
	 */
	public function _execute($context, $request)
	{
		$ls = LocaleService::getInstance();
		$cs = comment_CommentService::getInstance();
		$result = array();
		$document = $this->getDocumentInstanceFromRequest($request);
		$result['total'] = $cs->getCountByTargetId($document->getId());
		
		$commentsInfo = array();
		if ($result['total'] > 0)
		{
			$offset = $request->getParameter('startIndex');
			$offset = ($offset < $result['total'] && $offset > 0) ? $offset : 0;
			$limit = $request->getParameter('pageSize');
			$limit = ($limit < 0) ? 0 : $limit;
			
			$result['startIndex'] = $offset;
			$result['pageSize'] = $limit;
			
			$comments = $cs->getByTargetId($document->getId(), $offset, $limit);
			if (count($comments) > 0)
			{
				$ps = change_PermissionService::getInstance();
				$package = 'modules_' . $document->getPersistentModel()->getModuleName();
				$permission = $package . '.Validate.comment';
				$canValidate = $ps->hasPermission(users_UserService::getInstance()->getCurrentUser(), $permission, $document->getId());
				
				foreach ($comments as $comment)
				{
					/* @var $comment comment_persistentdocument_comment */
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
					$commentInfo['creationdate'] = date_Formatter::toDefaultDateTimeBO($comment->getUICreationdate());
					$commentInfo['authorName'] = $comment->getAuthorName();
					$commentInfo['email'] = $comment->getEmail();
					$commentInfo['authorwebsiteurl'] = $comment->getAuthorwebsiteurl();
					$commentInfo['rating'] = $comment->getRating();
					$commentInfo['relevancy'] = $comment->getRelevancy();
					$commentInfo['contents'] = $comment->getContentsAsHtml();
					$commentInfo['canValidate'] = $canValidate;
					if ($comment->getWebsiteId())
					{
						$website = DocumentHelper::getDocumentInstanceIfExists($comment->getWebsiteId());
						if ($website instanceof website_persistentdocument_website)
						{
							$commentInfo['linkedwebsite'] = $website->getLabel();
						}
						else
						{
							$commentInfo['linkedwebsite'] = $ls->trans('m.comment.bo.general.unknown', array('ucf', 'lab')) . ' ' . $comment->getWebsiteId();
						}
					}
					else
					{
						$commentInfo['linkedwebsite'] = null;
					}
					$commentsInfo[] = $commentInfo;
				}
			}
		}
		else
		{
			return $this->sendJSONError($ls->trans('m.comment.bo.general.no-comment', array('ucf')), false);
		}
		$result['comments'] = $commentsInfo;
		
		return $this->sendJSON($result);
	}
	
	/**
	 * @var Array<String, String>
	 */
	private $statusLabels = array();
	
	/**
	 * @param string $status
	 * @return string
	 */
	private function getStatusLabel($status)
	{
		if (!isset($this->statusLabels[$status]))
		{
			$this->statusLabels[$status] = LocaleService::getInstance()->trans('m.comment.bo.doceditor.status.' . strtolower($status), array('ucf'));
		}
		return $this->statusLabels[$status];
	}
}