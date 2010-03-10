<?php
/**
 * comment_patch_0302
 * @package modules.comment
 */
class comment_patch_0302 extends patch_BasePatch
{
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		$pp = f_persistentdocument_PersistentProvider::getInstance();
		$tm = f_persistentdocument_TransactionManager::getInstance();
		
		$commentCount = 0;
		$updatedComments = 0;
		foreach (comment_CommentService::getInstance()->createQuery()->find() as $comment)
		{
			$commentCount++;
			$content = $comment->getContents();
			$newContent = website_BBCodeService::getInstance()->fixContent($comment->getContents($content));
			if ($content != $newContent)
			{
				$tm->beginTransaction();
				$comment->setContents($newContent);
				$pp->updateDocument($comment);
				$tm->commit();
				$updatedComments++;
			}
		}
		echo ("\nResult:\n");
		echo ("$commentCount comments, $updatedComments updated.\n");
	}

	/**
	 * @return String
	 */
	protected final function getModuleName()
	{
		return 'comment';
	}

	/**
	 * @return String
	 */
	protected final function getNumber()
	{
		return '0302';
	}
}