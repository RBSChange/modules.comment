<?php
/**
 * comment_patch_0303
 * @package modules.comment
 */
class comment_patch_0303 extends patch_BasePatch
{
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		// Implement your patch here.
		$codes = array("modules_comment/commentRejected", "modules_comment/CommentValidationCancellation", "modules_comment/CommentValidationTermination", "modules_comment/commentAccepted", "modules_comment/newCommentValidation");
		foreach($codes as $code)
		{
			$notification = notification_NotificationService::getInstance()->getByCodeName($code);
			$notification->setAvailableparameters($notification->getAvailableparameters() . ', {authorEmail}, {authorName}, {authorWebsiteUrl}, {authorWebsiteLink}, {authorIp}, {commentContent}, {commentRating}, {targetLabel}, {targetUrl}, {targetLink}, {targetType}');
			$notification->save();
		}
	}

	/**
	 * @return String
	 */
	protected final function getModuleName()
	{
		return 'comment';
	}

	/**
	 * @return String
	 */
	protected final function getNumber()
	{
		return '0303';
	}
}