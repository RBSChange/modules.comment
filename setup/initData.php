<?php
/**
 * @package modules.comment
 */
class comment_Setup extends object_InitDataSetup
{
	public function install()
	{
		$this->executeModuleScript('init.xml');
	}
}