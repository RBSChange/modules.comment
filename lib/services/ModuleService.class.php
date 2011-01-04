<?php
/**
 * @package modules.comment.lib.services
 */
class comment_ModuleService extends ModuleBaseService
{
	/**
	 * Singleton
	 * @var comment_ModuleService
	 */
	private static $instance = null;

	/**
	 * @return comment_ModuleService
	 */
	public static function getInstance()
	{
		if (is_null(self::$instance))
		{
			self::$instance = self::getServiceClassInstance(get_class());
		}
		return self::$instance;
	}
	
	/**
	 * @param Integer $documentId
	 * @return f_persistentdocument_PersistentTreeNode
	 */
	public function getParentNodeForPermissions($documentId)
	{
		$document = DocumentHelper::getDocumentInstance($documentId);
		if ($document instanceof comment_persistentdocument_comment)
		{
			$targetId = $document->getTargetId();
			if ($targetId !== null)
			{
				$node = TreeService::getInstance()->getInstanceByDocumentId($targetId);
				if ($node === null)
				{
					$target = $document->getTarget();
					$moduleService = ModuleBaseService::getInstanceByModuleName($target->getPersistentModel()->getModuleName());
					if ($moduleService !== null)
					{
						$node = $moduleService->getParentNodeForPermissions($targetId);
					}
				}
				return $node;
			}
		}
		return null;
	}
	
	/**
	 * @deprecated
	 * @return String
	 */
	public function getIp()
	{
		return RequestContext::getInstance()->getClientIp();
	}
}