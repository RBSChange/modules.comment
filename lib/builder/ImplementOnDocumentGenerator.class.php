<?php
class comment_ImplementOnCommentGenerator extends builder_BlockGenerator
{
	const BLOCK_PREFIX = 'CommentsOn';
	
	/**
	 * @param String $documentModule
	 * @param String $documentName
	 */
	public function generateBlock($documentModule, $documentName)
	{
		$blockName = self::BLOCK_PREFIX.ucfirst($documentName);
		$this->_generateBlockAction($documentModule, $blockName);
		$this->_generateBlocksxml($documentModule, $blockName, 'comments');
		block_BlockService::getInstance()->compileBlocksForPackage('modules_'.$this->name);
	}
	
	/**
	 * @return String[] [$folder, $tplName]
	 */
	protected function getBlockTemplateInfo()
	{
		return array('blocks', 'CommentBlockAction.class.php.tpl');
	}
	
	/**
	 * @param String $documentModule
	 * @param String $blockName
	 * @return String the path of the generated PHP file
	 */
	protected function _generateBlockAction($documentModule, $blockName)
	{
		$blockactionFile = f_util_FileUtils::buildWebeditPath('modules', $this->name, 'lib', 'blocks', 'Block'.$blockName.'Action.class.php');
		if(!file_exists($blockactionFile))
		{
			list($tplFolder, $tplName) = $this->getBlockTemplateInfo();
			$result = $this->_getTpl($documentModule, $tplFolder, $tplName, $blockName);
			echo "Generating $blockactionFile\n";
			f_util_FileUtils::write($blockactionFile, $result);
		}
		else
		{
			echo "$blockactionFile already exists\n";
		}
		ClassResolver::getInstance()->appendFile($blockactionFile);
		return $blockactionFile;
	}
	
	/**
	 * @param String $documentModule
	 * @param String $blockName
	 * @param String $icon
	 */
	protected function _generateBlocksxml($documentModule, $blockName, $icon)
	{
		$blocksFile = f_util_FileUtils::buildWebeditPath('modules', $this->name, 'config', 'blocks.xml');
		$blockType = "modules_".$this->name."_".strtolower($blockName);
		if (file_exists($blocksFile))
		{
			$dom = f_util_DOMUtils::fromPath($blocksFile);
			if (!$dom->exists("//block[@type = '$blockType']"))
			{
				$result = $this->_getTpl($documentModule, 'modules', 'blocks.tpl', $blockName, $icon);
				$block = f_util_DOMUtils::fromString($result);
				$blockElement = $block->getElementsByTagName('block')->item(0);
				$domElements = $dom->getElementsByTagName('blocks')->item(0);
				$domElements->appendChild($dom->importNode($blockElement, true));
				echo "Add $blockType in $blocksFile.\n";
				f_util_DOMUtils::save($dom, $blocksFile);
			}
			else
			{
				echo "$blockType already in $blocksFile.\n";
			}
		}
		else
		{
			$result = $this->_getTpl($documentModule, 'modules', 'blocks.tpl', $blockName, $icon);
			echo "Generating $blocksFile, creating $blockType block entry.\n";
			f_util_FileUtils::write($blocksFile, $result);
		}

		// Block's locales.
		$directory = f_util_FileUtils::buildWebeditPath('modules', $this->name, 'locale', 'bo', 'blocks');
		if (!file_exists($directory))
		{
			echo "Add $directory directory.\n";
			f_util_FileUtils::mkdir($directory);
		}
		
		$localeFile = f_util_FileUtils::buildWebeditPath('modules', $this->name, 'locale', 'bo', 'blocks', strtolower($blockName).'.xml');
		if (file_exists($localeFile))
		{
			echo "$localeFile already exists.\n";
		}
		else
		{
			$result = $this->_getTpl($documentModule, 'modules', 'blocksLocale.tpl', $blockName);
			echo "Generating $localeFile.\n";
			f_util_FileUtils::write($localeFile, $result);
		}
	}
	
