<?php
require_once(dirname(__FILE__).'/helpers/common.php');
require_once(dirname(__FILE__).'/../lib/class.simplemailer.php');

class SimpleMailerTest extends SimpleMailForm_TestCase
{
	protected function setUpCompat()
	{
		if (!isset($_SESSION)) {
			$_SESSION = [];
		}
	}

	public function testSimplemailerFactory_getInstance()
	{
		$factory = new SimplemailerFactory();
		$mailer = $factory->getInstance();
		$this->assertInstanceOf('SimpleMailer', $mailer);
	}

	public function test__construct()
	{
		$FromEncoding = getProperty('SimpleMailer', 'FromEncoding');
		$CharSet = getProperty('SimpleMailer', 'CharSet');
		$mailer = new SimpleMailer();
		$this->assertEquals(mb_internal_encoding(), $FromEncoding->getValue($mailer));
		$this->assertEquals('UTF-8', $CharSet->getValue($mailer));
	}

	public function testSetFromEncoding()
	{
		$FromEncoding = getProperty('SimpleMailer', 'FromEncoding');
		$mailer = new SimpleMailer();
		$mailer->setFromEncoding("EUC-JP");
		$this->assertEquals("EUC-JP", $FromEncoding->getValue($mailer));
	}

	public function testSetEncode()
	{
		$Encoding = getProperty('SimpleMailer', 'Encoding');
		$mailer = new SimpleMailer();
		$mailer->setEncode("7bit");
		$this->assertEquals("7bit", $Encoding->getValue($mailer));
	}

	public function testSetCharSet()
	{
		$CharSet = getProperty('SimpleMailer', 'CharSet');
		$mailer = new SimpleMailer();
		$mailer->setCharSet("ISO-2022-JP");
		$this->assertEquals("ISO-2022-JP", $CharSet->getValue($mailer));
	}

	public function testConvertToCharset()
	{
		$convertToCharset = getMethod('SimpleMailer', 'convertToCharset');
		$mailer = new SimpleMailer();
		$mailer->setFromEncoding("EUC-JP");
		$mailer->setCharSet("UTF-8");
		$value = $convertToCharset->invokeArgs($mailer, [hex2bin('a5c6a5b9a5c8a5c7a1bca5bfa1bc')]);
		$this->assertEquals('テストデーター', $value);
	}

	public function testEncodeBody()
	{
		$encodeBody = getMethod('SimpleMailer', 'encodeBody');
		$mailer = new SimpleMailer();

		// base64
		$mailer->setEncode('base64');
		$encodedBase64 = $encodeBody->invokeArgs($mailer, ['Hello World']);
		$this->assertEquals(chunk_split(base64_encode('Hello World'), 76, "\n"), $encodedBase64);

		// 7bit (正常系)
		$mailer->setEncode('7bit');
		$encoded7bit = $encodeBody->invokeArgs($mailer, ["Hello\r\nWorld"]);
		$this->assertEquals("Hello\nWorld\n", $encoded7bit);

		// 7bit (8bit文字が含まれるためエラー)
		$encoded7bitFail = $encodeBody->invokeArgs($mailer, ['テスト']);
		$this->assertFalse($encoded7bitFail);

		// 8bit
		$mailer->setEncode('8bit');
		$encoded8bit = $encodeBody->invokeArgs($mailer, ["テスト\r\n本文"]);
		$this->assertEquals("テスト\n本文\n", $encoded8bit);
	}

	public function testValidateEncoding()
	{
		$validateEncoding = getMethod('SimpleMailer', 'validateEncoding');
		$mailer = new SimpleMailer();

		$this->assertTrue($validateEncoding->invokeArgs($mailer, ['base64']));
		$this->assertTrue($validateEncoding->invokeArgs($mailer, ['7bit']));
		$this->assertTrue($validateEncoding->invokeArgs($mailer, ['8bit']));
		$this->assertFalse($validateEncoding->invokeArgs($mailer, ['quoted-printable']));
		$this->assertFalse($validateEncoding->invokeArgs($mailer, ['invalid']));
	}

