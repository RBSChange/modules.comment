<?php
/**
 * This load handler loads data from the current user to fill the form fields.
 * 
 * Loaded variables are:
 *  - 'currentUser': the current user.
 * 
 * @package modules.comment.lib.loadhandlers
 */
class comment_InitFormInfoLoadHandler extends website_ViewLoadHandlerImpl
{
	/**
	 * @param website_BlockActionRequest $request
	 * @param website_BlockActionResponse $response
	 */
	function execute($request, $response)
	{
		$currentUser = users_WebsitefrontenduserService::getInstance()->getCurrentFrontEndUser();
		$request->setAttribute('currentUser', $currentUser);
	}
}