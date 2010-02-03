<?php
/**
 * @package modules.comment.tests
 */
abstract class comment_tests_AbstractBaseUnitTest extends comment_tests_AbstractBaseTest
{
	/**
	 * @return void
	 */
	public function prepareTestCase()
	{
		$this->resetDatabase();
	}
}