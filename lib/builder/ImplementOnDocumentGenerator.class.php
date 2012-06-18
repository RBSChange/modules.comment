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
		block_BlockService::getInstance()->compileBlocks();
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
		change_AutoloadBuilder::getInstance()->appendFile($blockactionFile);
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
		$baseKey = 'm.' . $this->name . '.bo.blocks.' . strtolower($blockName);
		$localeId = strtolower($blockName);	
		echo "Add $localeId locale in $baseKey package.\n";
		$ls = LocaleService::getInstance();
		$keysInfos = array();
		$keysInfos[$ls->getLCID('fr')] = array('title' => $blockName);
		$keysInfos[$ls->getLCID('en')] = array('title' => $blockName);
		$ls->updatePackage($baseKey, $keysInfos, false, true, 'm.comment.bo.blocks.commentbase');
	}

	/**
	 * @param String $documentModule
	 * @param String $documentName
	 * @return String message to transmit to the user
	 */
	public function addEditorTab($documentModule, $documentName)
	{
		$moduleFolder = f_util_FileUtils::buildWebeditPath('modules', $documentModule);
		if (is_link($moduleFolder))
		{
			// Module is not embeded with project => dest is webapp
			$actionFile = f_util_FileUtils::buildOverridePath('modules', $documentModule, 'config', 'actions.xml');
		}
		else
		{
			// Module is embeded with project => dest is module itself
			$actionFile = f_util_FileUtils::buildWebeditPath('modules', $documentModule, 'config', 'actions.xml');
		}

		if (!file_exists($actionFile))
		{
			$actionsDom = f_util_DOMUtils::fromString('<?xml version="1.0" encoding="UTF-8"?><actions></actions>');
		}
		else
		{
			$actionsDom = f_util_DOMUtils::fromPath($actionFile);
		}

		$handlerName = 'comment-'.$documentName;
		if (!$actionsDom->exists("handler[@name = '$handlerName']"))
		{
			$newDom = f_util_DOMUtils::fromString('<?xml version="1.0" encoding="UTF-8"?><handler name="'.$handlerName.'" event="registerDocumentEditor"><![CDATA[
	// Action added by comment.implement-on-document
	var editor = event.originalTarget;
	if (editor.documentname === \''.$documentName.'\')
	{
		editor.addTab(\'comments\', \'&modules.comment.bo.doceditor.tab.Comments;\', \'comments\');
	}]]></handler>');

			$newHandler = $actionsDom->importNode($newDom->documentElement, true);

			$actionsDom->documentElement->appendChild($newHandler);

			f_util_DOMUtils::save($actionsDom, $actionFile);
			return $actionFile." altered with new handler";
		}
		return "Handler already exists in $actionFile";
	}

	protected function _getTpl($documentModule, $folder, $tpl, $blockName, $icon = null, $additionalParams = null)
	{
		$templateDir = f_util_FileUtils::buildWebeditPath('modules', 'comment', 'templates', 'builder', $folder);
		$generator = new builder_Generator();
		$generator->setTemplateDir($templateDir);
		$generator->assign('blockName', $blockName);
		$generator->assign('module', $this->name);
		$generator->assign('icon', $icon);
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