	public function testSetFrom()
	{
		$From = getProperty('SimpleMailer', 'From');
		$mailer = new SimpleMailer();

		$mailer->setFrom('test@example.com');
		$from = $From->getValue($mailer);
		$this->assertEquals('test@example.com', $from[0]);
		$this->assertEquals('', $from[1]);

		$mailer->setFrom('test@example.com', 'テストユーザー');
		$from = $From->getValue($mailer);
		$this->assertEquals('test@example.com', $from[0]);
		$this->assertEquals('テストユーザー', $from[1]);
	}

	public function testAddAddress()
	{
		$To = getProperty('SimpleMailer', 'To');
		$mailer = new SimpleMailer();

		$mailer->addAddress('test1@example.com');
		$to = $To->getValue($mailer);
		$this->assertCount(1, $to);
		$this->assertEquals('test1@example.com', $to[0][0]);
		$this->assertEquals('', $to[0][1]);

		$mailer->addAddress('test2@example.com', 'テストユーザー');
		$to = $To->getValue($mailer);
		$this->assertCount(2, $to);
		$this->assertEquals('test2@example.com', $to[1][0]);
		$this->assertEquals('テストユーザー', $to[1][1]);
	}

	public function testAddCc()
	{
		$Cc = getProperty('SimpleMailer', 'Cc');
		$mailer = new SimpleMailer();

		$mailer->addCc('test1@example.com');
		$cc = $Cc->getValue($mailer);
		$this->assertCount(1, $cc);
		$this->assertEquals('test1@example.com', $cc[0][0]);
		$this->assertEquals('', $cc[0][1]);

		$mailer->addCc('test2@example.com', 'テストユーザー');
		$cc = $Cc->getValue($mailer);
		$this->assertCount(2, $cc);
		$this->assertEquals('test2@example.com', $cc[1][0]);
		$this->assertEquals('テストユーザー', $cc[1][1]);
	}

	public function testAddBcc()
	{
		$Bcc = getProperty('SimpleMailer', 'Bcc');
		$mailer = new SimpleMailer();

		$mailer->addBcc('test1@example.com');
		$bcc = $Bcc->getValue($mailer);
		$this->assertCount(1, $bcc);
		$this->assertEquals('test1@example.com', $bcc[0][0]);
		$this->assertEquals('', $bcc[0][1]);

		$mailer->addBcc('test2@example.com', 'テストユーザー');
		$bcc = $Bcc->getValue($mailer);
		$this->assertCount(2, $bcc);
		$this->assertEquals('test2@example.com', $bcc[1][0]);
		$this->assertEquals('テストユーザー', $bcc[1][1]);
	}

	public function testSetReplyTo()
	{
		$ReplyTo = getProperty('SimpleMailer', 'ReplyTo');
		$mailer = new SimpleMailer();

		$mailer->setReplyTo('test1@example.com');
		$replyTo = $ReplyTo->getValue($mailer);
		$this->assertEquals('test1@example.com', $replyTo[0]);
		$this->assertEquals('', $replyTo[1]);

		$mailer->setReplyTo('test2@example.com', 'テストユーザー');
		$replyTo = $ReplyTo->getValue($mailer);
		$this->assertEquals('test2@example.com', $replyTo[0]);
		$this->assertEquals('テストユーザー', $replyTo[1]);
	}

	public function testSetReturnPath()
	{
		$ReturnPath = getProperty('SimpleMailer', 'ReturnPath');
		$mailer = new SimpleMailer();

		$mailer->setReturnPath('test1@example.com');
		$returnPath = $ReturnPath->getValue($mailer);
		$this->assertEquals('test1@example.com', $returnPath[0]);
		$this->assertEquals('', $returnPath[1]);

		$mailer->setReturnPath('test2@example.com', 'テストユーザー');
		$returnPath = $ReturnPath->getValue($mailer);
		$this->assertEquals('test2@example.com', $returnPath[0]);
		$this->assertEquals('テストユーザー', $returnPath[1]);
	}

	public function testSetSubject()
	{
		$Subject = getProperty('SimpleMailer', 'Subject');
		$mailer = new SimpleMailer();

		$mailer->setSubject('件名テスト');
		$subject = $Subject->getValue($mailer);
		$this->assertEquals('件名テスト', $subject);
	}

