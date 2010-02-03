<?php
/**
 * comment_RatingService
 * @package modules.comment
 */
class comment_RatingService extends BaseService 
{
	/**
	 * @var comment_ScoringService
	 */
	private static $instance;

	/**
	 * @return comment_ScoringService
	 */
	public static function getInstance()
	{
		if (self::$instance === null)
		{
			self::$instance = self::getServiceClassInstance(get_class());
		}
		return self::$instance;
	}
	
	/**
	 * @param Integer $RatingValue
	 * @return Integer
	 */
	public function normalizeRating($RatingValue)
	{
		return min(max(0, intval($RatingValue)), 5);
	}
	
	/**
	 * @return Boolean
	 */
	function getRelevancyForComment($comment)
	{
		return $comment->getUsefulcount()-$comment->getUselesscount();
	}
}