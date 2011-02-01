<?php
/**
 * @author intportg
 * @package modules.comment.action
 */
class comment_ViewDetailAction extends generic_ViewDetailAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		$document = $this->getDocumentInstanceFromRequest($request);
		if ($document instanceof comment_persistentdocument_comment)
		{
			$target = DocumentHelper::getDocumentInstance($document->getTargetId());
			
			$module = $target->getPersistentModel()->getModuleName();
			$action = 'ViewDetail';
			
			// Complete commentParam because the forwarded action will use them. 
			$params = $request->getParameter('commentParam');
			$params[K::COMPONENT_ID_ACCESSOR] = $target->getId();
			$params['commentId'] = $document->getId();
			$request->setParameter('commentParam', $params);
			
			// Complete the params for the target module because the blocks will use them.
			$params = $request->getParameter($module.'Param');
			$params[K::COMPONENT_ID_ACCESSOR] = $target->getId();
			$params['commentId'] = $document->getId();
			$request->setParameter($module.'Param', $params);
		}
		else 
		{
			$module = AG_ERROR_404_MODULE;
			$action = AG_ERROR_404_ACTION;
		}

		$context->getController()->forward($module, $action);
		return View::NONE;
	}
}