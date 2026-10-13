<?php
require_once(dirname(__FILE__).'/helpers/common.php');
require_once(dirname(__FILE__).'/../lib/class.recaptcha_checker.php');

class ReCaptchaCheckerTest extends SimpleMailForm_TestCase
{
	public function testGetClientConfig()
	{
		$checker = new ReCaptchaChecker('my_secret', 'my_site', null);
		$config = $checker->getClientConfig();
		$this->assertEquals(['site_key' => 'my_site'], $config);
	}

	public function testGetErrorIdInitial()
	{
		$checker = new ReCaptchaChecker('my_secret', 'my_site', null);
		$this->assertNull($checker->getErrorId());
	}

	private function createReCaptchaCheckerMock(array $methods, $constructorArgs = null)
	{
		if ($constructorArgs === null) {
			$constructorArgs = ['my_secret', 'my_site', null];
		}
		$mockBuilder = $this->getMockBuilder('ReCaptchaChecker')
		->setConstructorArgs($constructorArgs);

		if (method_exists($mockBuilder, 'onlyMethods')) {
			$mockBuilder->onlyMethods($methods);
		} else {
			$mockBuilder->setMethods($methods);
		}

		return $mockBuilder->getMock();
	}

	public function testCheckSuccess()
	{
		$checker = $this->createReCaptchaCheckerMock(['executeCurl']);
		$expectation = $checker->expects($this->once())
			->method('executeCurl');
		$this->setCompatibleReturnValue($expectation, json_encode(['success' => true, 'score' => 0.9]));

		$this->assertTrue($checker->check(['recaptchaResponse' => 'valid_token']));
		$this->assertNull($checker->getErrorId());
	}

	public function testCheckLowScoreRobot()
	{
		$checker = $this->createReCaptchaCheckerMock(['executeCurl']);
		$expectation = $checker->expects($this->once())
			->method('executeCurl');
		$this->setCompatibleReturnValue($expectation, json_encode(['success' => true, 'score' => 0.3]));

		$this->assertFalse($checker->check(['recaptchaResponse' => 'bot_token']));
		$this->assertEquals(ReCaptchaChecker::ERROR_CODE_RECAPTCHA, $checker->getErrorId());
	}
	
	public function testCheckCustomScoreThreshold()
	{
		// 閾値を 0.7 に設定
		$checker = $this->createReCaptchaCheckerMock(['executeCurl'], ['my_secret', 'my_site', 0.7]);
		$expectation = $checker->expects($this->once())
		->method('executeCurl');
		// スコア 0.6 は 0.7 未満のため失敗（ロボット判定）
		$this->setCompatibleReturnValue($expectation, json_encode(['success' => true, 'score' => 0.6]));
		
		$this->assertFalse($checker->check(['recaptchaResponse' => 'bot_token']));
		$this->assertEquals(ReCaptchaChecker::ERROR_CODE_RECAPTCHA, $checker->getErrorId());
	}
	
	public function testConstructorScoreThresholdFallback()
	{
		// 0 や null を渡しても 0.5 として動作するため、スコア 0.4 は失敗（0.5未満）
		foreach ([0, null, '0', ''] as $invalidThreshold) {
			$checker = $this->createReCaptchaCheckerMock(['executeCurl'], ['my_secret', 'my_site', $invalidThreshold]);
			$expectation = $checker->expects($this->once())
			->method('executeCurl');
			$this->setCompatibleReturnValue($expectation, json_encode(['success' => true, 'score' => 0.4]));
			
			$this->assertFalse($checker->check(['recaptchaResponse' => 'bot_token']));
			$this->assertEquals(ReCaptchaChecker::ERROR_CODE_RECAPTCHA, $checker->getErrorId(), "Failed for threshold: " . var_export($invalidThreshold, true));
		}
	}
	
	public function testCheckDuplicateOrTimeout()
	{
		$checker = $this->createReCaptchaCheckerMock(['executeCurl']);
		$expectation = $checker->expects($this->once())
			->method('executeCurl');
		$this->setCompatibleReturnValue($expectation, json_encode([
			'success' => false,
			'error-codes' => ['timeout-or-duplicate']
		]));

		$this->assertFalse($checker->check(['recaptchaResponse' => 'used_token']));
		$this->assertEquals(ReCaptchaChecker::ERROR_CODE_DUPLICATE_RECAPTCHA, $checker->getErrorId());
	}

	public function testCheckConnectionFailure()
	{
		$checker = $this->createReCaptchaCheckerMock(['executeCurl']);
		$expectation = $checker->expects($this->once())
			->method('executeCurl');
		$this->setCompatibleReturnValue($expectation, false);

		$this->assertFalse($checker->check(['recaptchaResponse' => 'token']));
		$this->assertEquals(ReCaptchaChecker::ERROR_CODE_NO_CONNECT_RECAPTCHA, $checker->getErrorId());
	}
}
