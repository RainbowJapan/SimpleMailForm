<?php

trait SimpleMailForm_SetUpTrait
{
	protected function setUp()
	{
		if (method_exists('PHPUnit_Framework_TestCase', 'setUp')) {
			parent::setUp();
		}
		$this->setUpCompat();
	}
	
	protected function tearDown()
	{
		$this->tearDownCompat();
		if (method_exists('PHPUnit_Framework_TestCase', 'tearDown')) {
			parent::tearDown();
		}
	}
}