	public function testSetBody()
	{
		$Body = getProperty('SimpleMailer', 'Body');
		$mailer = new SimpleMailer();

		$mailer->setBody('本文テスト');
		$body = $Body->getValue($mailer);
		$this->assertEquals('本文テスト', $body);
	}

	public function testSetAltBody()
	{
		$mailer = new SimpleMailer();
		$exceptionCaught = false;
		try {
			$mailer->setAltBody('alt body text');
		} catch (BadMethodCallException $e) {
			$exceptionCaught = true;
			$this->assertEquals('unimplemented', $e->getMessage());
		}
		$this->assertTrue($exceptionCaught);
	}

	public function testAddAttachment()
	{
		$mailer = new SimpleMailer();
		$exceptionCaught = false;
		try {
			$mailer->addAttachment('/path/to/file');
		} catch (BadMethodCallException $e) {
			$exceptionCaught = true;
			$this->assertEquals('unimplemented', $e->getMessage());
		}
		$this->assertTrue($exceptionCaught);
	}

	public function testAddHeader()
	{
		$mailer = new SimpleMailer();
		$exceptionCaught = false;
		try {
			$mailer->addHeader('X-Custom-Header', 'HeaderValue');
		} catch (BadMethodCallException $e) {
			$exceptionCaught = true;
			$this->assertEquals('unimplemented', $e->getMessage());
		}
		$this->assertTrue($exceptionCaught);
	}

	public function testSend_falsePattern()
	{
		$mailer = $this->createCompatibleMock('SimpleMailer', ['sendMail']);
		$mailer->expects($this->never())->method('sendMail');

		// To・From 未設定の場合
		$this->assertFalse($mailer->send());
		$this->assertEquals('You must provide at least one recipient email address and From address', $mailer->getErrorInfo());

		$mailer->addAddress('test1@example.com');
		$this->assertFalse($mailer->send());
		$this->assertEquals('You must provide at least one recipient email address and From address', $mailer->getErrorInfo());
	}

	public function testSend_invalidEncoding()
	{
		$mailer = $this->createCompatibleMock('SimpleMailer', ['sendMail']);
		$mailer->expects($this->never())->method('sendMail');

		$mailer->addAddress('to@example.com');
		$mailer->setFrom('from@example.com');
		$mailer->setEncode('invalid_encoding');

		$this->assertFalse($mailer->send());
		$this->assertEquals('Invalid encoding: invalid_encoding', $mailer->getErrorInfo());
	}

	public function testSend_headerInjectionInSubject()
	{
		$mailer = $this->createCompatibleMock('SimpleMailer', ['sendMail']);
		$mailer->expects($this->never())->method('sendMail');

		$mailer->addAddress('to@example.com');
		$mailer->setFrom('from@example.com');
		$mailer->setSubject("Subject\nHeader-Injection: test");

		$this->assertFalse($mailer->send());
		$this->assertEquals('Header injection attempt detected in subject', $mailer->getErrorInfo());
	}

	public function testSend_invalidAddresses()
	{
		$mailer = $this->createCompatibleMock('SimpleMailer', ['sendMail']);
		$mailer->expects($this->never())->method('sendMail');

		// 不正なFromアドレス
		$mailer->addAddress('to@example.com');
		$mailer->setFrom('invalid-email-address');
		$this->assertFalse($mailer->send());

		// 表示名に改行コードが含まれる
		$mailer->setFrom('from@example.com', "Header\nInjection");
		$this->assertFalse($mailer->send());
	}

	public function testSend_trueSimplePattern()
	{
		$mailer = $this->createCompatibleMock('SimpleMailer', ['sendMail']);

		$headerEol = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? "\r\n" : "\n";
		$expectedHeaders = implode($headerEol, [
		'MIME-Version: 1.0',
		'Content-Type: text/plain; charset=UTF-8',
		'Content-Transfer-Encoding: base64',
		'From: from@example.com',
		'X-Mailer: PHP/' . phpversion()
		]);

		$expectedBody = chunk_split(base64_encode('body'), 76, "\n");
		$expectedParams = '-f' . escapeshellarg('from@example.com');

		$expectation = $mailer->expects($this->once())
		->method('sendMail')
		->with('to@example.com', 'subject', $expectedBody, $expectedHeaders, $expectedParams);

		$this->setCompatibleReturnValue($expectation, true);

		$mailer->addAddress('to@example.com');
		$mailer->setFrom('from@example.com');
		$mailer->setBody('body');
		$mailer->setSubject('subject');
		$this->assertTrue($mailer->send());
	}

