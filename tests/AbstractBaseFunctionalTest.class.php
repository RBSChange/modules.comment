<?php
/**
 * @package modules.comment.tests
 */
abstract class comment_tests_AbstractBaseFunctionalTest extends comment_tests_AbstractBaseTest
{
	/**
	 * @return void
	 */
	public function prepareTestCase()
	{
		$this->loadSQLResource('functional-test.sql', true, false);
	}
}