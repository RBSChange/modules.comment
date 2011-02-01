<?php
/**
 * comment_patch_0350
 * @package modules.comment
 */
class comment_patch_0350 extends patch_BasePatch
{
//  by default, isCodePatch() returns false.
//  decomment the following if your patch modify code instead of the database structure or content.
    /**
     * Returns true if the patch modify code that is versionned.
     * If your patch modify code that is versionned AND database structure or content,
     * you must split it into two different patches.
     * @return Boolean true if the patch modify code that is versionned.
     */
//	public function isCodePatch()
//	{
//		return true;
//	}
 
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		$this->executeSQLQuery('ALTER TABLE m_comment_doc_comment ADD `private` tinyint(1) NOT NULL default \'0\'');
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
		return '0350';
	}
}