<?php
require_once(dirname(__FILE__).'/helpers/common.php');
require_once(dirname(__FILE__).'/../lib/interface.mailer.php');
require_once(dirname(__FILE__).'/../lib/interface.smtpclient.php');
require_once(dirname(__FILE__).'/../lib/class.simplemailer.php');
require_once(dirname(__FILE__).'/../lib/class.smtpmailer.php');

class SmtpMailerTest extends SimpleMailForm_TestCase
{
	public function testSmtpMailerFactory_getInstance()
	{
		$factory = new SmtpMailerFactory(
			'smtp.example.com',
			465,
			'ssl',
			'user@example.com',
			'secret_pass',
			'login',
			'mail.example.com',
			15
		);

		$mailer = $factory->getInstance();
		$this->assertInstanceOf('SmtpMailer', $mailer);

		$Host = getProperty('SmtpMailer', 'Host');
		$Port = getProperty('SmtpMailer', 'Port');
		$SMTPSecure = getProperty('SmtpMailer', 'SMTPSecure');
		$Helo = getProperty('SmtpMailer', 'Helo');
		$Timeout = getProperty('SmtpMailer', 'Timeout');
		$Username = getProperty('SmtpMailer', 'Username');
		$Password = getProperty('SmtpMailer', 'Password');
		$AuthType = getProperty('SmtpMailer', 'AuthType');

		$this->assertEquals('smtp.example.com', $Host->getValue($mailer));
		$this->assertEquals(465, $Port->getValue($mailer));
		$this->assertEquals('ssl', $SMTPSecure->getValue($mailer));
		$this->assertEquals('mail.example.com', $Helo->getValue($mailer));
		$this->assertEquals(15, $Timeout->getValue($mailer));
		$this->assertEquals('user@example.com', $Username->getValue($mailer));
		$this->assertEquals('secret_pass', $Password->getValue($mailer));
		$this->assertEquals('login', $AuthType->getValue($mailer));
	}

	public function testSmtpMailerFactory_getInstance_withoutAuth()
	{
		$factory = new SmtpMailerFactory('smtp.example.com', 587, 'tls', '');
		$mailer = $factory->getInstance();

		$Username = getProperty('SmtpMailer', 'Username');
		$Password = getProperty('SmtpMailer', 'Password');
		$this->assertEquals('', $Username->getValue($mailer));
		$this->assertEquals('', $Password->getValue($mailer));
	}

	public function testSetters()
	{
		$mailer = new SmtpMailer();

		$mailer->setHost('  mail.server.com  ');
		$mailer->setPort('465');
		$mailer->setSMTPSecure('SSL');
		$mailer->setUsername('testuser');
		$mailer->setPassword('testpass');
		$mailer->setAuthType('CRAM-MD5');
		$mailer->setTimeout('20');
		$mailer->setHelo('  client.domain.com  ');

		$this->assertEquals('mail.server.com', getProperty('SmtpMailer', 'Host')->getValue($mailer));
		$this->assertEquals(465, getProperty('SmtpMailer', 'Port')->getValue($mailer));
		$this->assertEquals('ssl', getProperty('SmtpMailer', 'SMTPSecure')->getValue($mailer));
		$this->assertEquals('testuser', getProperty('SmtpMailer', 'Username')->getValue($mailer));
		$this->assertEquals('testpass', getProperty('SmtpMailer', 'Password')->getValue($mailer));
		$this->assertEquals('cram-md5', getProperty('SmtpMailer', 'AuthType')->getValue($mailer));
		$this->assertEquals(20, getProperty('SmtpMailer', 'Timeout')->getValue($mailer));
		$this->assertEquals('client.domain.com', getProperty('SmtpMailer', 'Helo')->getValue($mailer));
	}

	public function testValidateConfiguration()
	{
		$validateConfiguration = getMethod('SmtpMailer', 'validateConfiguration');
		$mailer = new SmtpMailer();

		// 未設定初期状態は失敗
		$this->assertFalse($validateConfiguration->invokeArgs($mailer, []));
		$this->assertEquals('A valid SMTP host is required', $mailer->getErrorInfo());

		// ホスト設定、しかし認証情報不足
		$mailer->setHost('smtp.example.com');
		$this->assertFalse($validateConfiguration->invokeArgs($mailer, []));
		$this->assertEquals('SMTP username and password are required', $mailer->getErrorInfo());

		// 正常設定
		$mailer->setUsername('user');
		$mailer->setPassword('pass');
		$this->assertTrue($validateConfiguration->invokeArgs($mailer, []));

		// タイムアウト不正
		$mailer->setTimeout(0);
		$this->assertFalse($validateConfiguration->invokeArgs($mailer, []));
		$this->assertEquals('SMTP timeout must be greater than zero', $mailer->getErrorInfo());
		$mailer->setTimeout(10);

		// 認証タイプ不正
		$mailer->setAuthType('unsupported');
		$this->assertFalse($validateConfiguration->invokeArgs($mailer, []));
		$this->assertEquals('Invalid SMTP authentication type', $mailer->getErrorInfo());
	}

