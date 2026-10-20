<?php
require_once(dirname(__FILE__).'/helpers/common.php');
require_once(dirname(__FILE__).'/../lib/class.oauth2mailer.php');

class OAuth2MailerTest extends SimpleMailForm_TestCase
{
	public function testOAuth2AccessTokenProvider_getAccessToken()
	{
		$provider = new OAuth2AccessTokenProvider('dummy_access_token');
		$this->assertEquals('dummy_access_token', $provider->getAccessToken());
	}

	public function testOAuth2AccessTokenProvider_emptyTokenThrowsException()
	{
		$provider = new OAuth2AccessTokenProvider('');
		$exceptionThrown = false;

		try {
			$provider->getAccessToken();
		} catch (RuntimeException $e) {
			$exceptionThrown = true;
			$this->assertTrue(strpos($e->getMessage(), 'OAuth2 access token is empty') !== false);
		}

		$this->assertTrue($exceptionThrown, 'Expected RuntimeException was not thrown');
	}

	public function testOAuth2RefreshTokenProvider_constructSuccess()
	{
		$provider = new OAuth2RefreshTokenProvider(
			'https://oauth2.example.com/token',
			'client_id_123',
			'client_secret_abc',
			'refresh_token_xyz',
			15
		);
		$this->assertInstanceOf('OAuth2RefreshTokenProvider', $provider);
	}

	public function testOAuth2RefreshTokenProvider_invalidEndpointUrl()
	{
		$exceptionThrown = false;
		try {
			// HTTPSではないURL
			new OAuth2RefreshTokenProvider('http://oauth2.example.com/token', 'id', 'secret', 'refresh');
		} catch (InvalidArgumentException $e) {
			$exceptionThrown = true;
			$this->assertTrue(strpos($e->getMessage(), 'HTTPS URL') !== false);
		}
		$this->assertTrue($exceptionThrown, 'Expected InvalidArgumentException for non-HTTPS URL');
	}

	public function testOAuth2RefreshTokenProvider_invalidPort()
	{
		$exceptionThrown = false;
		try {
			// ポート番号付きURL（指定不可）
			new OAuth2RefreshTokenProvider('https://oauth2.example.com:8080/token', 'id', 'secret', 'refresh');
		} catch (InvalidArgumentException $e) {
			$exceptionThrown = true;
			$this->assertTrue(strpos($e->getMessage(), 'port number') !== false);
		}
		$this->assertTrue($exceptionThrown, 'Expected InvalidArgumentException for URL with port number');
	}

	public function testOAuth2RefreshTokenProvider_missingParameters()
	{
		$exceptionThrown = false;
		try {
			// クライアントIDが空
			new OAuth2RefreshTokenProvider('https://oauth2.example.com/token', '', 'secret', 'refresh');
		} catch (InvalidArgumentException $e) {
			$exceptionThrown = true;
			$this->assertTrue(strpos($e->getMessage(), 'required') !== false);
		}
		$this->assertTrue($exceptionThrown, 'Expected InvalidArgumentException for empty parameters');
	}

	public function testOAuth2RefreshTokenProvider_getHttpStatus()
	{
		$provider = new OAuth2RefreshTokenProvider('https://oauth2.example.com', 'id', 'secret', 'refresh');
		$method = getMethod('OAuth2RefreshTokenProvider', 'getHttpStatus');

		$headers200 = ['HTTP/1.1 200 OK', 'Content-Type: application/json'];
		$this->assertEquals(200, $method->invokeArgs($provider, [$headers200]));

		$headers404 = ['HTTP/2 404 Not Found', 'Date: Mon, 20 Oct 2026'];
		$this->assertEquals(404, $method->invokeArgs($provider, [$headers404]));

		$headersInvalid = ['Content-Type: text/html'];
		$this->assertEquals(0, $method->invokeArgs($provider, [$headersInvalid]));
	}

	public function testOAuth2RefreshTokenProvider_getAccessToken_cached()
	{
		$provider = new OAuth2RefreshTokenProvider('https://oauth2.example.com/token', 'id', 'secret', 'refresh');

		// リフレクションを用いてキャッシュされたトークンと有効期限（未来）を擬似的にセット
		getProperty('OAuth2RefreshTokenProvider', 'AccessToken')->setValue($provider, 'cached_token_123');
		getProperty('OAuth2RefreshTokenProvider', 'ExpiresAt')->setValue($provider, time() + 3600);

		$this->assertEquals('cached_token_123', $provider->getAccessToken());
	}

