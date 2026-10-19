<?php
require_once(dirname(__FILE__).'/helpers/common.php');
require_once(dirname(__FILE__).'/../lib/interface.smtpclient.php');
require_once(dirname(__FILE__).'/../lib/class.smtpcurlclient.php');

class SmtpCurlClientTest extends SimpleMailForm_TestCase
{
	public function testConnect()
	{
		$client = new SmtpCurlClient();
		$client->connect('smtp.example.com', '465', 'SMTPS', 'helo.domain.com', '5');

		$this->assertEquals('smtp.example.com', getProperty('SmtpCurlClient', 'Host')->getValue($client));
		$this->assertEquals(465, getProperty('SmtpCurlClient', 'Port')->getValue($client));
		$this->assertEquals('smtps', getProperty('SmtpCurlClient', 'Encryption')->getValue($client));
		$this->assertEquals('helo.domain.com', getProperty('SmtpCurlClient', 'Helo')->getValue($client));
		$this->assertEquals(5, getProperty('SmtpCurlClient', 'Timeout')->getValue($client));

		$client->connect('smtp.example.com', 587, 'tls', 'helo', 0);
		$this->assertEquals(1, getProperty('SmtpCurlClient', 'Timeout')->getValue($client));
	}

	public function testAuthenticatePassword()
	{
		$client = new SmtpCurlClient();
		$client->authenticatePassword('testuser', 'secret', 'plain');

		$this->assertEquals('testuser', getProperty('SmtpCurlClient', 'Username')->getValue($client));
		$this->assertEquals('secret', getProperty('SmtpCurlClient', 'Password')->getValue($client));
		$this->assertEquals('plain', getProperty('SmtpCurlClient', 'AuthType')->getValue($client));
		$this->assertFalse(getProperty('SmtpCurlClient', 'IsOAuth')->getValue($client));
	}

	public function testAuthenticateXOAuth2()
	{
		$client = new SmtpCurlClient();
		$client->authenticateXOAuth2('oauth_user', 'token_xyz');

		$this->assertEquals('oauth_user', getProperty('SmtpCurlClient', 'Username')->getValue($client));
		$this->assertEquals('token_xyz', getProperty('SmtpCurlClient', 'AccessToken')->getValue($client));
		$this->assertTrue(getProperty('SmtpCurlClient', 'IsOAuth')->getValue($client));
	}

	public function testHasExtension()
	{
		$client = new SmtpCurlClient();
		// cURL実装ではパラメータの自力送出を回避するため false を返す仕様
		$this->assertFalse($client->hasExtension('SMTPUTF8'));
		$this->assertFalse($client->hasExtension('8BITMIME'));
		$this->assertFalse($client->hasExtension('PIPELINING'));
	}

	public function testSendMessage_senderInjectionException()
	{
		$client = new SmtpCurlClient();
		$exceptionCaught = false;

		try {
			$client->sendMessage("from@example.com\nInjection: header", ['to@example.com'], 'Test body');
		} catch (SmtpClientException $e) {
			$exceptionCaught = true;
			$this->assertEquals('sender', $e->getStage());
			$this->assertEquals('Sender address contains a line break', $e->getMessage());
		}

		$this->assertTrue($exceptionCaught);
	}

	public function testSendMessage_recipientInjectionException()
	{
		$client = new SmtpCurlClient();
		$exceptionCaught = false;

		try {
			$client->sendMessage('from@example.com', ["to@example.com\rInjection: header"], 'Test body');
		} catch (SmtpClientException $e) {
			$exceptionCaught = true;
			$this->assertEquals('recipient', $e->getStage());
			$this->assertEquals('Recipient address contains a line break', $e->getMessage());
		}

		$this->assertTrue($exceptionCaught);
	}

	public function testDetermineErrorStage()
	{
		$determineErrorStage = getMethod('SmtpCurlClient', 'determineErrorStage');
		$client = new SmtpCurlClient();

		// connection
		$this->assertEquals('connection', $determineErrorStage->invokeArgs($client, [CURLE_COULDNT_CONNECT]));
		$this->assertEquals('connection', $determineErrorStage->invokeArgs($client, [CURLE_COULDNT_RESOLVE_HOST]));
		$this->assertEquals('connection', $determineErrorStage->invokeArgs($client, [CURLE_OPERATION_TIMEDOUT]));

		// tls
		$this->assertEquals('tls', $determineErrorStage->invokeArgs($client, [CURLE_SSL_CONNECT_ERROR]));
		if (defined('CURLE_PEER_FAILED_VERIFICATION')) {
			$this->assertEquals('tls', $determineErrorStage->invokeArgs($client, [CURLE_PEER_FAILED_VERIFICATION]));
		} elseif (defined('CURLE_SSL_CACERT')) {
			$this->assertEquals('tls', $determineErrorStage->invokeArgs($client, [CURLE_SSL_CACERT]));
		}

		// authentication (定義されている場合)
		if (defined('CURLE_LOGIN_DENIED')) {
			$this->assertEquals('authentication', $determineErrorStage->invokeArgs($client, [CURLE_LOGIN_DENIED]));
		}

		// data
		$this->assertEquals('data', $determineErrorStage->invokeArgs($client, [CURLE_SEND_ERROR]));
		$this->assertEquals('data', $determineErrorStage->invokeArgs($client, [CURLE_RECV_ERROR]));

		// protocol
		$this->assertEquals('protocol', $determineErrorStage->invokeArgs($client, [9999]));
	}
}
