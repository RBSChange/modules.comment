<?php
/**
 * @package modules.comment.tests
 */
abstract class comment_tests_AbstractBaseIntegrationTest extends comment_tests_AbstractBaseTest
{
	/**
	 * @return void
	 */
	public function prepareTestCase()
	{
		$this->loadSQLResource('integration-test.sql', true, false);
	}
}