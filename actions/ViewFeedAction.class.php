<?php
/**
 * comment_ViewFeedAction
 * @package modules.comment.actions
 */
class comment_ViewFeedAction extends f_action_BaseAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{		
		$targetId = $request->getModuleParameter('comment', 'targetId');
		if (null === $targetId)
		{
			$targetId = $request->getParameter('targetId');
		}
		
		$target = DocumentHelper::getDocumentInstance($targetId);
		if ($target instanceof website_persistentdocument_website)
		{
			$feedWriter = comment_CommentService::getInstance()->getRSSFeedWriterByWebsiteId($targetId);
		}
		else 
		{
			$website = website_WebsiteModuleService::getInstance()->getCurrentWebsite();
			$feedWriter = comment_CommentService::getInstance()->getRSSFeedWriterByTargetId($targetId, $website->getId());
		}
		
		// Set the link, title and description of the feed
		$this->setHeaders($feedWriter, $request, $target);
		$this->setContentType('text/xml');
		echo $feedWriter->toString();
	}
	
	/**
	 * @param Request $request
	 * @param rss_FeedWriter $feedWriter
	 * @param f_persistentdocument_PersistentDocument $parent
	 */
	private function setHeaders($feedWriter, $request, $parent)
	{
		$title = f_Locale::translate('&modules.comment.frontoffice.Rss-feed-title;', array('target' => $parent->getLabel()));
		$feedWriter->setTitle($title);
		
		// Description.
		$description = null;
		if (f_util_ClassUtils::methodExists($parent, 'getRSSDescription'))
		{
			$description = strip_tags($parent->getRSSDescription());
		}
		if ($description === null)
		{
			$description = $parent->getLabel();
		}
		$feedWriter->setDescription($description);
		
		$feedURL = LinkHelper::getDocumentUrl($parent);

		$feedWriter->setLink($feedURL);
	}
	
	public function isSecure()
	{
		return false;
	}
	
	protected function suffixSecureActionByDocument()
	{
		return false;
	}
}