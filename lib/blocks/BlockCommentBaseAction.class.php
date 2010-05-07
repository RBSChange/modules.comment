<?php
/**
 * comment_BlockCommentsAction
 * @package modules.comment.lib.blocks
 */
abstract class comment_BlockCommentsBaseAction extends website_BlockAction
{
	/**
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return String
	 */
	public function execute($request, $response)
	{
		if ($this->isInBackoffice())
		{
			$request->setAttribute('blockLabel', f_Locale::translate('&modules.'.$this->getModuleName().'.bo.blocks.'.ucfirst($this->getName()).';'));
			try
			{
				return $this->getTemplate(website_BlockView::BACKOFFICE);
			}
			catch (TemplateNotFoundException $e)
			{
				return website_BlockView::NONE;
			}
		}
		
		$target = $this->getTarget($request);
		if ($this->hideComments($target))
		{
			return website_BlockView::NONE;
		}
		$request->setAttribute('target', $target);
		$allComments = $this->getCommentsListByTarget($target);
		$request->setAttribute('totalCount', count($allComments));
		
		$itemPerPage = $this->getNbItemPerPage($request, $response);
		$pageNumber = $this->getPageNumber($request, $itemPerPage, $allComments);
		$offset = $itemPerPage * ($pageNumber - 1);
		$request->setAttribute('offset', $offset);
		
		$comments = new paginator_Paginator($this->getModuleName(), $pageNumber, $allComments, $itemPerPage);
		$request->setAttribute('comments', $comments);
		
		$commentId = $request->getParameter('commentId');
		$request->setAttribute('currentCommentId', $commentId);		

		// Add the RSS feed.
		$feedTitle = f_Locale::translate('&modules.comment.frontoffice.Rss-feed-title;', array('target' => $target->getLabel()));
		$page = $this->getPage();
		$page->addRssFeed($feedTitle, LinkHelper::getActionUrl('comment', 'ViewFeed', array('targetId' => $target->getId())));
		// Deal with filters 
		$globalRequest = f_mvc_HTTPRequest::getInstance();
		$ratingFilterValue = $globalRequest->getParameter('filter', null);
		if ($ratingFilterValue !== null)
		{
			$request->setAttribute('ratingFilterValue', comment_RatingService::getInstance()->normalizeRating($ratingFilterValue));
		}
		return $this->getCommentView(website_BlockView::SUCCESS);
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocument $target
	 * @return Boolean
	 */
	protected function hideComments($target)
	{
		return ($target === null || !$target->isPublished());
	}
	
	/**
	 * @param f_mvc_Request $request
	 * @param comment_persistentdocument_comment $bean
	 * @return Boolean
	 */
	public function validateSaveInput($request, $bean)
	{
		// TODO: move it to a better place.
		$request->setAttribute('target', $this->getTarget($request));
		
		// Validation.
		$validationRules = BeanUtils::getBeanValidationRules('comment_persistentdocument_comment', null, array('label', 'targetdocumentmodel'));
		if ($this->isRatingRequired())
		{
			$validationRules[] = "rating{min:0;max:5}";
		}
		
		$isOk = $this->processValidationRules($validationRules, $request, $bean);
		// Captcha is tested only for not logged-in users. 
		if (users_WebsitefrontenduserService::getInstance()->getCurrentFrontEndUser() === null)
		{
			$code = Controller::getInstance()->getContext()->getRequest()->getModuleParameter('form', 'CHANGE_CAPTCHA');
			if (!FormHelper::checkCaptcha($code))
			{
				$this->addError(f_Locale::translate('&modules.comment.frontoffice.Error-captcha;'));
				$isOk = false;
			}
		}
		return $isOk;
	}
	
	/**
	 * @return String
	 */
	public function getSaveInputViewName()
	{
		return $this->getCommentView(website_BlockView::SUCCESS);
	}
	
	/**
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @param comment_persistentdocument_comment $comment
	 * @return String
	 */
	public function executePreview($request, $response, comment_persistentdocument_comment $comment)
	{
		$comment->setContents(website_BBCodeService::getInstance()->fixContent($comment->getContents()));
		$request->setAttribute('previewComment', $comment);	
		$request->setAttribute('comment', $comment);	
		return $this->execute($request, $response);
	}
	
	/**
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @param comment_persistentdocument_comment $comment
	 * @return String
	 */
	public function executeSave($request, $response, comment_persistentdocument_comment $comment)
	{
		$comment->save();
		$request->setAttribute('comment', $comment);
		
		// Ask validation.
		comment_CommentHelper::validateComment($comment);
		$request->setAttribute('published', $comment->isPublished());
		return $this->getCommentView('Save');
	}
	
	/**
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return String
	 */
	public function executeRateUseful($request, $response)
	{
		if ($request->hasNonEmptyParameter('commentId'))
		{
			$comment = DocumentHelper::getDocumentInstance(intval($request->getParameter('commentId')));
			if ($comment instanceof comment_persistentdocument_comment && !$comment->isEvaluatedByCurrentUser())
			{
				$comment->setUsefulcount(intval($comment->getUsefulcount()) + 1);
				$comment->getDocumentService()->updateApprovedComment($comment);
				$comment->markAsEvaluatedForCurrentUser();
			}
		}
		return $this->execute($request, $response);
	}
	
	/**
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return String
	 */
	public function executeRateUseless($request, $response)
	{
		if ($request->hasNonEmptyParameter('commentId'))
		{
			$comment = DocumentHelper::getDocumentInstance(intval($request->getParameter('commentId')));
			if ($comment instanceof comment_persistentdocument_comment && !$comment->isEvaluatedByCurrentUser())
			{
				$comment->setUselesscount(intval($comment->getUselesscount()) + 1);
				$comment->getDocumentService()->updateApprovedComment($comment);
				$comment->markAsEvaluatedForCurrentUser();
			}
		}
		return $this->execute($request, $response);
	}
	
	/**
	 * This method may be redefined in the final block if the target
	 * has to be found differently.
	 * @param f_mvc_Request $request
	 */
	protected function getTarget($request)
	{
		return $this->getDocumentParameter();
	}
	
	/**
	 * Return true to force the input of a rating inside the commentary
	 * @return Boolean
	 */
	protected function isRatingRequired()
	{
		return false;
	}
	
	/**
	 * @param f_mvc_Request $request
	 */
	protected function getPageNumber($request, $itemPerPage, $allComments)
	{
		// If there is a page set, return it.
		$pageNumber = $request->getParameter(paginator_Paginator::REQUEST_PARAMETER_NAME);
		if ($pageNumber)
		{
			if (floor(count($allComments) / $itemPerPage) + 1 >= $pageNumber)
			{
				return $pageNumber;
			}
		}
		
		// Else look for a comment id.
		$commentId = intval($request->getParameter('commentId'));
		if ($commentId)
		{
			foreach ($allComments as $index => $comment)
			{
				if ($commentId == $comment->getId())
				{
					return 1 + floor($index / $itemPerPage);
				}
			}
		}
		
		// Else return the first page.
		return 1;
	}
	
	/**
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return Integer
	 */
	protected function getNbItemPerPage($request, $response)
	{
		$configuration = $this->getConfiguration();
		if (f_util_ClassUtils::methodExists($configuration, 'getNbitemperpage'))
		{
			return $this->getConfiguration()->getNbitemperpage();;
		}
		return 10;
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocument $target
	 * @return comment_persistentdocument_comment[]
	 */
	protected function getCommentsListByTarget($target)
	{
		$globalRequest = f_mvc_HTTPRequest::getInstance();
		$ratingFilterValue = $globalRequest->getParameter('filter');
		$website = website_WebsiteModuleService::getInstance()->getCurrentWebsite();
		switch ($globalRequest->getParameter('sort', 'date'))
		{
			case 'relevancy':
				$allComments = comment_CommentService::getInstance()->getPublishedByTargetIdOrderByRelevancy($target->getId(), $ratingFilterValue, $website->getId());
				break;
			case 'rating':
				$allComments = comment_CommentService::getInstance()->getPublishedByTargetIdOrderByRating($target->getId(), $ratingFilterValue, $website->getId());
				break;
			case 'date':
			default:
				$allComments = comment_CommentService::getInstance()->getPublishedByTargetId($target->getId(), $ratingFilterValue, $website->getId());
				break;
		}
		return $allComments;
	}

	/**
	 * @param $shortViewName
	 * @throws TemplateNotFoundException if template could not be found in current module and comment module
	 * @return TemplateObject
	 */
	protected function getCommentView($shortViewName)
	{
		try
		{
			return $this->getTemplate($shortViewName);
		}
		catch (TemplateNotFoundException $e)
		{
			$templateName = 'Comment-Block-CommentBase-'.$shortViewName;
			return $this->getTemplateByFullName('modules_comment', $templateName);
		}
	}
}