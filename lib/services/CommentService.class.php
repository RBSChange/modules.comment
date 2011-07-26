<?php
/**
 * comment_CommentService
 * @package modules.comment
 */
class comment_CommentService extends f_persistentdocument_DocumentService
{
	const SESSION_NAMESPACE = 'm_comment';
	
	/**
	 * @var comment_CommentService
	 */
	private static $instance;

	/**
	 * @return comment_CommentService
	 */
	public static function getInstance()
	{
		if (self::$instance === null)
		{
			self::$instance = self::getServiceClassInstance(get_class());
		}
		return self::$instance;
	}

	/**
	 * @return comment_persistentdocument_comment
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_comment/comment');
	}

	/**
	 * Create a query based on 'modules_comment/comment' model.
	 * Return document that are instance of modules_comment/comment,
	 * including potential children.
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_comment/comment');
	}
	
	/**
	 * Create a query based on 'modules_comment/comment' model.
	 * Only documents that are strictly instance of modules_comment/comment
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->pp->createQuery('modules_comment/comment', false);
	}
	
	/**
	 * Please always call this method if you want to validate a new comment
	 * @param comment_persistentdocument_comment $comment
	 */
	public function frontendValidation($comment)
	{
		if (!$comment->getPersistentModel()->hasWorkflow())
		{
			$comment->activate();
			return;
		}
			
		$user = users_UserService::getInstance()->getCurrentFrontEndUser();
		if ($user !== null)
		{
			$target = $comment->getTarget();
			if ($this->hasPermission($user, $this->getValidatePermissionNameByTarget($target), $target))
			{
				$comment->activate();
				return;
			}
			if ($user->getId() == $comment->getAuthorid())
			{
				if ($this->hasPermission($user, $this->getTrustedPermissionNameByTarget($target), $target))
				{
					$comment->activate();
					return;
				}
			}
		}
		
		$comment->getDocumentService()->createWorkflowInstance($comment->getId(), array());
	}
		
