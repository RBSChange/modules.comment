<?php
/**
 * comment_BlockRatingdistributionAction
 * @package modules.comment.lib.blocks
 */
class comment_BlockRatingdistributionAction extends website_BlockAction
{
	/**
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return String
	 */
	public function execute($request, $response)
	{
		$target = $this->getDocumentParameter();
		if ($target === null)
		{
			return website_BlockView::NONE;
		}
		$totalCount = 0;
		$ratingFilter = change_Controller::getInstance()->getRequest()->getParameter('filter');
		$website = website_WebsiteModuleService::getInstance()->getCurrentWebsite();
		foreach (comment_CommentService::getInstance()->getRatingDistributionByTargetId($target->getId(), $website->getId()) as $key => $val)
		{
			$totalCount += $val;
			$request->setAttribute('displayRating' . $key . 'Link', ($val != 0 && (intval($key) !=  intval($ratingFilter)  || $ratingFilter === null)));
			$request->setAttribute('rating' . $key .'Count', $val);
			$request->setAttribute('filter'. $key. 'Params', array('filter' => $key));
		}
		$sortOption = change_Controller::getInstance()->getRequest()->getParameter('sort');
		$request->setAttribute('hasSortOption', $sortOption !== null);
		$request->setAttribute('sortOption', $sortOption);
		$request->setAttribute('totalCount', $totalCount);
		$request->setAttribute('target', $target);
		$request->setAttribute('avgRating', comment_CommentService::getInstance()->getRatingAverageByTargetId($target->getId(), $website->getId()));
		$request->setAttribute('anchor', $request->getParameter('anchor', 'comment-toolbar-title'));
		return $this->getCommentView(website_BlockView::SUCCESS);
	}
	
	/**
	 * @param $shortViewName
	 * @throws TemplateNotFoundException if template could not be found in current module and comment module
	 * @return TemplateObject
	 */
	private function getCommentView($shortViewName)
	{
		try
		{
			return $this->getTemplate($shortViewName);
		}
		catch (TemplateNotFoundException $e)
		{
			if (Framework::isDebugEnabled())
			{
				Framework::debug(__METHOD__ . ' ' . $e->getMessage()); 
			}
		}
		$templateName = 'Comment-Block-Ratingdistribution-'.$shortViewName;
		return $this->getTemplateByFullName('modules_comment', $templateName);
	}
}