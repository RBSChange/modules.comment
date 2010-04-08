<?php
/**
 * @package modules.comment.setup
 */
class comment_Setup extends object_InitDataSetup
{
	public function install()
	{
		$this->executeModuleScript('init.xml');
		@copy(f_util_FileUtils::buildWebeditPath('modules', 'comment', 'setup', 'media', 'star.gif'), 
			f_util_FileUtils::buildWebeditPath('media', 'frontoffice', 'star.gif'));
		@copy(f_util_FileUtils::buildWebeditPath('modules', 'comment', 'setup', 'media', 'star_small.gif'), 
			f_util_FileUtils::buildWebeditPath('media', 'frontoffice', 'star_small.gif'));	
	}

	/**
	 * @return array<string>
	 */
	public function getRequiredPackages()
	{
		// Return an array of packages name if the data you are inserting in
		// this file depend on the data of other packages.
		// Example:
		// return array('modules_website', 'modules_users');
		return array();
	}
}