<?php
/**
 * comment_patch_0351
 * @package modules.comment
 */
class comment_patch_0351 extends patch_BasePatch
{
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		$pp = f_persistentdocument_PersistentProvider::getInstance();
		$tm = f_persistentdocument_TransactionManager::getInstance();
		
		try 
		{
			$tm->beginTransaction();
			foreach (comment_CommentService::getInstance()->createQuery()->find() as $comment)
			{
				$comment->setContentsAsBBCode($comment->getContents());
				$pp->updateDocument($comment);
			}
			$tm->commit();
		}
		catch (Exception $e)
		{
			$tm->rollback($e);
		}
	}
}