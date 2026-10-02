<?php
require_once(dirname(__FILE__).'/helpers/common.php');

class PHPMailerTest extends SimpleMailForm_TestCase
{
	protected function setUpCompat()
	{
		if (!file_exists(dirname(__FILE__) . '/../external/phpmailer/class.phpmailer.php')) {
			$this->markTestSkipped('external/phpmailer/class.phpmailer.php is not present.');
			return;
		}
		require_once(dirname(__FILE__).'/../lib/class.phpmailer.php');

		if (!isset($_SESSION)) {
			$_SESSION = [];
		}
	}

	public function testEncodePHPMailerFactory_getInstance()
	{
		$factory = new EncodePHPMailerFactory();
		$mailer = $factory->getInstance();
		$this->assertInstanceOf('EncodePHPMailer', $mailer);
	}

	public function testConstruct()
	{
		$mailer = new EncodePHPMailer();
		$this->assertEquals(mb_internal_encoding(), $mailer->FromEncoding);
		$this->assertEquals(mb_internal_encoding(), $mailer->ToEncoding);
	}

	public function testSetFromEncoding()
	{
		$mailer = new EncodePHPMailer();
		$mailer->setFromEncoding('EUC-JP');
		$this->assertEquals('EUC-JP', $mailer->FromEncoding);
	}

	public function testSetEncode()
	{
		$mailer = new EncodePHPMailer();
		$mailer->setEncode('base64');
		$this->assertEquals('base64', $mailer->Encoding);
	}

	public function testSetCharSet()
	{
		$mailer = new EncodePHPMailer();
		$mailer->setCharSet('UTF-8');
		$this->assertEquals('UTF-8', $mailer->ToEncoding);
		$this->assertEquals('UTF-8', $mailer->CharSet);
	}

	public function testSetFromAndAddresses()
	{
		$mailer = new EncodePHPMailer();
		$mailer->setCharSet('UTF-8');
		$mailer->setFromEncoding('UTF-8');
		
		$mailer->setFrom('from@example.com', '送信者');
		$this->assertEquals('from@example.com', $mailer->From);
		$this->assertEquals('送信者', $mailer->FromName);

		$mailer->addAddress('to@example.com', '宛先');
		$mailer->addCc('cc@example.com', 'CC宛先');
		$mailer->addBcc('bcc@example.com', 'BCC宛先');
		$mailer->setReplyTo('reply@example.com', '返信先');

		$this->assertTrue(count($mailer->getToAddresses()) > 0);
		$this->assertTrue(count($mailer->getCcAddresses()) > 0);
		$this->assertTrue(count($mailer->getBccAddresses()) > 0);
		$this->assertTrue(count($mailer->getReplyToAddresses()) > 0);
	}

	public function testSetSubjectAndBodyAndAltBody()
	{
		$mailer = new EncodePHPMailer();
		$mailer->setCharSet('UTF-8');
		$mailer->setFromEncoding('UTF-8');

		$mailer->setSubject('件名テスト');
		$this->assertEquals('件名テスト', $mailer->Subject);

		$mailer->setBody('本文テスト', false);
		$this->assertEquals('本文テスト', $mailer->Body);

		$mailer->setAltBody('代替本文テスト');
		$this->assertEquals('代替本文テスト', $mailer->AltBody);
	}

	public function testSetReturnPath()
	{
		$mailer = new EncodePHPMailer();
		$mailer->setReturnPath('return@example.com', 'リターン');
		$this->assertEquals('リターン<return@example.com>', $mailer->Sender);
	}

	public function testAddHeader()
	{
		$mailer = new EncodePHPMailer();
		$mailer->addHeader('X-Test-Header', '');
		$mailer->addHeader('X-Test-Header', 'HeaderValue');
		$this->assertTrue(is_array($mailer->getCustomHeaders()));
	}

	public function testEncodeHeaderFilename()
	{
		$encodeHeaderFilename = getMethod('EncodePHPMailer', 'encodeHeaderFilename');
		$mailer = new EncodePHPMailer();
		$mailer->setCharSet('UTF-8');

		// ASCII filename
		$res1 = $encodeHeaderFilename->invokeArgs($mailer, ['test.txt']);
		$this->assertTrue(strpos($res1, 'filename=') !== false);

		// Long multibyte filename
		$longName = '非常に長い日本語のファイル名テスト用あいうえおかきくけこさしすせそたちつてとなにぬねの.txt';
		$res2 = $encodeHeaderFilename->invokeArgs($mailer, [$longName]);
		$this->assertTrue(strpos($res2, 'filename*') !== false);
	}
}
