<?php
class comment_TargetListener
{
	/**
	 * @param mixed $sender
	 * @param array $params
	 */
	function onPersistentDocumentDeleted($sender, $params)
	{
		$document = $params["document"];
		if ($document->hasMeta(comment_persistentdocument_comment::COMMENTED_META))
		{
			comment_CommentService::getInstance()->deleteByTargetId($document->getId());
		}
	}
	
	/**
	 * @param mixed $sender
	 * @param array $params
	 */
	function onPersistentDocumentPublished($sender, $params)
	{
		$document = $params["document"];
		if ($document->hasMeta(comment_persistentdocument_comment::COMMENTED_META))
		{
			comment_CommentService::getInstance()->refreshPublicationByTargetId($document->getId());
		}
	}
	
	/**
	 * @param mixed $sender
	 * @param array $params
	 */
	function onPersistentDocumentUnpublished($sender, $params)
	{
		$document = $params["document"];
		if ($document->hasMeta(comment_persistentdocument_comment::COMMENTED_META))
		{
			comment_CommentService::getInstance()->refreshPublicationByTargetId($document->getId());
		}
	}
}