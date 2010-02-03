<?php
/**
 * @package modules.comment
 */
class comment_patch_0300 extends patch_BasePatch
{
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		parent::execute();
		
		// Import the new workflow.
		$scriptReader = import_ScriptReader::getInstance();
		$scriptReader->executeModuleScript('comment', 'init.xml');
		
		// Update publication dates.
		$ws = workflow_WorkflowService::getInstance();
		$now = date_Calendar::getInstance()->sub(1, date_Calendar::MINUTE)->toString();
		$workflow1 = $ws->createQuery()->add(Restrictions::eq('label', 'Validation des commentaires'))->findUnique();
		if ($workflow1 !== null)
		{
			$workflow1->setEndpublicationdate($now);
			$workflow1->save();
		}
		else
		{
			$this->logWarning('missing old workflow');
		}
		$workflow2 = $ws->createQuery()->add(Restrictions::eq('label', 'Validation des commentaires (V2)'))->findUnique();
		if ($workflow2 !== null)
		{
			$workflow2->setStartpublicationdate($now);
			$workflow2->setEndpublicationdate(null);
			$workflow2->save();
		}
		else
		{
			$this->logError('missing new workflow');
		}
		
		// Revalidate workflows.
		$workflowDesignerService = workflow_WorkflowDesignerService::getInstance();
		if ($workflow1 !== null)
		{
			$workflowDesignerService->validateWorkflowDefinition($workflow1);
		}
		if ($workflow2 !== null)
		{
			$workflowDesignerService->validateWorkflowDefinition($workflow2);
		}
	}
	
	/**
	 * Returns the name of the module the patch belongs to.
	 *
	 * @return String
	 */
	protected final function getModuleName()
	{
		return 'comment';
	}
	
	/**
	 * Returns the number of the current patch.
	 * @return String
	 */
	protected final function getNumber()
	{
		return '0300';
	}
}