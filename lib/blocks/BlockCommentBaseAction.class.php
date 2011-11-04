<?php
/**
 * comment_BlockCommentsAction
 * @package modules.comment.lib.blocks
 */
abstract class comment_BlockCommentsBaseAction extends website_BlockAction
{
	/**
	 * @return array
	 */
	public function getRequestModuleNames()
	{
		return array('comment', $this->getModuleName());
	}
	
	/**
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return String
	 */
	public function execute($request, $response)
	{
		if ($this->isInBackoffice())
		{
			$request->setAttribute('blockLabel', LocaleService::getInstance()->transFO('m.'.$this->getModuleName().'.bo.blocks.'.$this->getName(), array('ucf')));
			return $this->getTemplate(website_BlockView::BACKOFFICE);
		}

		$target = $this->getTarget($request);
		if ($this->hideComments($target))
		{
			return website_BlockView::NONE;
		}

		$this->loadSuccessView($request);
		return $this->getCommentView(website_BlockView::SUCCESS);
	}

	/**
	 * This method loads data for success view.
	 * @param f_mvc_Request $request
	 */
	protected function loadSuccessView($request)
	{
		$globalRequest = change_Controller::getInstance()->getRequest();
		$target = $this->getTarget($request);
		$request->setAttribute('target', $target);
		$allComments = $this->getCommentsListByTarget($target);
		$request->setAttribute('totalCount', count($allComments));

		$itemPerPage = $this->getNbItemPerPage($request, null);
		$pageNumber = $this->getPageNumber($request, $itemPerPage, $allComments);
		$offset = $itemPerPage * ($pageNumber - 1);
		$request->setAttribute('offset', $offset);

		$comments = new paginator_Paginator($this->getModuleName(), $pageNumber, $allComments, $itemPerPage);
		$request->setAttribute('comments', $comments);

		if ($globalRequest->hasParameter('commentId'))
		{
			$commentId = $globalRequest->getParameter('commentId');
			$request->setAttribute('currentCommentId', $commentId);
		}
		
		// Handle restriction to connected users.
		$user = users_UserService::getInstance()->getCurrentFrontEndUser();
		$request->setAttribute('connectToPost', (!$this->allowNotRegistered() && $user === null));
			
		// Deal with filters.
		$ratingFilterValue = $globalRequest->getParameter('filter', null);
		if ($ratingFilterValue !== null)
		{
			$request->setAttribute('ratingFilterValue', comment_RatingService::getInstance()->normalizeRating($ratingFilterValue));
		}
		
		// Add the RSS feed.
		$disableRSS = $this->getDisableRSS($request, $target);
		$request->setAttribute('disableRSS', $disableRSS);
		if (!$disableRSS)
		{
			$feedTitle = LocaleService::getInstance()->transFO('m.comment.frontoffice.rss-feed-title', array('ucf'), array('target' => $target->getLabel()));
			$this->getContext()->addRssFeed($feedTitle, LinkHelper::getActionUrl('comment', 'ViewFeed', array('targetId' => $target->getId())));
		}
		
		// Handle comments closing.
		$request->setAttribute('closeComments', $this->getCloseComments($request, $target));
		
		// Add link rel canonical.
		$this->addCanonical($target, $pageNumber, $request);
	}
	
	/**
	 * @param f_mvc_Request $request
	 * @param f_persistentdocument_PersistentDocument $target
	 * @return boolean
	 */
	protected function getDisableRSS($request, $target)
	{
		return false;
	}
	