	public function testSend_trueReplyToPattern()
	{
		$mailer = $this->createCompatibleMock('SimpleMailer', ['sendMail']);

		$headerEol = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? "\r\n" : "\n";
		$expectedHeaders = implode($headerEol, [
		'MIME-Version: 1.0',
		'Content-Type: text/plain; charset=UTF-8',
		'Content-Transfer-Encoding: base64',
		'From: from@example.com',
		'Reply-To: reply@example.com',
		'X-Mailer: PHP/' . phpversion()
		]);

		$expectedBody = chunk_split(base64_encode('body'), 76, "\n");
		$expectedParams = '-f' . escapeshellarg('from@example.com');

		$expectation = $mailer->expects($this->once())
		->method('sendMail')
		->with('to@example.com', 'subject', $expectedBody, $expectedHeaders, $expectedParams);

		$this->setCompatibleReturnValue($expectation, true);

		$mailer->addAddress('to@example.com');
		$mailer->setFrom('from@example.com');
		$mailer->setBody('body');
		$mailer->setSubject('subject');
		$mailer->setReplyTo('reply@example.com');
		$this->assertTrue($mailer->send());
	}

	public function testSend_trueCcAndBccPattern()
	{
		$mailer = $this->createCompatibleMock('SimpleMailer', ['sendMail']);

		$headerEol = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? "\r\n" : "\n";
		$expectedHeaders = implode($headerEol, [
		'MIME-Version: 1.0',
		'Content-Type: text/plain; charset=UTF-8',
		'Content-Transfer-Encoding: base64',
		'From: from@example.com',
		'Cc: cc@example.com',
		'Bcc: bcc@example.com',
		'X-Mailer: PHP/' . phpversion()
		]);

		$expectedBody = chunk_split(base64_encode('body'), 76, "\n");
		$expectedParams = '-f' . escapeshellarg('from@example.com');

		$expectation = $mailer->expects($this->once())
		->method('sendMail')
		->with('to@example.com', 'subject', $expectedBody, $expectedHeaders, $expectedParams);

		$this->setCompatibleReturnValue($expectation, true);

		$mailer->addAddress('to@example.com');
		$mailer->setFrom('from@example.com');
		$mailer->setBody('body');
		$mailer->setSubject('subject');
		$mailer->addCc('cc@example.com');
		$mailer->addBcc('bcc@example.com');
		$this->assertTrue($mailer->send());
	}

	public function testSend_trueReturnPathPattern()
	{
		$mailer = $this->createCompatibleMock('SimpleMailer', ['sendMail']);

		$headerEol = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? "\r\n" : "\n";
		$expectedHeaders = implode($headerEol, [
		'MIME-Version: 1.0',
		'Content-Type: text/plain; charset=UTF-8',
		'Content-Transfer-Encoding: base64',
		'From: from@example.com',
		'X-Mailer: PHP/' . phpversion()
		]);

		$expectedBody = chunk_split(base64_encode('body'), 76, "\n");
		$expectedParams = '-f' . escapeshellarg('return@example.com');

		$expectation = $mailer->expects($this->once())
		->method('sendMail')
		->with('to@example.com', 'subject', $expectedBody, $expectedHeaders, $expectedParams);

		$this->setCompatibleReturnValue($expectation, true);

		$mailer->addAddress('to@example.com');
		$mailer->setFrom('from@example.com');
		$mailer->setReturnPath('return@example.com');
		$mailer->setBody('body');
		$mailer->setSubject('subject');
		$this->assertTrue($mailer->send());
	}

	public function testSendMail()
	{
		$sendMail = getMethod('SimpleMailer', 'sendMail');
		$mailer = new SimpleMailer();
		$result = @$sendMail->invokeArgs($mailer, ['to@example.com', 'subject', 'body', "From: from@example.com\r\n", null]);
		$this->assertTrue(is_bool($result));
	}

