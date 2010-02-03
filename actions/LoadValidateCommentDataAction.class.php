<?php
/**
 * comment_LoadValidateCommentDataAction
 * @package modules.comment.actions
 */
class comment_LoadValidateCommentDataAction extends task_LoadDataBaseAction
{
	/**
	 * @param comment_persistentdocument_comment $document
	 * @return Array
	 */
	protected function getInfoForDocument($document)
	{
		$data = array();
		$data['label'] = $document->getLabel();
		$data['authorLabel'] = $document->getFullAuthorLabel();
		$data['authorEmail'] = $document->getEmail();
		$data['authorWebsite'] = $document->getAuthorwebsiteurl();
		$data['targetUrl'] = $document->getTargetUrl();
		$data['targetLabel'] = $document->getTarget()->getLabelAsHtml();
		$data['rating'] = $document->getRating();
		$data['contents'] = $document->getContentsAsHtml();
		return $data;
	}
}