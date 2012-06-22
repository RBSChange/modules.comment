<?php
/**
 * @package modules.comment
 * @method comment_RatingService getInstance()
 */
class comment_RatingService extends change_BaseService 
{
	/**
	 * @param integer $RatingValue
	 * @return integer
	 */
	public function normalizeRating($RatingValue)
	{
		return min(max(0, intval($RatingValue)), 5);
	}
	
	/**
	 * @return boolean
	 */
	function getRelevancyForComment($comment)
	{
		return $comment->getUsefulcount()-$comment->getUselesscount();
	}
}