	public function testFormatAddress()
	{
		$formatAddress = getMethod('SimpleMailer', 'formatAddress');
		$mailer = new SimpleMailer();

		$value = $formatAddress->invokeArgs($mailer, [['test@example.com', '']]);
		$this->assertEquals('test@example.com', $value);

		$value = $formatAddress->invokeArgs($mailer, [['test@example.com', 'テスト']]);
		$this->assertTrue(strpos($value, '<test@example.com>') !== false);
	}

	public function testFormatAddressList()
	{
		$formatAddressList = getMethod('SimpleMailer', 'formatAddressList');
		$mailer = new SimpleMailer();

		$value = $formatAddressList->invokeArgs($mailer, [[]]);
		$this->assertEquals('', $value);

		$value = $formatAddressList->invokeArgs($mailer, [[['test1@example.com', ''], ['test2@example.com', '']]]);
		$this->assertEquals('test1@example.com, test2@example.com', $value);
	}

	public function testNormalizeAddress()
	{
		$normalizeAddress = getMethod('SimpleMailer', 'normalizeAddress');
		$mailer = new SimpleMailer();

		// 正常系：前後の空白が trim されて正常なアドレスが返ること
		$this->assertEquals('user@example.com', $normalizeAddress->invokeArgs($mailer, [' user@example.com ']));

		// 異常系：ローカルパートに8bit文字（最上位ビットが立っている非ASCII文字）が含まれる場合は false
		$this->assertFalse($normalizeAddress->invokeArgs($mailer, ["user\x80@example.com"]));

		// 異常系：ドメインに8bit文字が含まれる場合は false
		$this->assertFalse($normalizeAddress->invokeArgs($mailer, ["user@exam\x80ple.com"]));

		// 途中に改行が入っている場合は false になることを検証する
		$this->assertFalse($normalizeAddress->invokeArgs($mailer, ["user\n@example.com"]));

		// 異常系：メールアドレスフォーマット不正は false
		$this->assertFalse($normalizeAddress->invokeArgs($mailer, ['invalid-address']));
	}

	public function testHas8bitChars()
	{
		$has8bitChars = getMethod('SimpleMailer', 'has8bitChars');
		$mailer = new SimpleMailer();

		$this->assertFalse($has8bitChars->invokeArgs($mailer, ['ASCII text']));
		$this->assertTrue($has8bitChars->invokeArgs($mailer, ['マルチバイト']));
	}

	public function testHasHeaderBreaks()
	{
		$hasHeaderBreaks = getMethod('SimpleMailer', 'hasHeaderBreaks');
		$mailer = new SimpleMailer();

		$this->assertFalse($hasHeaderBreaks->invokeArgs($mailer, ['No break text']));
		$this->assertTrue($hasHeaderBreaks->invokeArgs($mailer, ["Break\ntext"]));
		$this->assertTrue($hasHeaderBreaks->invokeArgs($mailer, ["Break\rtext"]));
	}

	public function testNormalizeBreaks()
	{
		$normalizeBreaks = getMethod('SimpleMailer', 'normalizeBreaks');
		$mailer = new SimpleMailer();

		$text = "Line1\r\nLine2\rLine3\nLine4";
		$this->assertEquals("Line1\nLine2\nLine3\nLine4", $normalizeBreaks->invokeArgs($mailer, [$text, "\n"]));
		$this->assertEquals("Line1\r\nLine2\r\nLine3\r\nLine4", $normalizeBreaks->invokeArgs($mailer, [$text, "\r\n"]));
	}

	public function testGetErrorInfo()
	{
		$mailer = new SimpleMailer();
		$this->assertEquals('', $mailer->getErrorInfo());

		// send に失敗した際のエラーメッセージが反映されているか検証
		$mailer->send();
		$this->assertEquals('You must provide at least one recipient email address and From address', $mailer->getErrorInfo());
	}

	public function testSend_mailFunctionReturnsFalse()
	{
		$mailer = $this->createCompatibleMock('SimpleMailer', ['sendMail']);

		$expectation = $mailer->expects($this->once())
		->method('sendMail');
		$this->setCompatibleReturnValue($expectation, false);

		$mailer->addAddress('to@example.com');
		$mailer->setFrom('from@example.com');
		$mailer->setBody('body');
		$mailer->setSubject('subject');

		$this->assertFalse($mailer->send());
		$this->assertEquals('mail() function returned false', $mailer->getErrorInfo());
	}
}
