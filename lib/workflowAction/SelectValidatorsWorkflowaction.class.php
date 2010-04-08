<?php
/**
 * @author intportg
 * @package modules.comment.lib.workflowAction
 */
class comment_SelectValidatorsWorkflowaction extends workflow_BaseWorkflowaction
{
	/**
	 * This method will execute the action.
	 * @return boolean true if the execution end successfully, false in error case.
	 */
	public function execute()
	{	
		$comment = $this->getDocument();
		$actorsIds = $comment->getDocumentService()->getValidators($comment);
		$this->setCaseParameter('__NEXT_ACTORS_IDS', $actorsIds);
		$this->setCaseParameter('authorEmail', $comment->getEmail());
		$this->setCaseParameter('authorName', $comment->getAuthorNameAsHtml());
		$websiteUrl = $comment->getAuthorwebsiteurl();
		$this->setCaseParameter('authorWebsiteUrl', $websiteUrl);
		$this->setCaseParameter('authorWebsiteLink', f_util_StringUtils::isEmpty($websiteUrl) ? '' : '<a href="' . $websiteUrl . '">' . $websiteUrl . '</a>');
		$this->setCaseParameter('authorIp', $_SERVER['REMOTE_ADDR']);
		$this->setCaseParameter('commentContent', $comment->getContentsAsHtml());
		$this->setCaseParameter('commentRating', $comment->getRating());
		$target = $comment->getTarget();
		$targetLabel = $target->getLabelAsHtml();
		$targetUrl = LinkHelper::getDocumentUrl($target);
		$this->setCaseParameter('targetLabel', $targetLabel);
		$this->setCaseParameter('targetUrl', $targetUrl);
		$this->setCaseParameter('targetLink', '<a href="' . $targetUrl . '">' . $targetLabel . '</a>');
		$this->setCaseParameter('targetType', f_Locale::translate($target->getPersistentModel()->getLabel()));
		return parent::execute();
	}
}