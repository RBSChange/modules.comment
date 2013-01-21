<?php
/**
 * @package modules.comment
 */
class comment_ViewFeedAction extends change_Action
{
	/**
	 * @param change_Context $context
	 * @param change_Request $request
	 */
	public function _execute($context, $request)
	{
		$targetId = $request->getModuleParameter('comment', 'targetId');
		if (null === $targetId)
		{
			$targetId = $request->getParameter('targetId');
		}
		
		if ($targetId !== null)
		{
			$target = DocumentHelper::getDocumentInstance($targetId);
			if (f_util_ClassUtils::methodExists($target->getDocumentService(), 'getTargetForComment'))
			{
				$target = $target->getDocumentService()->getTargetForComment($target);
				$targetId = $target->getId();
			}
			
			if ($target instanceof website_persistentdocument_website)
			{
				$feedWriter = comment_CommentService::getInstance()->getRSSFeedWriterByWebsiteId($targetId);
			}
			else
			{
				$website = website_WebsiteService::getInstance()->getCurrentWebsite();
				$feedWriter = comment_CommentService::getInstance()->getRSSFeedWriterByTargetId($targetId, $website->getId());
			}
			
			// Set the link, title and description of the feed.
			$this->setHeaders($feedWriter, $request, $target);
			$this->setContentType('text/xml');
			echo $feedWriter->toString();
		}
	}
	
	/**
	 * @param change_Request $request
	 * @param rss_FeedWriter $feedWriter
	 * @param f_persistentdocument_PersistentDocument $parent
	 */
	private function setHeaders($feedWriter, $request, $parent)
	{
		$title = LocaleService::getInstance()->trans('m.comment.frontoffice.rss-feed-title', array('ucf'), array('target' => $parent->getNavigationLabel()));
		$feedWriter->setTitle($title);
		
		// Description.
		$description = null;
		if (f_util_ClassUtils::methodExists($parent, 'getRSSDescription'))
		{
			$description = strip_tags($parent->getRSSDescription());
		}
		if ($description === null)
		{
			$description = $parent->getNavigationLabel();
		}
		$feedWriter->setDescription($description);
		
		$feedURL = LinkHelper::getDocumentUrl($parent);

		$feedWriter->setLink($feedURL);
	}
	
	/**
	 * @return boolean
	 */
	public function isSecure()
	{
		return false;
	}
	
	/**
	 * @return boolean
	 */
	protected function isDocumentAction()
	{
		return false;
	}
}