	/**
	 * @param users_persistentdocument_user $user
	 * @param String $permission
	 * @param f_persistentdocument_PersistentDocument $target
	 * @return boolean
	 */
	private function hasPermission($user, $permission, $target)
	{
		$ps = f_permission_PermissionService::getInstance();
		$result = $ps->hasExplicitPermission($user, $permission, $target->getId());
		return $result;
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocument $target
	 * @return String
	 */
	public function getValidatePermissionNameByTarget($target)
	{
		return 'modules_' . $this->getModuleNameByTarget($target) . '.Validate.comment';
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocument $target
	 * @return String
	 */
	public function getTrustedPermissionNameByTarget($target)
	{
		return 'modules_' . $this->getModuleNameByTarget($target) . '.Trusted.comment';
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocument $target
	 * @return string
	 */
	protected function getModuleNameByTarget($target)
	{
		$model = $target->getPersistentModel();
		$rootModelName = f_util_ArrayUtils::firstElement($model->getAncestorModelNames());
		if ($rootModelName !== null)
		{
			$rootModel = f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName($rootModelName);
			$moduleName = $rootModel->getModuleName();
		}
		else 
		{
			$moduleName = $model->getModuleName();
		}
		return $moduleName;
	}
	
	/**
	 * @param comment_persistentdocument_comment $comment
	 * @return Integer[]
	 */
	public function getValidators($comment)
	{
		$ps = f_permission_PermissionService::getInstance();
		$permission = $this->getValidatePermissionNameByTarget($comment->getTarget());
		$package = f_util_ArrayUtils::firstElement(explode('.', $permission));
		$definitionPoint = $ps->getDefinitionPointForPackage($comment->getTargetId(), $package);
		return $ps->getAccessorIdsForPermissionAndDocumentId($permission, $definitionPoint);
	}
	
	/**
	 * @param Integer $targetId
	 * @param Integer $offset
	 * @param Integer $limit
	 * @param Integer $websiteId
	 * @return comment_persistentdocument_comment[]
	 */
	public function getByTargetId($targetId, $offset = null, $limit = null, $websiteId = null)
	{
		$query = $this->createQuery();
		$query->add(Restrictions::eq('targetId', $targetId));
		$query->addOrder(Order::desc('document_creationdate'));
		if ($websiteId !== null && $this->filterByWebsite())
		{
			$query->add(Restrictions::orExp(Restrictions::isNull('websiteId'), Restrictions::eq('websiteId', $websiteId)));
		}
		if ($offset !== null)
		{
			$query->setFirstResult($offset);
		}
		if ($limit !== null)
		{
			$query->setMaxResults($limit);
		}
		return $query->find();
	}
	
	/**
	 * @param Integer $targetId
	 * @param Integer $websiteId
	 * @return comment_persistentdocument_comment
	 */
	public function getLastByTargetId($targetId, $websiteId = null)
	{
		return f_util_ArrayUtils::firstElement($this->getByTargetId($targetId, 0, 1, $websiteId));
	}
	
	/**
	 * @param Integer $targetId
	 * @param Integer $websiteId
	 * @return Integer
	 */
	public function getCountByTargetId($targetId, $websiteId = null)
	{
		$query = $this->createQuery();
		$query->add(Restrictions::eq('targetId', $targetId));
		if ($websiteId !== null && $this->filterByWebsite())
		{
			$query->add(Restrictions::orExp(Restrictions::isNull('websiteId'), Restrictions::eq('websiteId', $websiteId)));
		}
		$query->setProjection(Projections::rowCount('count'));
		$row = $query->findUnique();
		return $row['count'];
	}
	
	/**
	 * @param f_persistentdocument_criteria_Query $query
	 * @param Integer $targetId
	 * @param Integer $ratingValue
	 * @param Integer $websiteId
	 */
	private function addVisibilityRestrictions($query, $targetId, $ratingValue, $websiteId)
	{
		$query->add(Restrictions::eq('targetId', $targetId));
				
		$user = users_UserService::getInstance()->getCurrentFrontEndUser();
		if ($user !== null)
		{
			$target = DocumentHelper::getDocumentInstance($targetId);
			if ($this->hasPermission($user, $this->getValidatePermissionNameByTarget($target), $target))
			{
				$query->add($this->getValidatorVisibilityRestriction($user));
			}
			else 
			{
				$query->add($this->getVisitorVisibilityRestriction($user));
			}
		}
		else
		{
			$query->add($this->getAnonymousVisibilityRestriction());
		}
		
		if ($websiteId !== null && $this->filterByWebsite())
		{
			$query->add(Restrictions::orExp(Restrictions::isNull('websiteId'), Restrictions::eq('websiteId', $websiteId)));
		}
		
		if ($ratingValue !== null)
		{
			$query->add(Restrictions::eq('rating', comment_RatingService::normalizeRating($ratingValue)));
		}
	}
	
	/**
	 * @param users_persistentdocument_user $user
	 * @return f_persistentdocument_criteria_Criterion
	 */
	protected function getValidatorVisibilityRestriction($user)
	{
		return Restrictions::in('publicationstatus', array('WORKFLOW', 'PUBLICATED', 'ACTIVE'));
	}
	
	/**
	 * @param users_persistentdocument_user $user
	 * @return f_persistentdocument_criteria_Criterion
	 */
	protected function getVisitorVisibilityRestriction($user)
	{
		return Restrictions::orExp(Restrictions::published(), Restrictions::andExp(Restrictions::eq('authorid', $user->getId()), $this->getAuthorVisibilityRestriction($user)));
	}
	
	/**
	 * @return f_persistentdocument_criteria_Criterion
	 */
	protected function getAnonymousVisibilityRestriction()
	{
		$postedIds = $this->getPostedFromSession();
		if (f_util_ArrayUtils::isNotEmpty($postedIds))
		{
			return Restrictions::orExp(Restrictions::published(), Restrictions::andExp(Restrictions::in('id', $postedIds), $this->getAuthorVisibilityRestriction()));
		}
		else 
		{
			return Restrictions::published();
		}
	}
	
	/**
	 * @param users_persistentdocument_user $user
	 * @return f_persistentdocument_criteria_Criterion
	 */
	protected function getAuthorVisibilityRestriction($user = null)
	{
		return Restrictions::in('publicationstatus', array('WORKFLOW', 'PUBLICATED', 'ACTIVE'));
	}
	
	/**
	 * @param Integer $targetId
	 * @param Integer $ratingValue
	 * @param Integer $websiteId
	 * @return comment_persistentdocument_comment[]
	 */
	public function getPublishedByTargetId($targetId, $ratingValue = null, $websiteId = null)
	{
		$query = $this->createQuery();
		$this->addVisibilityRestrictions($query, $targetId, $ratingValue, $websiteId);
		$query->addOrder(Order::asc('document_creationdate'));
		return $query->find();
	}
	
	/**
	 * @param Integer $targetId
	 * @param Integer $ratingValue
	 * @param Integer $websiteId
	 * @return comment_persistentdocument_comment[]
	 */
	public function getPublishedByTargetIdOrderByRating($targetId, $ratingValue = null, $websiteId = null)
	{
		$query = $this->createQuery();
		$this->addVisibilityRestrictions($query, $targetId, $ratingValue, $websiteId);
		$query->addOrder(Order::desc('rating'));
		return $query->find();
	}
	
	/**
	 * @param Integer $targetId
	 * @param Integer $ratingValue
	 * @param Integer $websiteId
	 * @return comment_persistentdocument_comment[]
	 */
	public function getPublishedByTargetIdOrderByRelevancy($targetId, $ratingValue = null, $websiteId = null)
	{
		$query = $this->createQuery();
		$this->addVisibilityRestrictions($query, $targetId, $ratingValue, $websiteId);
		$query->addOrder(Order::desc('relevancy'));
		return $query->find();
	}
	
	/**
	 * @param Integer $targetId
	 * @param Integer $websiteId
	 * @return Integer
	 */
	public function getPublishedCountByTargetId($targetId, $websiteId = null)
	{
		$query = $this->createQuery();
		$this->addVisibilityRestrictions($query, $targetId, null, $websiteId);
		$query->setProjection(Projections::rowCount('count'));
		$row = $query->findUnique();
		return $row['count'];
	}
	
	/**
	 * @param Integer $targetId
	 */
	public function deleteByTargetId($targetId)
	{
		$query = $this->createQuery()->add(Restrictions::eq('targetId', $targetId));
		foreach ($query->find() as $comment)
		{
			$comment->delete();
		}
	}
	
	/**
	 * @param Integer $targetId
	 * @param users_persistentdocument_user $user
	 */
	public function hasCommented($targetId, $user)
	{
		$query = $this->createQuery();
		$query->add(Restrictions::eq('targetId', $targetId));
		$query->add(Restrictions::eq('authorid', $user->getId()));
		$query->setProjection(Projections::rowCount('count'));
		$row = $query->findUnique();
		return $row['count'] > 0;
	}
	
	/**
	 * @param Integer $targetId
	 */
	public function hasCurrentUserCommented($targetId)
	{
		return $this->hasCommented($targetId, users_UserService::getInstance()->getCurrentFrontEndUser());
	}
	
	/**
	 * @param Integer $targetId
	 */
	public function refreshPublicationByTargetId($targetId)
	{
		$query = $this->createQuery()->add(Restrictions::eq('targetId', $targetId));
		foreach ($query->find() as $comment)
		{
			$this->publishDocumentIfPossible($comment);
		}
	}
	
	/**
	 * @param Integer $targetId
	 * @param Integer $websiteId
	 * @return rss_FeedWriter
	 */
	public function getRSSFeedWriterByTargetId($targetId, $websiteId = null)
	{
		$query = $this->createQuery()->add(Restrictions::published());
		$query->add(Restrictions::eq('targetId', $targetId));
		if ($websiteId !== null && $this->filterByWebsite())
		{
			$query->add(Restrictions::orExp(Restrictions::isNull('websiteId'), Restrictions::eq('websiteId', $websiteId)));
		}
		$user = users_FrontenduserService::getInstance()->getCurrentFrontEndUser();
		
		$target = DocumentHelper::getDocumentInstance($targetId);
		$targetService = $target->getDocumentService();
		if (!f_util_ClassUtils::methodExists($targetService, "canViewCommentsRSS") ||
			!$targetService->canViewCommentsRSS($target, $user))
		{
			$query->add(Restrictions::eq("private", false));
		}
		$query->setMaxResults(100);
		$query->addOrder(Order::desc('document_creationdate'));
		
		$writer = new rss_FeedWriter();
		foreach ($query->find() as $post)
		{
			$writer->addItem($post);
		}
		return $writer;
	}
	
	/**
	 * @param Integer $websiteId
	 * @return rss_FeedWriter
	 */
	public function getRSSFeedWriterByWebsiteId($websiteId)
	{
		$query = $this->createQuery()->add(Restrictions::published())
			->add(Restrictions::eq("private", false))
			->add(Restrictions::orExp(Restrictions::isNull('websiteId'), Restrictions::eq('websiteId', $websiteId)));
		$query->setMaxResults(100);
		$query->addOrder(Order::desc('document_creationdate'));
		
		$writer = new rss_FeedWriter();
		foreach ($query->find() as $post)
		{
			$writer->addItem($post);
		}
		return $writer;
	}
	
	/**
	 * Shortcut method to update a comment after its been approved (used for evaluation counters...)
	 *
	 * @param comment_persistentdocument_comment $comment
	 */
	public function updateApprovedComment($comment)
	{
		$modifiedProperties = $comment->getOldValues();
		$modifiedPropertyNames = $comment->getModifiedPropertyNames();
		try 
		{
			$this->tm->beginTransaction();
			$relevancy = comment_RatingService::getInstance()->getRelevancyForComment($comment);
			$comment->setRelevancy($relevancy);
			$this->pp->updateDocument($comment);
			$this->tm->commit();
		}
		catch (Exception $e)
		{
			$this->tm->rollBack($e);
			throw $e;
		}		
		$params = array('document' => $comment, 'modifiedPropertyNames' => $modifiedPropertyNames, 'oldPropertyValues' => $modifiedProperties);
		f_event_EventManager::dispatchEvent('persistentDocumentUpdated', $this,	$params);
	}
	
	/**
	 * @param Integer $targetId
	 * @param Integer $websiteId
	 * @return Integer[]
	 */
	public function getRatingDistributionByTargetId($targetId, $websiteId = null)
	{
		$result = array(0, 0, 0, 0, 0, 0);
		$query = $this->createQuery()->add(Restrictions::published());
		$query->add(Restrictions::eq('targetId', $targetId));
		if ($websiteId !== null && $this->filterByWebsite())
		{
			$query->add(Restrictions::orExp(Restrictions::isNull('websiteId'), Restrictions::eq('websiteId', $websiteId)));
		}
		$query->setProjection(Projections::property('rating'));
		foreach ($query->find() as $line)
		{
			++$result[intval($line['rating'])];
		}
		return $result;
	}
	
	/**
	 * Compute the rating average for the commented document.
	 * @param Integer $targetId
	 * @param Integer $websiteId
	 * @return float
	 */
	public function getRatingAverageByTargetId($targetId, $websiteId = null)
	{
		$query = $this->getRatingBaseQuery($targetId, $websiteId);
		$query->setProjection(Projections::avg('rating', 'avg'));
		return floatval(f_util_ArrayUtils::firstElement($query->findColumn('avg')));
	}
	
	/**
	 * Get the number of comment with a rating on a target document.
	 * @param Integer $targetId
	 * @param Integer $websiteId
	 * @return integer
	 */
	public function getRatingCountByTargetId($targetId, $websiteId = null)
	{
		$query = $this->getRatingBaseQuery($targetId, $websiteId);
		$query->setProjection(Projections::rowCount('count'));
		return intval(f_util_ArrayUtils::firstElement($query->findColumn('count')));
	}
	
	/**
	 * @param Integer $targetId
	 * @param Integer $websiteId
	 * @return f_persistentdocument_criteria_Query
	 */
	private function getRatingBaseQuery($targetId, $websiteId)
	{
		$query = $this->createQuery()->add(Restrictions::published());
		$query->add(Restrictions::eq('targetId', $targetId));
		if ($websiteId !== null && $this->filterByWebsite())
		{
			$query->add(Restrictions::orExp(Restrictions::isNull('websiteId'), Restrictions::eq('websiteId', $websiteId)));
		}
		$query->add(Restrictions::gt('rating', 0));
		return $query; 
	}

	/**
	 * @param comment_persistentdocument_comment $document
	 * @return Boolean
	 */
	public function isPublishable($document)
	{
		return parent::isPublishable($document) && $document->getTarget()->isPublished();
	}
	
	/**
	 * @param catalog_persistentdocument_productdeclination $document
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal).
	 * @return void
	 */
	protected function preInsert($document, $parentNodeId)
	{
		$document->setMeta('author_IP', RequestContext::getInstance()->getClientIp());
		$document->setInsertInTree(false);
		parent::preInsert($document, $parentNodeId);
	}
	
	/**
	 * @param comment_persistentdocument_comment $document
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal => can be null !).
	 * @return void
	 */
	protected function preSave($document, $parentNodeId = null)
	{
		// This may not be done in preInsert because preSave is executed before and requires these values.
		if ($document->getTargetId() === null && $parentNodeId !== null)
		{
			$document->setTargetId($parentNodeId);
			$document->setTargetdocumentmodel(DocumentHelper::getDocumentInstance($parentNodeId)->getDocumentModelName());			
		}
		
		$target = $document->getTarget();
		$replacements = array(
			'target' => f_util_StringUtils::shortenString($target->getLabel(), 125),
			'author' => f_util_StringUtils::shortenString($document->getAuthorName(), 75)
		);
		$document->setLabel(LocaleService::getInstance()->transFO('m.comment.document.comment.label-pattern', array('ucf'), $replacements));
		$document->setTargetdocumentmodel($target->getPersistentModel()->getName());
	}
	
	/**
	 * @param comment_persistentdocument_comment $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
	protected function postSave($document, $parentNodeId)
	{
		$target = $document->getTarget(); 
		$target->setMeta(comment_persistentdocument_comment::COMMENTED_META, "true");
		$target->saveMeta();
	}

	/**
	 * @return Boolean
	 */
	private function filterByWebsite()
	{
		return (Framework::getConfigurationValue('modules/comment/filterByWebsite', 'true') == 'true');
	}
		
	/**
	 * @param website_UrlRewritingService $urlRewritingService
	 * @param comment_persistentdocument_comment $document
	 * @param website_persistentdocument_website $website
	 * @param string $lang
	 * @param array $parameters
	 * @return f_web_Link | null
	 */
	public function getWebLink($urlRewritingService, $document, $website, $lang, $parameters)
	{
		$parameters['commentId'] = $document->getId();
		$link = $urlRewritingService->getDocumentLinkForWebsite($document->getTarget(), $website, $lang, $parameters);
		if ($link) {$link->setFragment($document->getAnchor());}
		return $link;
	}
	
	/**
	 * @param website_persistentdocument_website $website
	 * @param string $lang
	 * @param string $modelName
	 * @param integer $offset
	 * @param integer $chunkSize
	 * @return comment_persistentdocument_comment[]
	 */
	public function getDocumentForSitemap($website, $lang, $modelName, $offset, $chunkSize)
	{
		return array();
	}
	
	/**
	 * @param integer $commentId
	 */
	public function addPostedToSession($commentId)
	{
		$session = Controller::getInstance()->getContext()->getUser();
		$ids = $session->getAttribute('postedComments', self::SESSION_NAMESPACE);
		if (!is_array($ids) || !in_array($commentId, $ids))
		{
			$ids[] = $commentId;
			$session->setAttribute('postedComments', $ids, self::SESSION_NAMESPACE);
		}
	}
	
	/**
	 * @return integer[]
	 */
	public function getPostedFromSession()
	{
		$session = Controller::getInstance()->getContext()->getUser();
		return $session->getAttribute('postedComments', self::SESSION_NAMESPACE);
	}
	
	/**
	 * @param array $array an associative array containing enties for 'comment' and 'specificParams'
	 */
	public function getNotificationParameters($array)
	{
		/* @var $comment comment_persistentdocument_comment */ 
		$comment = $array['comment'];
		
		$replacements = array();
		$replacements['commentId'] = $comment->getId();
		$replacements['commentLabel'] = $comment->getLabelAsHtml();
		$replacements['commentContent'] = $comment->getContentsAsHtml();
		$replacements['commentRating'] = $comment->getRating();
		$replacements['commentCreationDate'] = date_Formatter::toDefaultDate($comment->getUICreationdate());
		
		$target = $comment->getTarget();
		$replacements['targetId'] = $target->getId();
		$replacements['targetLabel'] = $target->getLabelAsHtml();		
		$replacements['targetUrl'] = LinkHelper::getDocumentUrl($target);
		$replacements['targetType'] = f_Locale::translate($target->getPersistentModel()->getLabel());
				
		$replacements['authorEmail'] = $comment->getEmail();
		$replacements['authorName'] = $comment->getAuthorNameAsHtml();
		$replacements['authorWebsiteUrl'] = $comment->getAuthorwebsiteurl();
		$replacements['authorWebsiteLink'] = $replacements['authorWebsiteUrl'] ? '' : '<a href="' . $replacements['authorWebsiteUrl'] . '">' . $replacements['authorWebsiteUrl'] . '</a>';
		$replacements['authorIp'] = $comment->getMeta('author_IP');
		
		// For compatibility...
		$replacements['documentId'] = $replacements['commentId'];
		$replacements['documentLabel'] = $replacements['commentLabel'];
		$replacements['documentCreationDate'] = $replacements['commentCreationDate'];
		$replacements['targetLink'] = '<a href="' . $replacements['targetUrl'] . '">' . $replacements['targetLabel'] . '</a>';
		
		// Add specific params.
		if (isset($array['specificParams']) && is_array($array['specificParams']))
		{
			$replacements = array_merge($replacements, $array['specificParams']);
		}
		return $replacements;
	}
	
	// Deprecated.
	
	/**
	 * @deprecated (will be removed in 4.0) use addVisibilityRestrictions
	 */
	protected function addPublishedCommentRestriction($query, $target)
	{
		$user = users_UserService::getInstance()->getCurrentFrontEndUser();
		if ($user === null)
		{
			$query->add(Restrictions::published());
		}
		else if (!f_permission_PermissionService::getInstance()->hasFrontEndPermission($user, 'modules_' . $target->getPersistentModel()->getModuleName() . '.Validate.comment', $target->getId()))
		{
			$query->add(Restrictions::orExp(
				Restrictions::published(),
				Restrictions::eq('authorId', $user->getId())
			));
		}
		return $query;
	}
	
	/**
	 * @deprecated (will be removed in 4.0) use getRSSFeedWriterByTargetId
	 */
	public function getRSSFeedWirterByTargetId($targetId)
	{
		return $this->getRSSFeedWriterByTargetId($targetId);
	}
}