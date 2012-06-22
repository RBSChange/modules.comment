<?php
/**
 * comment_persistentdocument_comment
 * @package comment.persistentdocument
 */
class comment_persistentdocument_comment extends comment_persistentdocument_commentbase implements rss_Item
{
	/**
	 * Meta used to mark a document as commented by some comment
	 * @see comment_TargetDeletedListener
	 */
	const COMMENTED_META = "modules.comment.commented";
	
	/**
	 * @return f_persistentdocument_PersistentDocument
	 */
	public function getTarget()
	{
		return DocumentHelper::getDocumentInstance($this->getTargetId());
	}
	
	/**
	 * @return string
	 */
	public function getTargetUrl()
	{
		return LinkHelper::getDocumentUrl($this->getTarget());
	}
	
	/**
	 * @return string
	 */
	public function getAnchor()
	{
		return 'comment-'.$this->getId();
	}

	/**
	 * @return string
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
		$this->setContents($parser->convertBBCodeToXml($bbcode, $parser->getModuleProfile('comment')));
	}
	
	/**
	 * @return string
	 */
	public function getRSSLabel()
	{
		return $this->getLabel();
	}
	
	/**
	 * @return string
	 */
	public function getRSSDescription()
	{
		return $this->getContentsAsHtml();
	}
	
	/**
	 * @return string
	 */
	public function getRSSGuid()
	{
		return LinkHelper::getPermalink($this);
	}
	
	/**
	 * @return string
	 */
	public function getRSSLink()
	{
		return LinkHelper::getDocumentUrl($this);
	}
	
	/**
	 * @return string
	 */
	public function getRSSDate()
	{
		return $this->getCreationdate();
	}
	
	/**
	 * @return integer
	 */
	public function getEvaluationcount()
	{
		return intval($this->getUsefulcount()) + intval($this->getUselesscount());
	}
	
	/**
	 * @return string
	 */	
	public function getRatingImageUrl()
	{
		return comment_RatingService::getInstance()->getRatingImageUrlByRating($this->getRating());
	}
	
	/**
	 * @return string
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
	 * @return boolean
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
	 * @return string
	 */
	public function getFullAuthorLabel()
	{
		$ls = LocaleService::getInstance();
		if ($this->getAuthorid() !== null)
		{
			$substitutions =  array('label' => $this->getAuthorName(), 'authorId' => $this->getAuthorid());
			$fullAuthorLabel = $ls->trans('m.comment.bo.workflow.validatecomment.fullauthorauthentifiedlabel', array('ucf'), $substitutions);
		}
		else
		{
			$substitutions =  array('label' => $this->getAuthorName());
			$fullAuthorLabel = $ls->trans('m.comment.bo.workflow.validatecomment.fullauthoranonymouslabel', array('ucf'), $substitutions);
		}
		return $fullAuthorLabel;
	}
	
	/**
	 * @return string
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
		return f_util_StringUtils::shortenString(f_util_HtmlUtils::htmlToText($this->getContentsAsHtml()), $maxLength);
	}
	
	// Deprecated
	
	/**
	 * @deprecated use change:avatar
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
}