<?php
/**
 * comment_persistentdocument_comment
 * @package comment.persistentdocument
 */
class comment_persistentdocument_comment extends comment_persistentdocument_commentbase implements indexer_IndexableDocument, rss_Item
{
	/**
	 * Meta used to mark a document as commented by some comment
	 * @see comment_TargetDeletedListener
	 */
	const COMMENTED_META = "modules.comment.commented";
	
	/**
	 * Get the indexable document
	 *
	 * @return indexer_IndexedDocument
	 */
	public function getIndexedDocument()
	{
		$indexedDoc = new indexer_IndexedDocument();
		$indexedDoc->setId($this->getId());
		$indexedDoc->setDocumentModel('modules_comment/comment');
		$indexedDoc->setLabel($this->getLabel());
		$indexedDoc->setLang($this->getLang());
		$indexedDoc->setText($this->getFullTextForIndexation());
		return $indexedDoc;
	}
	
	/**
	 * @return String
	 */
	private function getFullTextForIndexation()
	{
		$fullText = $this->getAuthorName();
		$fullText .= ' ' . f_util_StringUtils::htmlToText($this->getContentsAsHtml());
		return f_util_StringUtils::htmlToText($fullText);
	}
	
	/**
	 * @return f_persistentdocument_PersistentDocument
	 */
	public function getTarget()
	{
		return DocumentHelper::getDocumentInstance($this->getTargetId());
	}
	
	/**
	 * @return String
	 */
	public function getTargetUrl()
	{
		return LinkHelper::getDocumentUrl($this->getTarget());
	}
	
	/**
	 * @return String
	 */
	public function getAnchor()
	{
		return 'comment-'.$this->getId();
	}
	
	/**
	 * @see http://fr.gravatar.com/site/implement/url
	 * @param Integer $size
	 * @param String $defaultImageUrl
	 * @param String $rating
	 * @return String 
	 */
	public function getGravatarUrl($size = '48', $defaultImageUrl = '', $rating = 'g')
	{
		$url = 'http://www.gravatar.com/avatar/'.md5($this->getEmail()).'?s='.$size.'&amp;r='.$rating;
		if ($defaultImageUrl)
		{
			$url .= '&amp;d='.urlencode($defaultImageUrl);
		}
		return $url;
	}

	/**
	 * @return String
	 */
	public function getContentsAsHtml()
	{
		$parser = new website_BBCodeParser();
		return $parser->convertXmlToHtml($this->getContents());
	}

	/**
	 * @return string
	 */
	public function getContentsAsBBCode()
	{
		$parser = new website_BBCodeParser();
		return $parser->convertXmlToBBCode($this->getContents());
	}

	/**
	 * @param string $bbcode
	 */
	public function setContentsAsBBCode($bbcode)
	{
		$parser = new website_BBCodeParser();
		$this->setContents($parser->convertBBCodeToXml($bbcode, 'default'));
	}
	
	/**
	 * @return String
	 */
	public function getRSSLabel()
	{
		return $this->getLabel();
	}
	
	/**
	 * @return String
	 */
	public function getRSSDescription()
	{
		return $this->getContentsAsHtml();
	}
	
	/**
	 * @return String
	 */
	public function getRSSGuid()
	{
		return LinkHelper::getDocumentUrl($this);
	}
	
	/**
	 * @return String
	 */
	public function getRSSDate()
	{
		return $this->getCreationdate();
	}
	
	/**
	 * @return Integer
	 */
	public function getEvaluationcount()
	{
		return intval($this->getUsefulcount()) + intval($this->getUselesscount());
	}
	
	/**
	 * @return String
	 */	
	public function getRatingImageUrl()
	{
		return comment_RatingService::getInstance()->getRatingImageUrlByRating($this->getRating());
	}
	
	/**
	 * @return String
	 */
	public function getRatingImageAlt()
	{
		return comment_RatingService::getInstance()->getRatingImageAltByRating($this->getRating());
	}
	
	/**
	 * @return void
	 */
	public function markAsEvaluatedForCurrentUser()
	{
		$commentId = $this->getId();
		if (isset($_COOKIE['forbidden-comment-evaluations']))
		{
			$forbiddenIds = explode(',', $_COOKIE['forbidden-comment-evaluations']);
			
			if (!in_array($commentId, $forbiddenIds))
			{
				$forbiddenIds[] = $commentId;
			}
		}
		else
		{
			$forbiddenIds = array($commentId);
		}
		$_COOKIE['forbidden-comment-evaluations'] = implode(",", $forbiddenIds);
		setcookie('forbidden-comment-evaluations', $_COOKIE['forbidden-comment-evaluations'], time() + 2592000);
	}
	
	/**
	 * @return Boolean
	 */
	public function isEvaluatedByCurrentUser()
	{
		if (isset($_COOKIE['forbidden-comment-evaluations']))
		{
			$forbiddenIds = explode(',', $_COOKIE['forbidden-comment-evaluations']);
			return in_array($this->getId(), $forbiddenIds);
		}
		return false;
	}
	
	/**
	 * @return String
	 */
	public function getFullAuthorLabel()
	{
		$ls = LocaleService::getInstance();
		if ($this->getAuthorid() !== null)
		{
			$substitutions =  array('label' => $this->getAuthorName(), 'authorId' => $this->getAuthorid());
			$fullAuthorLabel = $ls->transFO('m.comment.bo.workflow.validatecomment.fullauthorauthentifiedlabel', array('ucf'), $substitutions);
		}
		else
		{
			$substitutions =  array('label' => $this->getAuthorName());
			$fullAuthorLabel = $ls->transFO('m.comment.bo.workflow.validatecomment.fullauthoranonymouslabel', array('ucf'), $substitutions);
		}
		return $fullAuthorLabel;
	}
	
	/**
	 * @return String
	 */
	public final function getValidatePermissionName()
	{
		return $this->getDocumentService()->getValidatePermissionNameByTarget($this->getTarget());
	}
	
	/**
	 * @return tast_persistentdocument_usertask
	 */
	public function getValidationTask()
	{
		if ($this->getPublicationstatus() != 'WORKFLOW')
		{
			return null;
		}
			
		$user = users_UserService::getInstance()->getCurrentUser();
		if ($user !== null)
		{
			return TaskHelper::getTaskForUserIdByDocumentId($user, $this->getId(), 'COMMENT_VALIDATION');
		}
		return null;
	}
	
	/**
	 * @param integer $maxLength
	 * @return string
	 */
	public function getSummary($maxLength = 80)
	{
		return f_util_StringUtils::shortenString(f_util_StringUtils::htmlToText($this->getContentsAsHtml()), $maxLength);
	}
}