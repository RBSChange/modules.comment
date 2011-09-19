<?php
/**
 * @package modules.comment
 */
class commands_comment_ImplementOnDocument extends c_ChangescriptCommand
{
	/**
	 * @return String
	 */
	function getUsage()
	{
		return "<moduleName> <documentName>";
	}

	/**
	 * @return String
	 */
	function getDescription()
	{
		return "implement comments on a document model.";
	}
	
	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 */
	protected function validateArgs($params, $options)
	{
		return count($params) == 2;
	}
	
	/**
	 * @param Integer $completeParamCount the parameters that are already complete in the command line
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @return String[] or null
	 */
	function getParameters($completeParamCount, $params, $options, $current)
	{
		if ($completeParamCount == 0)
		{
			$components = array();
			foreach (glob("modules/*", GLOB_ONLYDIR) as $module)
			{
				$components[] = basename($module);
			}
			return $components;
		}
		elseif ($completeParamCount == 1)
		{
			$module = $params[0];
			$docs = array();
			foreach (glob("modules/$module/persistentdocument/*.xml") as $docFile)
			{
				$docs[] = basename($docFile, ".xml");
			}
			return $docs;
		}
		return null;
	}
	
	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Implement comments on a document model ==");

		$moduleName = $params[0];
		$documentName = $params[1];
		$destModuleName = (isset($params[2])) ? $params[2] : $moduleName;
				
		$this->loadFramework();
		$moduleGenerator = new comment_ImplementOnCommentGenerator($destModuleName);
		$moduleGenerator->setAuthor($this->getAuthor());
		$moduleGenerator->generateBlock($moduleName, $documentName);
		$message = $moduleGenerator->addEditorTab($moduleName, $documentName);
		if ($message !== null)
		{
			$this->warnMessage($message);
		}
		
		// Recompile locales for module.
		$this->message("Recompile locales for module $destModuleName.");
		LocaleService::getInstance()->regenerateLocalesForModule($destModuleName);
		$this->executeCommand('clear-webapp-cache');
		$this->executeCommand('clear-template-cache');
		
		$this->quitOk("Block 'CommentsOn$documentName' added in module '$destModuleName' and ready to use.
To specialize rendering, add the following templates:
 * 'modules/$destModuleName/templates/".ucfirst($destModuleName)."-Block-Success.all.all.html': for the comment list and form.
 * 'modules/$destModuleName/templates/".ucfirst($destModuleName)."-Block-Save.all.all.html': for the confirmation screen.
 * 'modules/$destModuleName/templates/".ucfirst($destModuleName)."-Block-Backoffice.all.all.html': for the backoffice view.
Default templates are placed in 'modules/comment/templates/Comment-Block-CommentBase-<view>.all.all.html'.");
	}
}