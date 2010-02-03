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
		else if ($document !== null)
		{
			// Find detail page for the document to display.
			$page = $this->getDetailPage($document);
			if ($page !== null)
			{
				$request->setParameter(K::PAGE_REF_ACCESSOR, $page->getId());
				$module = 'website';
				$action = 'Display';
			}
		}
		else 
		{
			$module = AG_ERROR_404_MODULE;
			$action = AG_ERROR_404_ACTION;
		}

		$context->getController()->forward($module, $action);
		return View::NONE;
	}

	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @return website_persistentdocument_page
	 */
	private function getDetailPage($document)
	{
		$page = null;
		try
		{
			$page = TagService::getInstance()->getDocumentBySiblingTag(
				$this->getFunctionalTag($document),
				$document
			);
		}
		catch (TagException $e)
		{
			//No taged Page found
			Framework::exception($e);
		}

		return $page;
	}

	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @return String
	 */
	private function getFunctionalTag($document)
	{
		$model = $document->getPersistentModel();
		if (!is_null($sourceModel = $model->getSourceInjectionModel()))
		{
			$model = $sourceModel;
		}
		return 'functional_' . $model->getModuleName() . '_' . $model->getDocumentName() .'-detail';
	}
}