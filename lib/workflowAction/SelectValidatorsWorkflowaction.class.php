<?php
/**
 * @author intportg
 * @package modules.comment.lib.workflowAction
 */
class comment_SelectValidatorsWorkflowaction extends workflow_BaseWorkflowaction
{
	/**
	 * This method will execute the action.
	 * @return boolean true if the execution end successfully, false in error case.
	 */
	function execute()
	{	
		$ps = f_permission_PermissionService::getInstance();
		$comment = DocumentHelper::getDocumentInstance($this->getDocumentId());
		$package = 'modules_' . $comment->getTarget()->getPersistentModel()->getOriginalModuleName();
		$permission = $package . '.Validate.comment';
		$definitionPoint = $ps->getDefinitionPointForPackage($this->getDocumentId(), $package);
		$actorsIds = $ps->getAccessorIdsForPermissionAndDocumentId($permission, $definitionPoint);
		$this->setCaseParameter('__NEXT_ACTORS_IDS', $actorsIds);
		return parent::execute();
	}
}