	public function testOAuth2MailerFactory_withProviderInstance()
	{
		$provider = new OAuth2AccessTokenProvider('token_from_factory');
		$factory = new OAuth2MailerFactory(
			'smtp.example.com',
			465,
			'ssl',
			'user@example.com',
			$provider,
			'mail.domain.com',
			20
		);

		$mailer = $factory->getInstance();
		$this->assertInstanceOf('OAuth2Mailer', $mailer);
		// 正しく情報がセットされるか
		$this->assertEquals('smtp.example.com', getProperty('SmtpMailer', 'Host')->getValue($mailer));
		$this->assertEquals(465, getProperty('SmtpMailer', 'Port')->getValue($mailer));
		$this->assertEquals('ssl', getProperty('SmtpMailer', 'SMTPSecure')->getValue($mailer));
		$this->assertEquals('user@example.com', getProperty('SmtpMailer', 'Username')->getValue($mailer));
		$this->assertEquals('mail.domain.com', getProperty('SmtpMailer', 'Helo')->getValue($mailer));
		$this->assertEquals(20, getProperty('SmtpMailer', 'Timeout')->getValue($mailer));

		// プロバイダが正しくセットされているか
		$this->assertSame($provider, getProperty('OAuth2Mailer', 'OAuth2TokenProvider')->getValue($mailer));
	}

	public function testOAuth2MailerFactory_withTokenString()
	{
		$factory = new OAuth2MailerFactory('smtp.example.com', 587, 'tls', 'user@example.com', 'direct_string_token', 'mail.domain.com', 15);
		$mailer = $factory->getInstance();

		$this->assertInstanceOf('OAuth2Mailer', $mailer);
		$this->assertEquals('smtp.example.com', getProperty('SmtpMailer', 'Host')->getValue($mailer));
		$this->assertEquals(587, getProperty('SmtpMailer', 'Port')->getValue($mailer));
		$this->assertEquals('tls', getProperty('SmtpMailer', 'SMTPSecure')->getValue($mailer));
		$this->assertEquals('user@example.com', getProperty('SmtpMailer', 'Username')->getValue($mailer));
		$this->assertEquals('mail.domain.com', getProperty('SmtpMailer', 'Helo')->getValue($mailer));
		$this->assertEquals(15, getProperty('SmtpMailer', 'Timeout')->getValue($mailer));

		$provider = getProperty('OAuth2Mailer', 'OAuth2TokenProvider')->getValue($mailer);
		$this->assertInstanceOf('OAuth2AccessTokenProvider', $provider);
		$this->assertEquals('direct_string_token', $provider->getAccessToken());
	}

	public function testOAuth2Mailer_validateAuthenticationConfiguration()
	{
		$validateMethod = getMethod('OAuth2Mailer', 'validateAuthenticationConfiguration');
		$mailer = new OAuth2Mailer();

		// ユーザー名がない場合は失敗
		$this->assertFalse($validateMethod->invokeArgs($mailer, []));
		$this->assertEquals('OAuth2 user email is required', $mailer->getErrorInfo());

		// ユーザー名はあるが、プロバイダがない場合は失敗
		$mailer->setUsername('user@example.com');
		$this->assertFalse($validateMethod->invokeArgs($mailer, []));
		$this->assertEquals('OAuth2 token provider is required', $mailer->getErrorInfo());

		// プロバイダ（直接のトークン文字列から）をセットすれば成功
		$mailer->setAccessToken('valid_token_string');
		$this->assertTrue($validateMethod->invokeArgs($mailer, []));
	}

	public function testOAuth2Mailer_authenticateClient_success()
	{
		$mailer = new OAuth2Mailer();
		$mailer->setUsername('user@example.com');
		$mailer->setAccessToken('xoauth2_access_token');

		// SMTPクライアントのモック作成
		$mockClient = $this->createCompatibleMock('SmtpClientInterface', [
			'connect', 'authenticatePassword', 'authenticateXOAuth2', 'hasExtension', 'sendMessage', 'quit', 'close'
		]);

		// authenticateXOAuth2が呼ばれることを期待
		$mockClient->expects($this->once())
		->method('authenticateXOAuth2')
		->with('user@example.com', 'xoauth2_access_token');

		$authenticateClient = getMethod('OAuth2Mailer', 'authenticateClient');
		$authenticateClient->invokeArgs($mailer, [$mockClient]);
	}

	public function testOAuth2Mailer_authenticateClient_exceptionHandling()
	{
		$mailer = new OAuth2Mailer();
		$mailer->setUsername('user@example.com');

		$mockProvider = $this->createCompatibleMock('OAuth2TokenProviderInterface', ['getAccessToken']);
		$mockProvider->expects($this->once())
		->method('getAccessToken')
		->willThrowException(new RuntimeException('Network failure'));

		$mailer->setOAuth2TokenProvider($mockProvider);

		$mockClient = $this->createCompatibleMock('SmtpClientInterface', [
		'connect',
		'authenticatePassword',
		'authenticateXOAuth2',
		'hasExtension',
		'sendMessage',
		'quit',
		'close'
		]);
		$mockClient->expects($this->never())->method('authenticateXOAuth2');

		$exceptionThrown = false;
		$authenticateClient = getMethod('OAuth2Mailer', 'authenticateClient');

		try {
			$authenticateClient->invokeArgs($mailer, [$mockClient]);
		} catch (SmtpClientException $e) {
			$exceptionThrown = true;
			$this->assertEquals('authentication', $e->getStage());
			$this->assertTrue(strpos($e->getMessage(), 'Unable to obtain an OAuth2 access token') !== false);
		}

		$this->assertTrue($exceptionThrown, 'Expected SmtpClientException was not thrown');
	}
}