	/**
	 * @param String $documentModule
	 * @param String $documentName
	 * @return String
	 */
	public function addEditorTab($documentModule, $documentName)
	{
		$directory = f_util_FileUtils::buildWebeditPath('modules', $documentModule, 'forms', 'editor', $documentName);
		if (!file_exists($directory))
		{
			return "$directory does not exists, so the document has no editor and the comment tab can't be added.\n";		
		}
		
		if ($documentModule == $this->name)
		{
			$mode = 'module';
			$moduleFile = $file = f_util_FileUtils::buildWebeditPath('modules', $documentModule, 'forms', 'editor', $documentName, 'editor.xml');
		}
		else 
		{	
			$mode = 'webapp';
			$moduleFile = f_util_FileUtils::buildWebeditPath('modules', $documentModule, 'forms', 'editor', $documentName, 'editor.xml');
			$file = f_util_FileUtils::buildWebappPath('modules', $documentModule, 'forms', 'editor', $documentName, 'editor.xml');
		}
		
		if (!file_exists($moduleFile) && !file_exists($file))
		{
			if ($mode === 'webapp')
			{
				$directory = f_util_FileUtils::buildWebappPath('modules', $documentModule, 'forms', 'editor', $documentName);
				if (!file_exists($directory))
				{
					f_util_FileUtils::mkdir($directory);		
				}
			}
			$tplFolder = 'modules';
			$tplName = 'editor.xml.tpl';
			$result = $this->_getTpl($documentModule, $tplFolder, $tplName, null);
			echo "Generating $file\n";
			f_util_FileUtils::write($file, $result);
			return null;
		}
		else
		{
			if ($mode === 'module' || file_exists($file))
			{
				return "$file already exists. To add the comment tab, manually edit this file to add the following code in the constructor:\n
					// Check comment module existence.
					var controller = document.getElementById('wcontroller');
					if (controller.checkModuleVersion('comment', '3.0.0'))
					{
						this.addTab('comments', '&modules.comment.bo.doceditor.tab.Comments;', 'comments');
					}\n";
			}
			else
			{
				return "$moduleFile already exists. To add the comment tab, manually copy this file to $file and edit this file to add the following code in the constructor:\n
					// Check comment module existence.
					var controller = document.getElementById('wcontroller');
					if (controller.checkModuleVersion('comment', '3.0.0'))
					{
						this.addTab('comments', '&modules.comment.bo.doceditor.tab.Comments;', 'comments');
					}\n";
			}
		}
	}
	
	protected function _getTpl($documentModule, $folder, $tpl, $blockName, $icon = null, $additionalParams = null)
	{
		$templateDir = f_util_FileUtils::buildWebeditPath('modules', 'comment', 'templates', 'builder', $folder);
		$generator = new builder_Generator();
		$generator->setTemplateDir($templateDir);
		$generator->assign('author', $this->author);
		$generator->assign('blockName', $blockName);
		$generator->assign('module', $this->name);
		$generator->assign('icon', $icon);
		$generator->assign('date', $this->date);
		$docName = strtolower(substr($blockName, strlen(self::BLOCK_PREFIX)));
		$generator->assign('documentName', $docName);
		$generator->assign('documentModule', $documentModule);
		$generator->assign('documentNameFr', f_Locale::translate('&modules.'.$documentModule.'.document.'.$docName.'.document-name;', null, 'fr'));
		$generator->assign('documentNameEn', f_Locale::translate('&modules.'.$documentModule.'.document.'.$docName.'.document-name;', null, 'en'));
		foreach ($this->getAdditionalTplVariables() as $key => $value)
		{
			$generator->assign($key, $value);
		}
		if ($additionalParams !== null)
		{
			foreach ($additionalParams as $key => $value)
			{
				$generator->assign($key, $value);
			}
		}
		$result = $generator->fetch($tpl);
		return $result;
	}
}