	public function testSanitizeErrorMessage()
	{
		$sanitize = getMethod('SmtpMailer', 'sanitizeErrorMessage');
		$mailer = new SmtpMailer();
		$mailer->setUsername('secret_user');
		$mailer->setPassword('secret_password');

		// 平文パスワード・平文ユーザー名のマスキング
		$rawMsg = 'Authentication failed for user secret_user with pass secret_password';
		$sanitized = $sanitize->invokeArgs($mailer, [$rawMsg]);
		$this->assertEquals('Authentication failed for user [redacted] with pass [redacted]', $sanitized);

		// Base64化された認証情報のマスキング
		$base64Pass = base64_encode('secret_password');
		$rawBase64Msg = 'Base64 auth string: ' . $base64Pass;
		$sanitizedBase64 = $sanitize->invokeArgs($mailer, [$rawBase64Msg]);
		$this->assertEquals('Base64 auth string: [redacted]', $sanitizedBase64);

		// 短すぎる認証情報（4文字未満）は過剰置換を防ぐためマスキングされない
		$mailer->setUsername('abc');
		$mailer->setPassword('123');
		$this->assertEquals('Error: abc and 123', $sanitize->invokeArgs($mailer, ['Error: abc and 123']));
	}

	public function testMapErrorStage()
	{
		$mapErrorStage = getMethod('SmtpMailer', 'mapErrorStage');
		$mailer = new SmtpMailer();

		$this->assertEquals('configuration', $mapErrorStage->invokeArgs($mailer, ['configuration']));
		$this->assertEquals('authentication', $mapErrorStage->invokeArgs($mailer, ['authentication']));
		$this->assertEquals('sender', $mapErrorStage->invokeArgs($mailer, ['sender']));
		$this->assertEquals('recipient', $mapErrorStage->invokeArgs($mailer, ['recipient']));
		$this->assertEquals('tls', $mapErrorStage->invokeArgs($mailer, ['tls']));
		$this->assertEquals('protocol', $mapErrorStage->invokeArgs($mailer, ['protocol']));
		$this->assertEquals('connection', $mapErrorStage->invokeArgs($mailer, ['connection']));
		$this->assertEquals('data', $mapErrorStage->invokeArgs($mailer, ['data']));
		// 未知のステージは protocol にフォールバック
		$this->assertEquals('protocol', $mapErrorStage->invokeArgs($mailer, ['unknown_stage']));
	}

	public function testNormalizeEnvelopeRecipients()
	{
		$normalizeRecipients = getMethod('SmtpMailer', 'normalizeEnvelopeRecipients');
		$mailer = new SmtpMailer();

		// プロパティ初期化
		getProperty('SimpleMailer', 'To')->setValue($mailer, []);
		getProperty('SimpleMailer', 'Cc')->setValue($mailer, []);
		getProperty('SimpleMailer', 'Bcc')->setValue($mailer, []);

		$mailer->addAddress('to1@example.com');
		$mailer->addAddress('TO1@example.com'); // 大文字小文字の重複排除テスト
		$mailer->addCc('cc@example.com');
		$mailer->addBcc('bcc@example.com');

		$recipients = $normalizeRecipients->invokeArgs($mailer, []);

		$this->assertNotFalse($recipients, 'normalizeEnvelopeRecipients returned false');
		// 重複が除外されて3件になっているか
		$this->assertCount(3, $recipients);

		// 小文字に揃えた配列を作成して存在チェック
		$lowerRecipients = array_map('strtolower', $recipients);
		$this->assertTrue(in_array('to1@example.com', $lowerRecipients, true));
		$this->assertTrue(in_array('cc@example.com', $lowerRecipients, true));
		$this->assertTrue(in_array('bcc@example.com', $lowerRecipients, true));
	}

	public function testSend_configurationValidationFailure()
	{
		$mailer = new SmtpMailer();
		// ホストや認証情報が不足
		$this->assertFalse($mailer->send());
		$this->assertEquals(SmtpMailer::ERROR_CONFIGURATION, $mailer->getErrorCode());
	}

