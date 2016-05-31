<?php
require_once(dirname(__FILE__).'/helpers/common.php');
require_once(dirname(__FILE__).'/../lib/class.simplemailer.php');

class InnerSessionTest extends PHPUnit_Framework_TestCase
{
	protected function setUp(){
		if(!isset($_SESSION)){
			$_SESSION=array();
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
		$ToEncoding = getProperty('SimpleMailer', 'ToEncoding');
		$mailer = new SimpleMailer();
		$this->assertEquals($FromEncoding->getValue($mailer), mb_internal_encoding());
		$this->assertEquals($ToEncoding->getValue($mailer), mb_internal_encoding());
	}
	
	public function testSetFromEncoding()
	{
		$FromEncoding = getProperty('SimpleMailer', 'FromEncoding');
		$mailer = new SimpleMailer();
		$mailer->setFromEncoding("test");
		$this->assertEquals($FromEncoding->getValue($mailer), "test");
	}
	public function testSetEncode()
	{
		$Encoding = getProperty('SimpleMailer', 'Encoding');
		$mailer = new SimpleMailer();
		$mailer->setEncode("test");
		$this->assertEquals($Encoding->getValue($mailer), "test");
	}
	
	public function testSetCharSet()
	{
		$ToEncoding = getProperty('SimpleMailer', 'ToEncoding');
		$mailer = new SimpleMailer();
		$mailer->setCharSet("test");
		$this->assertEquals($ToEncoding->getValue($mailer), "test");
	}
	
	public function testEncode()
	{
		$encode = getMethod('SimpleMailer', 'encode');
		$Encoding = getProperty('SimpleMailer', 'Encoding');
		$mailer = new SimpleMailer();
		$mailer->setFromEncoding("EUC-JP");
		$mailer->setCharSet("UTF-8");
		$value = $encode->invokeArgs($mailer, array(hex2bin('a5c6a5b9a5c8a5c7a1bca5bfa1bc')));
//		$this->assertEquals(bin2hex($value), 'e38386e382b9e38388e38387e383bce382bfe383bc');
		$this->assertEquals('テストデーター', $value);
	}
	
	public function testSetFrom()
	{
		$From = getProperty('SimpleMailer', 'From');
		$mailer = new SimpleMailer();
		$mailer->setFromEncoding("EUC-JP");
		$mailer->setCharSet("UTF-8");
		
		$mailer->setFrom('test@example.com');
		$from = $From->getValue($mailer);
		$this->assertEquals('test@example.com', $from[0]);
		$this->assertEquals('', $from[1]);
		
		$mailer->setFrom('test@example.com', hex2bin('a5c6a5b9a5c8a5c7a1bca5bfa1bc'));
		$from = $From->getValue($mailer);
		$this->assertEquals('test@example.com', $from[0]);
		$this->assertEquals('テストデーター', $from[1]);
	}
	
	public function testAddAddress()
	{
		$To = getProperty('SimpleMailer', 'To');
		$mailer = new SimpleMailer();
		$mailer->setFromEncoding("EUC-JP");
		$mailer->setCharSet("UTF-8");
		
		$mailer->addAddress('test1@example.com');
		$to = $To->getValue($mailer);
		$this->assertCount(1, $to);
		$this->assertEquals('test1@example.com', $to[0][0]);
		$this->assertEquals('', $to[0][1]);
		
		$mailer->addAddress('test2@example.com', hex2bin('a5c6a5b9a5c8a5c7a1bca5bfa1bc'));
		$to = $To->getValue($mailer);
		 $this->assertCount(2, $to);
		$this->assertEquals('test2@example.com', $to[1][0]);
		$this->assertEquals('テストデーター', $to[1][1]);
	}
	
	public function testAddCc()
	{
		$Cc = getProperty('SimpleMailer', 'Cc');
		$mailer = new SimpleMailer();
		$mailer->setFromEncoding("EUC-JP");
		$mailer->setCharSet("UTF-8");
		
		$mailer->addCc('test1@example.com');
		$cc = $Cc->getValue($mailer);
		$this->assertCount(1, $cc);
		$this->assertEquals('test1@example.com', $cc[0][0]);
		$this->assertEquals('', $cc[0][1]);
		
		$mailer->addCc('test2@example.com', hex2bin('a5c6a5b9a5c8a5c7a1bca5bfa1bc'));
		$cc = $Cc->getValue($mailer);
		 $this->assertCount(2, $cc);
		$this->assertEquals('test2@example.com', $cc[1][0]);
		$this->assertEquals('テストデーター', $cc[1][1]);
	}
	
	public function testAddBcc()
	{
		$Bcc = getProperty('SimpleMailer', 'Bcc');
		$mailer = new SimpleMailer();
		$mailer->setFromEncoding("EUC-JP");
		$mailer->setCharSet("UTF-8");
		
		$mailer->addBcc('test1@example.com');
		$bcc = $Bcc->getValue($mailer);
		$this->assertCount(1, $bcc);
		$this->assertEquals('test1@example.com', $bcc[0][0]);
		$this->assertEquals('', $bcc[0][1]);
		
		$mailer->addBcc('test2@example.com', hex2bin('a5c6a5b9a5c8a5c7a1bca5bfa1bc'));
		$bcc = $Bcc->getValue($mailer);
		 $this->assertCount(2, $bcc);
		$this->assertEquals('test2@example.com', $bcc[1][0]);
		$this->assertEquals('テストデーター', $bcc[1][1]);
	}
	
	public function testSetReplyTo()
	{
		$ReplyTo = getProperty('SimpleMailer', 'ReplyTo');
		$mailer = new SimpleMailer();
		$mailer->setFromEncoding("EUC-JP");
		$mailer->setCharSet("UTF-8");
		
		$mailer->setReplyTo('test1@example.com');
		$replyTo = $ReplyTo->getValue($mailer);
		$this->assertEquals('test1@example.com', $replyTo[0]);
		$this->assertEquals('', $replyTo[1]);
		
		$mailer->setReplyTo('test2@example.com', hex2bin('a5c6a5b9a5c8a5c7a1bca5bfa1bc'));
		$replyTo = $ReplyTo->getValue($mailer);
		$this->assertEquals('test2@example.com', $replyTo[0]);
		$this->assertEquals('テストデーター', $replyTo[1]);
	}
	
	public function testSetReturnPath()
	{
		$ReturnPath = getProperty('SimpleMailer', 'ReturnPath');
		$mailer = new SimpleMailer();
		$mailer->setFromEncoding("EUC-JP");
		$mailer->setCharSet("UTF-8");
		
		$mailer->setReturnPath('test1@example.com');
		$returnPath = $ReturnPath->getValue($mailer);
		$this->assertEquals('test1@example.com', $returnPath[0]);
		$this->assertEquals('', $returnPath[1]);
		
		$mailer->setReturnPath('test2@example.com', hex2bin('a5c6a5b9a5c8a5c7a1bca5bfa1bc'));
		$returnPath = $ReturnPath->getValue($mailer);
		$this->assertEquals('test2@example.com', $returnPath[0]);
		$this->assertEquals('テストデーター', $returnPath[1]);
	}
	
	public function testSetSubject()
	{
		$Subject = getProperty('SimpleMailer', 'Subject');
		$mailer = new SimpleMailer();
		$mailer->setFromEncoding("EUC-JP");
		$mailer->setCharSet("UTF-8");
		
		$mailer->setSubject(hex2bin('a5c6a5b9a5c8a5c7a1bca5bfa1bc'));
		$subject = $Subject->getValue($mailer);
		$this->assertEquals('テストデーター', $subject);
	}
	
	public function testSetBody()
	{
		$Body = getProperty('SimpleMailer', 'Body');
		$mailer = new SimpleMailer();
		$mailer->setFromEncoding("EUC-JP");
		$mailer->setCharSet("UTF-8");
		
		$mailer->setBody(hex2bin('a5c6a5b9a5c8a5c7a1bca5bfa1bc'));
		$body = $Body->getValue($mailer);
		$this->assertEquals('テストデーター', $body);
	}
	
	public function testSetAltBody()
	{
		// 実装なし
	}
	
	public function testAddAttachment()
	{
		// 実装なし
	}
	
	public function testAddHeader()
	{
		// 実装なし
	}
	
	public function testSend_falsePattern()
	{
		$mailer = $this->getMockBuilder('SimpleMailer')
		->setMethods(array('sendMail'))
		->getMock();
		
		$mailer->expects($this->any())
		->method('sendMail')
		->will($this->returnValue(false));

		$this->assertFalse($mailer->send());
		
		$mailer->addAddress('test1@example.com');
		$this->assertFalse($mailer->send());
		
		$mailer->setFrom('test1@example.com');
		$this->assertFalse($mailer->send());
	}
	
	public function testSend_trueSimplePattern()
	{
		$header = "From: from@example.com\n";
		$mailer = $this->getMockBuilder('SimpleMailer')
		->setMethods(array('sendMail'))
		->getMock();
		
		$mailer->expects($this->once())
		->method('sendMail')
		->with('to@example.com','subject','body', $header, NULL)
		->will($this->returnValue(true));
		
		$mailer->addAddress('to@example.com');
		$mailer->setFrom('from@example.com');
		$mailer->setBody('body');
		$mailer->setSubject('subject');
		$this->assertTrue($mailer->send());
	}
	
	public function testSend_trueReplyToPattern()
	{
		$header = "From: from@example.com\n";
		$mailer = $this->getMockBuilder('SimpleMailer')
		->setMethods(array('sendMail'))
		->getMock();
		
		$mailer->expects($this->once())
		->method('sendMail')
		->with('to@example.com','subject','body', $header."Reply-To: reply@example.com\n", NULL)
		->will($this->returnValue(true));
		
		$mailer->addAddress('to@example.com');
		$mailer->setFrom('from@example.com');
		$mailer->setBody('body');
		$mailer->setSubject('subject');
		$mailer->setReplyTo('reply@example.com');
		$this->assertTrue($mailer->send());
	}
	
	public function testSend_trueCcPattern()
	{
		$header = "From: from@example.com\n";
		$mailer = $this->getMockBuilder('SimpleMailer')
		->setMethods(array('sendMail'))
		->getMock();
		
		$mailer->expects($this->once())
		->method('sendMail')
		->with('to@example.com','subject','body', $header."Cc: cc@example.com\n", NULL)
		->will($this->returnValue(true));
		
		$mailer->addAddress('to@example.com');
		$mailer->setFrom('from@example.com');
		$mailer->setBody('body');
		$mailer->setSubject('subject');
		$mailer->AddCc('cc@example.com');
		$this->assertTrue($mailer->send());
	}
	
	public function testSend_trueBccPattern()
	{
		$header = "From: from@example.com\n";
		$mailer = $this->getMockBuilder('SimpleMailer')
		->setMethods(array('sendMail'))
		->getMock();
		
		$mailer->expects($this->once())
		->method('sendMail')
		->with('to@example.com','subject','body', $header."Bcc: bcc@example.com\n", NULL)
		->will($this->returnValue(true));
		
		$mailer->addAddress('to@example.com');
		$mailer->setFrom('from@example.com');
		$mailer->setBody('body');
		$mailer->setSubject('subject');
		$mailer->AddBcc('bcc@example.com');
		$this->assertTrue($mailer->send());
	}
	
	public function testSend_trueAllPattern()
	{
		$header = "From: from@example.com\n";
		$mailer = $this->getMockBuilder('SimpleMailer')
		->setMethods(array('sendMail'))
		->getMock();
		
		$mailer->expects($this->once())
		->method('sendMail')
		->with('to@example.com','subject','body', $header."Reply-To: reply@example.com\nCc: cc@example.com\nBcc: bcc@example.com\n", NULL)
		->will($this->returnValue(true));
		
		$mailer->addAddress('to@example.com');
		$mailer->setFrom('from@example.com');
		$mailer->setBody('body');
		$mailer->setSubject('subject');
		$mailer->setReplyTo('reply@example.com');
		$mailer->AddCc('cc@example.com');
		$mailer->AddBcc('bcc@example.com');
		$this->assertTrue($mailer->send());
	}
	
	public function testSend_trueCharset()
	{
		$header = "MIME-Version: 1.0\nContent-Transfer-Encoding: \nContent-Type : text/plain;\n\tcharset=\"UTF-8\";\nFrom: from@example.com\n";
		$mailer = $this->getMockBuilder('SimpleMailer')
		->setMethods(array('sendMail'))
		->getMock();
		
		$mailer->expects($this->once())
		->method('sendMail')
		->with('to@example.com','subject','body', $header, NULL)
		->will($this->returnValue(true));
		
		$mailer->setCharSet("UTF-8");
		$mailer->addAddress('to@example.com');
		$mailer->setFrom('from@example.com');
		$mailer->setBody('body');
		$mailer->setSubject('subject');
		$this->assertTrue($mailer->send());
	}
	
	public function testSendMail()
	{
		 // チェックできず
	}
	public function testMakeAddressTextFromAddresses()
	{
		$makeAddressTextFromAddresses = getMethod('SimpleMailer', 'makeAddressTextFromAddresses');
		$Encoding = getProperty('SimpleMailer', 'Encoding');
		$mailer = new SimpleMailer();
		$mailer->setFromEncoding("EUC-JP");
		$mailer->setCharSet("UTF-8");
		$value = $makeAddressTextFromAddresses->invokeArgs($mailer, array(array()));
		$this->assertEquals('', $value);
		
		$value = $makeAddressTextFromAddresses->invokeArgs($mailer, array(array(array('test@example.com', 'test'))));
		$this->assertEquals('test <test@example.com>', $value);
		
		$value = $makeAddressTextFromAddresses->invokeArgs($mailer, array(array(array('test@example.com', 'test'),array('test1@example.com', ''))));
		$this->assertEquals('test <test@example.com>,test1@example.com', $value);
	}
	
	public function testMakeAddressText()
	{
		$makeAddressText = getMethod('SimpleMailer', 'makeAddressText');
		$Encoding = getProperty('SimpleMailer', 'Encoding');
		$mailer = new SimpleMailer();
		$mailer->setFromEncoding("EUC-JP");
		$mailer->setCharSet("UTF-8");
		$value = $makeAddressText->invokeArgs($mailer, array('test@example.com', ''));
		$this->assertEquals('test@example.com', $value);
		
		$value = $makeAddressText->invokeArgs($mailer, array('test@example.com', 'test'));
		$this->assertEquals('test <test@example.com>', $value);
	}
}
?>