<?php
/**
 * comment_ModerateAction
 * @package modules.comment.actions
 */
class comment_ModerateAction extends f_action_BaseAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		
		/* @var $comment comment_persistentdocument_comment */
		$comment = null;
		$task = null;
		$url = null;
		
		$decision = $request->getParameter('decision');
		
		if ($request->hasParameter('cmpref'))
		{
			$comment = DocumentHelper::getDocumentInstanceIfExists($request->getParameter('cmpref'));
		}
		
		if ($comment != null)
		{
			
			if (in_array($decision, array('ACCEPTED', 'REFUSED')))
			{
				
				$task = $comment->getValidationTask();
				
				$user = users_UserService::getInstance()->getCurrentFrontEndUser();
				
				if ($task != null && $task->isPublished() && $task->getUser()->getId() == $user->getId() && $task->getWorkitem()->getDocumentId() == $comment->getId())
				{
					$task->getDocumentService()->execute($task, $decision, null);
				}
			
			}
			
			$url = LinkHelper::getDocumentUrl($comment->getTarget(), null, array("commentId" => $comment->getId()));
		
		}
		
		if ($url == null)
		{
			$context->getController()->forward('website', 'Error404');
		}
		else
		{
			$context->getController()->redirectToUrl($url);
		}
		
		return View::NONE;
	
	}

}