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
	 * @param Integer $targetId
	 * @param Integer $ratingValue
	 * @param Integer $websiteId
	 * @return comment_persistentdocument_comment[]
	 */
	public function getPublishedByTargetId($targetId, $ratingValue = null, $websiteId = null)
	{
		$query = $this->createQuery();
		$query->add(Restrictions::published());
		$query->add(Restrictions::eq('targetId', $targetId));
		if ($websiteId !== null && $this->filterByWebsite())
		{
			$query->add(Restrictions::orExp(Restrictions::isNull('websiteId'), Restrictions::eq('websiteId', $websiteId)));
		}
		if ($ratingValue !== null)
		{
			$query->add(Restrictions::eq('rating', comment_RatingService::normalizeRating($ratingValue)));
		}
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
		$query->add(Restrictions::published());
		$query->add(Restrictions::eq('targetId', $targetId));
		if ($websiteId !== null && $this->filterByWebsite())
		{
			$query->add(Restrictions::orExp(Restrictions::isNull('websiteId'), Restrictions::eq('websiteId', $websiteId)));
		}
		if ($ratingValue !== null)
		{
			$query->add(Restrictions::eq('rating', comment_RatingService::normalizeRating($ratingValue)));
		}
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
		$query->add(Restrictions::published());
		$query->add(Restrictions::eq('targetId', $targetId));
		if ($websiteId !== null && $this->filterByWebsite())
		{
			$query->add(Restrictions::orExp(Restrictions::isNull('websiteId'), Restrictions::eq('websiteId', $websiteId)));
		}
		if ($ratingValue !== null)
		{
			$query->add(Restrictions::eq('rating', comment_RatingService::normalizeRating($ratingValue)));
		}
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
		$query = $this->createQuery()->add(Restrictions::published());
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
		//TODO: limit?
		/*$limit = ModuleService::getInstance()->getPreferenceValue('blog', 'rssMaxItemCount');
		if ($limit > 0)
		{
			$query->setMaxResults($limit);
		}*/
		$query->addOrder(Order::desc('document_creationdate'));
		
		$writer = new rss_FeedWriter();
		foreach ($query->find() as $post)
		{
			$writer->addItem($post);
		}
		return $writer;
	}
	

	/**
	 * @param Integer $targetId
	 * @return rss_FeedWriter
	 * @deprecated use getRSSFeedWriterByTargetId
	 */
	public function getRSSFeedWirterByTargetId($targetId)
	{
		return $this->getRSSFeedWriterByTargetId($targetId);
	}
	
	/**
	 * Shortcut method to update a comment after its been approved (used for evaluation counters...)
	 *
	 * @param comment_persistentdocument_comment $comment
	 */
	public function updateApprovedComment($comment)
	{
		if ($comment->getPersistentModel()->hasWorkflow())
		{
			$this->tm->beginTransaction();
			$relevancy = comment_RatingService::getInstance()->getRelevancyForComment($comment);
			$comment->setRelevancy($relevancy);
			$this->pp->updateDocument($comment);
			// TODO: dispatch event
			$this->tm->commit();
		}
		else
		{
			$comment->save();
		}
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
	 * Compute the rating average for the commented document
	 * @param Integer $targetId
	 * @param Integer $websiteId
	 * @return Float
	 */
	public function getRatingAverageByTargetId($targetId, $websiteId = null)
	{
		$query = $this->createQuery()->add(Restrictions::published());
		$query->add(Restrictions::eq('targetId', $targetId));
		if ($websiteId !== null && $this->filterByWebsite())
		{
			$query->add(Restrictions::orExp(Restrictions::isNull('websiteId'), Restrictions::eq('websiteId', $websiteId)));
		}
		$query->setProjection(Projections::avg('rating', 'avg'));
		$result = $query->find();
		return floatval($result[0]['avg']);
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
		$target = $document->getTarget();
		
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
	protected function postSave($document, $parentNodeId = null)
	{
		$target = $document->getTarget(); 
		$target->setMeta(comment_persistentdocument_comment::COMMENTED_META, "true");
		$this->pp->updateDocument($target);
	}
	
	/**
	 * @return Boolean
	 */
	private function filterByWebsite()
	{
		return (Framework::getConfigurationValue('modules/comment/filterByWebsite', 'true') == 'true');
	}
}