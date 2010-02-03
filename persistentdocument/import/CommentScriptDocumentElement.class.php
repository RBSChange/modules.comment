<?php
/**
 * comment_CommentScriptDocumentElement
 * @package modules.comment.persistentdocument.import
 */
class comment_CommentScriptDocumentElement extends import_ScriptDocumentElement
{
    /**
     * @return comment_persistentdocument_comment
     */
    protected function initPersistentDocument()
    {
    	$document = comment_CommentService::getInstance()->getNewDocumentInstance();
    	return $document;
    }
    
	public function endProcess()
	{
		$document = $this->getPersistentDocument();
		if ($document->getPublicationstatus() == 'DRAFT')
		{
			$document->getDocumentService()->activate($document->getId());
		}
	}
}