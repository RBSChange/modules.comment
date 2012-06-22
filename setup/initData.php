<?php
/**
 * @package modules.comment.setup
 */
class comment_Setup extends object_InitDataSetup
{
	public function install()
	{
		$this->executeModuleScript('init.xml');
	}

	/**
	 * @return array<string>
	 */
	public function getRequiredPackages()
	{
		
																return array();
	}
}