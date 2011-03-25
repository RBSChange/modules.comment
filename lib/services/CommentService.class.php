<?php
/**
 * comment_CommentService
 * @package modules.comment
 */
class comment_CommentService extends f_persistentdocument_DocumentService
{
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
		$rs = $ps->getRoleServiceByModuleName($this->getModuleNameByTarget($target));
		$result = ($rs->isFrontEndPermission($permission) && $ps->hasPermission($user, $permission, $target->getId()));
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
			$ps = f_permission_PermissionService::getInstance();
			$target = DocumentHelper::getDocumentInstance($targetId);
			if ($ps->hasPermission($user, $this->getValidatePermissionNameByTarget($target), $targetId))
			{
				$query->add(Restrictions::in('publicationstatus', array('WORKFLOW', 'PUBLICATED', 'ACTIVE')));
			}
			else 
			{
				$query->add(Restrictions::orExp(Restrictions::published(), Restrictions::eq('authorid', $user->getId())));
			}
		}
		else
		{
			$query->add(Restrictions::published());
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
		//TODO: parameter?
		/*$limit = ModuleService::getInstance()->getPreferenceValue('blog', 'rssMaxItemCount');
		if ($limit > 0)
		{
			$query->setMaxResults($limit);
		}*/
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
		$query = $this->createQuery()->add(Restrictions::published());
		if ($this->filterByWebsite())
		{
			$query->add(Restrictions::orExp(Restrictions::isNull('websiteId'), Restrictions::eq('websiteId', $websiteId)));
		}
		//TODO: parameter?
		/*$limit = ModuleService::getInstance()->getPreferenceValue('blog', 'rssMaxItemCount');
		if ($limit > 0)
		{
			$query->setMaxResults($limit);
		}*/
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
		$document->setMeta('author_IP', comment_ModuleService::getInstance()->getIp());
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
		$document->setLabel(f_Locale::translate('&modules.comment.document.comment.Label-pattern;', $replacements));
		$document->setTargetdocumentmodel($target->getPersistentModel()->getOriginalModelName());
		
		// Fix bbcode content.
		if ($document->isPropertyModified('contents'))
		{
			$document->setContents(website_BBCodeService::getInstance()->fixContent($document->getContents()));
		}
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
	 * @param f_persistentdocument_PersistentDocument $target
	 * @return f_persistentdocument_criteria_Query
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
	 * @return Boolean
	 */
	private function filterByWebsite()
	{
		return (Framework::getConfigurationValue('modules/comment/filterByWebsite', 'true') == 'true');
	}
	
	// Deprecated.
	
	/**
	 * @deprecated use getRSSFeedWriterByTargetId
	 */
	public function getRSSFeedWirterByTargetId($targetId)
	{
		return $this->getRSSFeedWriterByTargetId($targetId);
	}
}