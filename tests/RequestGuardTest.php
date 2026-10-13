<?php
require_once(dirname(__FILE__).'/helpers/common.php');
require_once(dirname(__FILE__).'/../lib/class.request_guard.php');
require_once(dirname(__FILE__).'/../lib/class.recaptcha_checker.php');

class RequestGuardTest extends SimpleMailForm_TestCase
{
	public function testAddAndGetClientConfig()
	{
		$guard = new RequestGuard();
		$checker = new ReCaptchaChecker('test_secret', 'test_site', null);
		
		$guard->addChecker('recaptcha', $checker);
		
		$config = $guard->getClientConfig('recaptcha');
		$this->assertEquals(['site_key' => 'test_site'], $config);
		$this->assertNull($guard->getClientConfig('non_existent'));
	}

	public function testValidateSuccess()
	{
		$guard = new RequestGuard();
		$checkerMock = $this->createCompatibleMock('RequestGuardTestDummyChecker', ['check']);
		$expectation = $checkerMock->expects($this->once())
			->method('check');
		$this->setCompatibleReturnValue($expectation, true);
			
		$guard->addChecker('dummy', $checkerMock);
		
		$errors = $guard->validate([]);
		$this->assertEmpty($errors);
	}

	public function testValidateFailure()
	{
		$guard = new RequestGuard();
		$checkerMock = $this->createCompatibleMock('RequestGuardTestDummyChecker', ['check', 'getErrorId']);
		
		$expectationCheck = $checkerMock->expects($this->once())
			->method('check');
		$this->setCompatibleReturnValue($expectationCheck, false);

		$expectationErrorId = $checkerMock->expects($this->once())
			->method('getErrorId');
		$this->setCompatibleReturnValue($expectationErrorId, 101);
			
		$guard->addChecker('dummy', $checkerMock);
		
		$errors = $guard->validate([]);
		$this->assertEquals([101], $errors);
	}

	public function testValidateStopsOnFirstFailure()
	{
		$guard = new RequestGuard();

		$checkerMock1 = $this->createCompatibleMock('RequestGuardTestDummyChecker', ['check', 'getErrorId']);
		$expectationCheck1 = $checkerMock1->expects($this->once())
			->method('check');
		$this->setCompatibleReturnValue($expectationCheck1, false);

		$expectationErrorId1 = $checkerMock1->expects($this->once())
			->method('getErrorId');
		$this->setCompatibleReturnValue($expectationErrorId1, 101);

		$checkerMock2 = $this->createCompatibleMock('RequestGuardTestDummyChecker', ['check']);
		$checkerMock2->expects($this->never())
			->method('check');

		$guard->addChecker('dummy1', $checkerMock1);
		$guard->addChecker('dummy2', $checkerMock2);

		$errors = $guard->validate([]);
		$this->assertEquals([101], $errors);
	}
}

class RequestGuardTestDummyChecker implements RequestCheckerInterface
{
	public function check($request)
	{
		return true;
	}
	public function getErrorId()
	{
		return null;
	}
	public function getClientConfig()
	{
		return [];
	}
}