	public function testSend_missingRecipientOrSender()
	{
		$mailer = new SmtpMailer();
		$mailer->setHost('smtp.example.com');
		$mailer->setUsername('user');
		$mailer->setPassword('pass');

		// To・From 未設定
		$this->assertFalse($mailer->send());
		$this->assertEquals(SmtpMailer::ERROR_CONFIGURATION, $mailer->getErrorCode());
		$this->assertEquals('From and at least one To address are required', $mailer->getErrorInfo());

		// To のみ設定
		$mailer->addAddress('to@example.com');
		$this->assertFalse($mailer->send());
		$this->assertEquals(SmtpMailer::ERROR_CONFIGURATION, $mailer->getErrorCode());
	}

	public function testSend_subjectHeaderInjection()
	{
		$mailer = new SmtpMailer();
		$mailer->setHost('smtp.example.com');
		$mailer->setUsername('user');
		$mailer->setPassword('pass');
		$mailer->addAddress('to@example.com');
		$mailer->setFrom('from@example.com');
		$mailer->setSubject("Subject\nInjection: test");

		$this->assertFalse($mailer->send());
		$this->assertEquals(SmtpMailer::ERROR_CONFIGURATION, $mailer->getErrorCode());
		$this->assertEquals('Subject contains a line break', $mailer->getErrorInfo());
	}

	public function testSend_success()
	{
		$mockClient = $this->createCompatibleMock('SmtpClientInterface', [
			'connect',
			'authenticatePassword',
			'authenticateXOAuth2',
			'hasExtension',
			'sendMessage',
			'quit',
			'close'
		]);

		$mockClient->expects($this->once())
		->method('connect')
		->with('smtp.example.com', 587, 'tls', $this->anything(), 10);

		$mockClient->expects($this->once())
		->method('authenticatePassword')
		->with('user', 'pass', 'auto');

		$expectationExt = $mockClient->method('hasExtension');
		$this->setCompatibleReturnValue($expectationExt, false);

		$mockClient->expects($this->once())
		->method('sendMessage')
		->with(
			'from@example.com',
			['to@example.com'],
			$this->stringContains('Subject: Hello'),
			[]
		);

		$mockClient->expects($this->once())->method('quit');

		// SmtpMailer の createSmtpClient をモック化して注入
		$mailer = $this->createCompatibleMock('SmtpMailer', ['createSmtpClient']);
		$expectationClient = $mailer->expects($this->once())->method('createSmtpClient');
		$this->setCompatibleReturnValue($expectationClient, $mockClient);

		$mailer->setHost('smtp.example.com');
		$mailer->setUsername('user');
		$mailer->setPassword('pass');
		$mailer->setFrom('from@example.com');
		$mailer->addAddress('to@example.com');
		$mailer->setSubject('Hello');
		$mailer->setBody('World');

		$this->assertTrue($mailer->send());
		$this->assertEquals(SmtpMailer::ERROR_NONE, $mailer->getErrorCode());
	}

	public function testSend_smtpClientExceptionHandling()
	{
		$mockClient = $this->createCompatibleMock('SmtpClientInterface', [
			'connect',
			'authenticatePassword',
			'authenticateXOAuth2',
			'hasExtension',
			'sendMessage',
			'quit',
			'close'
		]);

		$mockClient->expects($this->once())
		->method('sendMessage')
		->willThrowException(new SmtpClientException('authentication', 'Invalid credentials for user: pass123'));

		// 例外発生時に close() が呼ばれること
		$mockClient->expects($this->once())->method('close');

		$mailer = $this->createCompatibleMock('SmtpMailer', ['createSmtpClient']);
		$expectationClient = $mailer->expects($this->once())->method('createSmtpClient');
		$this->setCompatibleReturnValue($expectationClient, $mockClient);

		$mailer->setHost('smtp.example.com');
		$mailer->setUsername('user');
		$mailer->setPassword('pass123');
		$mailer->setFrom('from@example.com');
		$mailer->addAddress('to@example.com');
		$mailer->setSubject('Hello');
		$mailer->setBody('World');

		$this->assertFalse($mailer->send());
		$this->assertEquals(SmtpMailer::ERROR_AUTHENTICATION, $mailer->getErrorCode());
		// パスワードがマスキングされていること
		$this->assertEquals('Invalid credentials for [redacted]: [redacted]', $mailer->getErrorInfo());
	}
}