	/**
	 * @param f_mvc_Request $request
	 * @param f_persistentdocument_PersistentDocument $target
	 * @return boolean
	 */
	protected function getCloseComments($request, $target)
	{
		return false;
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocument $target
	 * @param integer $pageNumber
	 * @param f_mvc_Request $request
	 */
	protected function addCanonical($target, $pageNumber, $request)
	{
		$this->getContext()->addCanonicalParam('page', $pageNumber > 1 ? $pageNumber : null, $this->getModuleName());
	}

	/**
	 * @return void
	 */
	public function onValidateInputFailed($request)
	{
		$this->loadSuccessView($request);
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
		// Check if the user is connected.
		$user = users_UserService::getInstance()->getCurrentFrontEndUser();
		if (!$this->allowNotRegistered() && $user === null)
		{
			$this->addError(LocaleService::getInstance()->transFO('m.comment.frontoffice.error-not-logged-in', array('ucf')));
			return false;
		}
		
		// Validation.
		$validationRules = BeanUtils::getBeanValidationRules('comment_persistentdocument_comment', null, array('label', 'targetdocumentmodel'));
		if ($this->isRatingRequired())
		{
			$validationRules[] = "rating{min:0;max:5}";
		}
		$isOk = $this->processValidationRules($validationRules, $request, $bean);

		// Captcha is tested only for not logged-in users.
		if ($user === null)
		{
			$code = change_Controller::getInstance()->getContext()->getRequest()->getModuleParameter('form', 'CHANGE_CAPTCHA');
			if (!FormHelper::checkCaptchaForKey($code, 'comment'))
			{
				$this->addError(LocaleService::getInstance()->transFO('m.comment.frontoffice.error-captcha', array('ucf')));
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
		$comment->setCreationdate(date_Calendar::getInstance());
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
		$this->saveComment($comment);

		$url = LinkHelper::getDocumentUrl($comment);
		change_Controller::getInstance()->redirectToUrl($url);
	}

	/**
	 * @param funcard_persistentdocument_comment $comment
	 */
	protected function saveComment($comment)
	{
		$tm = f_persistentdocument_TransactionManager::getInstance();
		try
		{
			$tm->beginTransaction();

			$comment->setWebsiteId(website_WebsiteService::getInstance()->getCurrentWebsite()->getId());
			$comment->save();
			
			// Ask validation.
			$comment->getDocumentService()->frontendValidation($comment);
			
			$tm->commit();
		}
		catch (Exception $e)
		{
			$tm->rollBack($e);
			throw $e;
		}
		
		$user = users_UserService::getInstance()->getCurrentUser();
		if ($user === null)
		{
			$comment->getDocumentService()->addPostedToSession($comment->getId());
		}
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
			$comment = comment_persistentdocument_comment::getInstanceById(intval($request->getParameter('commentId')));
			if (!$comment->isEvaluatedByCurrentUser())
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
			$comment = comment_persistentdocument_comment::getInstanceById(intval($request->getParameter('commentId')));
			if (!$comment->isEvaluatedByCurrentUser())
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
		$target = $this->getDocumentParameter();
		if (f_util_ClassUtils::methodExists($target->getDocumentService(), 'getTargetForComment'))
		{
			return $target->getDocumentService()->getTargetForComment($target);
		}
		return $target;
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
	 * @param integer $itemPerPage
	 * @param comment_persistentodcument_comment[] $allComments
	 */
	protected function getPageNumber($request, $itemPerPage, $allComments)
	{
		// If there is a page set, return it.
		$pageNumber = $request->getParameter(paginator_Paginator::PAGEINDEX_PARAMETER_NAME);
		if ($pageNumber)
		{
			if (floor(count($allComments) / $itemPerPage) + 1 >= $pageNumber)
			{
				return $pageNumber;
			}
		}

		// Else look for a comment id.
		$globalRequest = change_Controller::getInstance()->getRequest();
		$commentId = intval($globalRequest->getParameter('commentId'));
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
	 * @return boolean
	 */
	protected function allowNotRegistered()
	{
		$configuration = $this->getConfiguration();
		if (f_util_ClassUtils::methodExists($configuration, 'getAllowNotRegistered'))
		{
			return $this->getConfiguration()->getAllowNotRegistered();
		}
		return true;
	}

	/**
	 * @param f_persistentdocument_PersistentDocument $target
	 * @return comment_persistentdocument_comment[]
	 */
	protected function getCommentsListByTarget($target)
	{
		$globalRequest = change_Controller::getInstance()->getRequest();
		$ratingFilterValue = $globalRequest->getParameter('filter');
		$website = website_WebsiteService::getInstance()->getCurrentWebsite();
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
		$template = $this->getTemplate($shortViewName);
		if ($template !== null)
		{
			return $template;
		}
		$templateName = 'Comment-Block-CommentBase-'.$shortViewName;
		return $this->getTemplateByFullName('modules_comment', $templateName);
	}
}