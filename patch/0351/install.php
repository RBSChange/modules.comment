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
		$parser = new website_BBCodeParser();
		
		try 
		{
			$tm->beginTransaction();
			foreach (comment_CommentService::getInstance()->createQuery()->find() as $doc)
			{
				$text = $doc->getContents();
				if (f_util_StringUtils::beginsWith($text, '<div data-profile="'))
				{
					$text = $parser->convertXmlToBBCode($text);
				}
				$doc->setContentsAsBBCode($text);
				$pp->updateDocument($doc);
			}
			$tm->commit();
		}
		catch (Exception $e)
		{
			$tm->rollback($e);
		}
	}
}