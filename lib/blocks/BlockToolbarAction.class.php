<?php
/**
 * comment_BlockToolbarAction
 * @package modules.comment.lib.blocks
 */
class comment_BlockToolbarAction extends website_BlockAction
{
	/**
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return String
	 */
	function execute($request, $response)
	{
		$request->setAttribute('target', $this->getDocumentParameter());
		// Deal with filters 
		$globalRequest = change_Controller::getInstance()->getRequest();
		$ratingFilterValue = $globalRequest->getParameter('filter', null);
		if ($ratingFilterValue !== null)
		{
			$request->setAttribute('ratingFilterValue', comment_RatingService::getInstance()->normalizeRating($ratingFilterValue));
		}
		$request->setAttribute('currentSortOption', $globalRequest->getParameter('sort'));
		$request->setAttribute('anchor', $request->getParameter('anchor', 'comment-toolbar-title'));
		return website_BlockView::SUCCESS;
	}
}