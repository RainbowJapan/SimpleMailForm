<?php
trait SimpleMailForm_SetUpTrait
{
	protected function setUp(): void
	{
		parent::setUp();
		$this->setUpCompat();
	}
	
	protected function tearDown(): void
	{
		$this->tearDownCompat();
		parent::tearDown();
	}
}
