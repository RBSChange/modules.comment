<?php
/**
 * @package modules.comment
 * @method comment_ModuleService getInstance()
 */
class comment_ModuleService extends ModuleBaseService
{
	/**
	 * @param integer $documentId
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
}