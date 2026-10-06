<?php
require_once(dirname(__FILE__).'/helpers/common.php');
require_once(dirname(__FILE__).'/helpers/TestMailer.php');
require_once(dirname(__FILE__).'/helpers/TestSession.php');
require_once(dirname(__FILE__).'/../lib/class.mailform.php');

class MailFormTest extends SimpleMailForm_TestCase
{
	protected function setUpCompat()
	{
		if (!isset($_SESSION)) {
			$_SESSION=[];
		}
		$_SERVER=['DOCUMENT_ROOT' => '/document/root'];
	}
	
	public function test__construct()
	{
		$mailform = new MailForm();
		$FromEncoding = getProperty('MailForm', 'FromEncoding');
		$CharSet = getProperty('MailForm', 'CharSet');
		$Status = getProperty('MailForm', 'Status');
		$TmpDirectoryPath = getProperty('MailForm', 'TmpDirectoryPath');
		
		$this->assertEquals($mailform->FromEncoding, mb_internal_encoding());
		$this->assertEquals($mailform->CharSet, mb_internal_encoding());
		$this->assertEquals($Status->getValue($mailform), MailForm::STATUS_NONE);
		$this->assertEquals($mailform->TmpDirectoryPath, '/document/root/tmp/');
	}
	
	public function testGetDateTime()
	{
		$mailform = new MailForm();
		$this->assertEquals(date('Y/m/d(D) H:i'), $mailform->getDateTime());
	}
	
	public function testgetParameter()
	{
		$mailform = new MailForm();
		
		$this->assertNull($mailform->getParameter([], 'key'));
		$this->assertEquals('testtest', $mailform->getParameter(['key' => "test\0test"], 'key'));
	}
	
	public function testSetMailerFactory()
	{
		$MailerFactory = getProperty('MailForm', 'MailerFactory');
		$mailform = new MailForm();
		$factory = new TestmailerFactory();
		$mailform->setMailerFactory($factory);
		$this->assertEquals($factory, $MailerFactory->getValue($mailform));
	}
	
	public function testGetMailer()
	{
		$getMailer = getMethod('MailForm', 'getMailer');
		$mailform = new MailForm();
		
		$this->assertInstanceOf('SimpleMailer', $getMailer->invokeArgs($mailform, []));
		
		$mailform = new MailForm();
		$factory = new TestmailerFactory();
		$mailform->setMailerFactory($factory);
		$this->assertInstanceOf('TestMailer', $getMailer->invokeArgs($mailform, []));
	}
	
	public function testSetSessionFactory()
	{
		$SessionFactory = getProperty('MailForm', 'SessionFactory');
		$mailform = new MailForm();
		$factory = new TestSessionFactory();
		$mailform->setSessionFactory($factory);
		$this->assertEquals($factory, $SessionFactory->getValue($mailform));
	}
	
	public function testGetSession()
	{
		$getSession = getMethod('MailForm', 'getSession');
		$mailform = new MailForm();
		$factory = new TestSessionFactory();
		$mailform->setSessionFactory($factory);
		$this->assertInstanceOf('TestSession', $getSession->invokeArgs($mailform, []));
	}
	
	public function testSetErrorCodeMessage()
	{
		$ErrorCodeMessages = getProperty('MailForm', 'ErrorCodeMessages');
		$mailform = new MailForm();
		$mailform->setErrorCodeMessage(1000000, 'test');
		$errorCodeMessages = $ErrorCodeMessages->getValue($mailform);
		$this->assertArrayHasKey(1000000, $errorCodeMessages);
		$this->assertEquals('test', $errorCodeMessages[1000000]);
	}
	
	public function testGetStatus()
	{
		$Status = getProperty('MailForm', 'Status');
		$mailform = new MailForm();
		$Status->setValue($mailform, 100);
		$this->assertEquals(100, $mailform->getStatus());
	}
	
	public function testIsError()
	{
		$ErrorCode = getProperty('MailForm', 'ErrorCode');
		$mailform = new MailForm();
		$ErrorCode->setValue($mailform, MailForm::ERROR_CODE_OK);
		$this->assertFalse($mailform->isError());
		
		$ErrorCode->setValue($mailform, MailForm::ERROR_CODE_NOSET_TO);
		$this->assertTrue($mailform->isError());
		
	}
	
	public function testSetErrorMessage()
	{
		$ErrorMessages = getProperty('MailForm', 'ErrorMessages');
		$ErrorCode = getProperty('MailForm', 'ErrorCode');
		$mailform = new MailForm();
		$this->assertFalse($mailform->isError());
		
		$mailform->setErrorMessage(MailForm::BASE_ERROR_KEY, MailForm::ERROR_CODE_OK);
		$errorMessages = $ErrorMessages->getValue($mailform);
		$this->assertCount(0, $errorMessages);
		
		$mailform->setErrorMessage('key', MailForm::ERROR_CODE_NOT_NULL_PARAM, "title");
		$this->assertEquals(MailForm::ERROR_CODE_NOT_NULL_PARAM, $ErrorCode->getValue($mailform));
		$errorMessages = $ErrorMessages->getValue($mailform);
		$this->assertArrayHasKey('key', $errorMessages);
		$this->assertEquals('「title」は必ずご入力ください', $errorMessages['key']);
		
		$mailform->setErrorMessage('key2', 100000, "title2");
		$this->assertEquals(100000, $ErrorCode->getValue($mailform));
		$errorMessages = $ErrorMessages->getValue($mailform);
		$this->assertCount(2, $errorMessages);
		$this->assertArrayHasKey('key2', $errorMessages);
		$this->assertEquals('エラーが発生しています', $errorMessages['key2']);
		
		$mailform->setErrorMessage(MailForm::BASE_ERROR_KEY, MailForm::ERROR_CODE_INVALID_FORM_CONFIG);
		$this->assertEquals(MailForm::ERROR_CODE_INVALID_FORM_CONFIG, $ErrorCode->getValue($mailform));
		$errorMessages = $ErrorMessages->getValue($mailform);
		$this->assertCount(1, $errorMessages);
		$this->assertArrayHasKey(MailForm::BASE_ERROR_KEY, $errorMessages);
		$this->assertEquals('システム設定エラーが発生しました', $errorMessages[MailForm::BASE_ERROR_KEY]);
	}
	
	public function testIsCriticalError()
	{
		$ErrorMessages = getProperty('MailForm', 'ErrorMessages');
		$mailform = new MailForm();
		
		$this->assertFalse($mailform->isCriticalError());
		
		$mailform->setErrorMessage('key', MailForm::ERROR_CODE_NOT_NULL_PARAM, "title");
		$this->assertFalse($mailform->isCriticalError());
		
		$mailform->setErrorMessage(MailForm::BASE_ERROR_KEY, MailForm::ERROR_CODE_INVALID_FORM_CONFIG);
		$this->assertTrue($mailform->isCriticalError());
	}
	
	public function testIsErrorByFieldName()
	{
		$ErrorMessages = getProperty('MailForm', 'ErrorMessages');
		$mailform = new MailForm();
		
		$this->assertFalse($mailform->isErrorByFieldName('key'));
		
		$mailform->setErrorMessage('key', MailForm::ERROR_CODE_NOT_NULL_PARAM, "title");
		$this->assertTrue($mailform->isErrorByFieldName('key'));
		$this->assertFalse($mailform->isErrorByFieldName('key1'));
	}
	
	public function testGetErrorMessage()
	{
		$mailform = new MailForm();
		$this->assertEquals('', $mailform->getErrorMessage());
		
		$mailform->setErrorMessage('key1', MailForm::ERROR_CODE_NOT_NULL_PARAM, "title1");
		$this->assertEquals("\n「title1」は必ずご入力ください", $mailform->getErrorMessage());
		$mailform->setErrorMessage('key2', MailForm::ERROR_CODE_NOT_NULL_PARAM, "title2");
		$this->assertEquals("\n「title1」は必ずご入力ください\n「title2」は必ずご入力ください", $mailform->getErrorMessage());
	}
	
	public function testGetErrorMessages()
	{
		$mailform = new MailForm();
		$this->assertEquals([], $mailform->getErrorMessages());
		
		$mailform->setErrorMessage('key1', MailForm::ERROR_CODE_NOT_NULL_PARAM, "title1");
		$this->assertEquals(['「title1」は必ずご入力ください'], $mailform->getErrorMessages());
		
		$mailform->setErrorMessage('key2', MailForm::ERROR_CODE_NOT_NULL_PARAM, "title1");
		$this->assertEquals(['「title1」は必ずご入力ください'], $mailform->getErrorMessages());
		
		$mailform->setErrorMessage('key3', MailForm::ERROR_CODE_NOT_NULL_PARAM, "title3");
		$this->assertEquals(['「title1」は必ずご入力ください','「title3」は必ずご入力ください'], $mailform->getErrorMessages());
	}
	
	public function testGetMailConfigByIndex()
	{
		$getMailConfigByIndex = getMethod('MailForm', 'getMailConfigByIndex');
		$mailform = new MailForm();
		$test1Config = $getMailConfigByIndex->invokeArgs($mailform, ['test1']);
		$test1Config->ToAddresses['test1@example.com'] = 'test1';
		$test2Config = $getMailConfigByIndex->invokeArgs($mailform, ['test2']);
		$test2Config->ToAddresses['test2@example.com'] = 'test2';
		
		$this->assertEquals($test1Config, $getMailConfigByIndex->invokeArgs($mailform, ['test1']));
		$this->assertEquals($test2Config, $getMailConfigByIndex->invokeArgs($mailform, ['test2']));
		
	}
	
	public function testAddToAndGetToArray()
	{
		$getToArray = getMethod('MailForm', 'getToArray');
		$mailform = new MailForm();
		$mailform->addTo('to1@example.com', 'to1');
		$tos = $getToArray->invokeArgs($mailform, []);
		$this->assertCount(1, $tos);
		$this->assertArrayHasKey('to1@example.com', $tos);
		$this->assertEquals('to1', $tos['to1@example.com']);
		
		$mailform->addTo('to2@example.com', 'to2');
		$tos = $getToArray->invokeArgs($mailform, []);
		$this->assertCount(2, $tos);
		$this->assertArrayHasKey('to2@example.com', $tos);
		$this->assertEquals('to2', $tos['to2@example.com']);
	}
	
	public function testAddToByIndexAndGetToArrayByIndex()
	{
		$getToArrayByIndex = getMethod('MailForm', 'getToArrayByIndex');
		$mailform = new MailForm();
		
		$mailform->addToByIndex(1, 'to1@example.com', 'to1');
		$tos = $getToArrayByIndex->invokeArgs($mailform, [1]);
		$this->assertCount(1, $tos);
		$this->assertArrayHasKey('to1@example.com', $tos);
		$this->assertEquals('to1', $tos['to1@example.com']);
		
		$mailform->addToByIndex(1, 'to2@example.com', 'to2');
		$tos = $getToArrayByIndex->invokeArgs($mailform, [1]);
		$this->assertCount(2, $tos);
		$this->assertArrayHasKey('to2@example.com', $tos);
		$this->assertEquals('to2', $tos['to2@example.com']);
		
		$mailform->addToByIndex(2, 'to3@example.com', 'to3');
		$tos = $getToArrayByIndex->invokeArgs($mailform, [2]);
		$this->assertCount(1, $tos);
		$this->assertArrayHasKey('to3@example.com', $tos);
		$this->assertEquals('to3', $tos['to3@example.com']);
	}
	
	public function testAddFromAndGetFromArray()
	{
		$getFromArray = getMethod('MailForm', 'getFromArray');
		$mailform = new MailForm();
		$mailform->setFrom('from1@example.com', 'from1');
		$froms = $getFromArray->invokeArgs($mailform, []);
		$this->assertCount(1, $froms);
		$this->assertArrayHasKey('from1@example.com', $froms);
		$this->assertEquals('from1', $froms['from1@example.com']);
		
		$mailform->setFrom('from2@example.com', 'from2');
		$froms = $getFromArray->invokeArgs($mailform, []);
		$this->assertCount(1, $froms);
		$this->assertArrayHasKey('from2@example.com', $froms);
		$this->assertEquals('from2', $froms['from2@example.com']);
	}
	
	public function testAddFromByIndexAndGetFromArrayByIndex()
	{
		$getFromArrayByIndex = getMethod('MailForm', 'getFromArrayByIndex');
		$mailform = new MailForm();
		
		$mailform->setFromByIndex(1, 'from1@example.com', 'from1');
		$froms = $getFromArrayByIndex->invokeArgs($mailform, [1]);
		$this->assertCount(1, $froms);
		$this->assertArrayHasKey('from1@example.com', $froms);
		$this->assertEquals('from1', $froms['from1@example.com']);
		
		$mailform->setFromByIndex(1, 'from2@example.com', 'from2');
		$froms = $getFromArrayByIndex->invokeArgs($mailform, [1]);
		$this->assertCount(1, $froms);
		$this->assertArrayHasKey('from2@example.com', $froms);
		$this->assertEquals('from2', $froms['from2@example.com']);
		
		$mailform->setFromByIndex(2, 'from3@example.com', 'from3');
		$froms = $getFromArrayByIndex->invokeArgs($mailform, [2]);
		$this->assertCount(1, $froms);
		$this->assertArrayHasKey('from3@example.com', $froms);
		$this->assertEquals('from3', $froms['from3@example.com']);
	}
	
	public function testAddReturnPathAndGetReturnPathArray()
	{
		$getReturnPathArray = getMethod('MailForm', 'getReturnPathArray');
		$mailform = new MailForm();
		$mailform->setReturnPath('path1@example.com', 'path1');
		$paths = $getReturnPathArray->invokeArgs($mailform, []);
		$this->assertCount(1, $paths);
		$this->assertArrayHasKey('path1@example.com', $paths);
		$this->assertEquals('path1', $paths['path1@example.com']);
		
		$mailform->setReturnPath('path2@example.com', 'path2');
		$paths = $getReturnPathArray->invokeArgs($mailform, []);
		$this->assertCount(1, $paths);
		$this->assertArrayHasKey('path2@example.com', $paths);
		$this->assertEquals('path2', $paths['path2@example.com']);
	}

	public function testAddReturnPathByIndexAndGetReturnPathArrayByIndex()
	{
		$getReturnPathArrayByIndex = getMethod('MailForm', 'getReturnPathArrayByIndex');
		$mailform = new MailForm();
		
		$mailform->setReturnPathByIndex(1, 'path1@example.com', 'path1');
		$paths = $getReturnPathArrayByIndex->invokeArgs($mailform, [1]);
		$this->assertCount(1, $paths);
		$this->assertArrayHasKey('path1@example.com', $paths);
		$this->assertEquals('path1', $paths['path1@example.com']);
		
		$mailform->setReturnPathByIndex(1, 'path2@example.com', 'path2');
		$paths = $getReturnPathArrayByIndex->invokeArgs($mailform, [1]);
		$this->assertCount(1, $paths);
		$this->assertArrayHasKey('path2@example.com', $paths);
		$this->assertEquals('path2', $paths['path2@example.com']);
		
		$mailform->setReturnPathByIndex(2, 'path3@example.com', 'path3');
		$paths = $getReturnPathArrayByIndex->invokeArgs($mailform, [2]);
		$this->assertCount(1, $paths);
		$this->assertArrayHasKey('path3@example.com', $paths);
		$this->assertEquals('path3', $paths['path3@example.com']);
	}

	public function testSetReplyToAndGetReplyToArray()
	{
		$getReplyToArray = getMethod('MailForm', 'getReplyToArray');
		$mailform = new MailForm();
		$mailform->setReplyTo('reply1@example.com', 'reply1');
		$replys = $getReplyToArray->invokeArgs($mailform, []);
		$this->assertCount(1, $replys);
		$this->assertArrayHasKey('reply1@example.com', $replys);
		$this->assertEquals('reply1', $replys['reply1@example.com']);

		$mailform->setReplyTo('reply2@example.com', 'reply2');
		$replys = $getReplyToArray->invokeArgs($mailform, []);
		$this->assertCount(1, $replys);
		$this->assertArrayHasKey('reply2@example.com', $replys);
		$this->assertEquals('reply2', $replys['reply2@example.com']);
	}

	public function testSetReplyToByIndexAndGetReplyToArrayByIndex()
	{
		$getReplyToArrayByIndex = getMethod('MailForm', 'getReplyToArrayByIndex');
		$mailform = new MailForm();

		$mailform->setReplyToByIndex(1, 'reply1@example.com', 'reply1');
		$replys = $getReplyToArrayByIndex->invokeArgs($mailform, [1]);
		$this->assertCount(1, $replys);
		$this->assertArrayHasKey('reply1@example.com', $replys);
		$this->assertEquals('reply1', $replys['reply1@example.com']);

		$mailform->setReplyToByIndex(1, 'reply2@example.com', 'reply2');
		$replys = $getReplyToArrayByIndex->invokeArgs($mailform, [1]);
		$this->assertCount(1, $replys);
		$this->assertArrayHasKey('reply2@example.com', $replys);
		$this->assertEquals('reply2', $replys['reply2@example.com']);

		$mailform->setReplyToByIndex(2, 'reply3@example.com', 'reply3');
		$replys = $getReplyToArrayByIndex->invokeArgs($mailform, [2]);
		$this->assertCount(1, $replys);
		$this->assertArrayHasKey('reply3@example.com', $replys);
		$this->assertEquals('reply3', $replys['reply3@example.com']);
	}

	public function testSetMailBodyTemplateAndGetMailBodyTemplate()
	{
		$getMailBodyTemplate = getMethod('MailForm', 'getMailBodyTemplate');
		$mailform = new MailForm();
		$mailform->setMailBodyTemplate('body1');
		$body = $getMailBodyTemplate->invokeArgs($mailform, []);
		$this->assertEquals('body1', $body);
		
		$mailform->setMailBodyTemplate('body2');
		$body = $getMailBodyTemplate->invokeArgs($mailform, []);
		$this->assertEquals('body2', $body);
	}
	
	public function testSetMailBodyTemplateByIndexAndGetMailBodyTemplateByIndex()
	{
		$getMailBodyTemplateByIndex = getMethod('MailForm', 'getMailBodyTemplateByIndex');
		$mailform = new MailForm();
		$mailform->setMailBodyTemplateByIndex(1, 'body1');
		$body = $getMailBodyTemplateByIndex->invokeArgs($mailform, [1]);
		$this->assertEquals('body1', $body);
		
		$mailform->setMailBodyTemplateByIndex(1, 'body2');
		$body = $getMailBodyTemplateByIndex->invokeArgs($mailform, [1]);
		$this->assertEquals('body2', $body);
		
		$mailform->setMailBodyTemplateByIndex(2, 'body3');
		$body = $getMailBodyTemplateByIndex->invokeArgs($mailform, [2]);
		$this->assertEquals('body3', $body);
	}
	
	public function testSetMailSubjectTemplateAndGetMailSubjectTemplate()
	{
		$getMailSubjectTemplate = getMethod('MailForm', 'getMailSubjectTemplate');
		$mailform = new MailForm();
		$mailform->setMailSubjectTemplate('subject1');
		$subject = $getMailSubjectTemplate->invokeArgs($mailform, []);
		$this->assertEquals('subject1', $subject);
		
		$mailform->setMailSubjectTemplate('subject2');
		$subject = $getMailSubjectTemplate->invokeArgs($mailform, []);
		$this->assertEquals('subject2', $subject);
	}
	
	public function testSetMailSubjectTemplateByIndexAndGetMailSubjectTemplateByIndex()
	{
		$getMailSubjectTemplateByIndex = getMethod('MailForm', 'getMailSubjectTemplateByIndex');
		$mailform = new MailForm();
		$mailform->setMailSubjectTemplateByIndex(1, 'subject1');
		$subject = $getMailSubjectTemplateByIndex->invokeArgs($mailform, [1]);
		$this->assertEquals('subject1', $subject);
		
		$mailform->setMailSubjectTemplateByIndex(1, 'subject2');
		$subject = $getMailSubjectTemplateByIndex->invokeArgs($mailform, [1]);
		$this->assertEquals('subject2', $subject);
		
		$mailform->setMailSubjectTemplateByIndex(2, 'subject3');
		$subject = $getMailSubjectTemplateByIndex->invokeArgs($mailform, [2]);
		$this->assertEquals('subject3', $subject);
	}
	
	public function testSetAllowTempFilesAndgetAllowTempFiles()
	{
		$getAllowTempFiles = getMethod('MailForm', 'getAllowTempFiles');
		$mailform = new MailForm();
		$mailform->setAllowTempFiles(true);
		$flag = $getAllowTempFiles->invokeArgs($mailform, []);
		$this->assertEquals(true, $flag);
		
		$mailform->setAllowTempFiles(false);
		$flag = $getAllowTempFiles->invokeArgs($mailform, []);
		$this->assertEquals(false, $flag);
	}
	
	public function testSetAllowTempFilesByIndexAndgetAllowTempFilesByIndex()
	{
		$getAllowTempFilesByIndex = getMethod('MailForm', 'getAllowTempFilesByIndex');
		$mailform = new MailForm();
		$mailform->setAllowTempFilesByIndex(1, true);
		$flag = $getAllowTempFilesByIndex->invokeArgs($mailform, [1]);
		$this->assertEquals(true, $flag);
		
		$mailform->setAllowTempFilesByIndex(1, false);
		$flag = $getAllowTempFilesByIndex->invokeArgs($mailform, [1]);
		$this->assertEquals(false, $flag);
		
		$mailform->setAllowTempFilesByIndex(2, true);
		$flag = $getAllowTempFilesByIndex->invokeArgs($mailform, [2]);
		$this->assertEquals(true, $flag);
	}
	
	public function testSettingTextParse()
	{
		$mailform = new MailForm();
		$this->assertNull($mailform->settingTextParse("test", 100000));
		// YAMLはテストしない
		/*
		$text = <<<EOT
from: from@example.com
EOT;
		$this->assertEquals(['from' => 'from@example.com'], $mailform->settingTextParse($text, MailForm::MAILFORM_FILE_TYPE_YAML));
		*/
		
		$text = <<<EOT
{"from":"from@example.com"}
EOT;
		$this->assertEquals(['from' => 'from@example.com'], $mailform->settingTextParse($text, MailForm::MAILFORM_FILE_TYPE_JSON));
	}
	
	public function testSetFormConfig()
	{
		$getAllowTempFilesByIndex = getMethod('MailForm', 'getAllowTempFilesByIndex');
		$getToArrayByIndex = getMethod('MailForm', 'getToArrayByIndex');
		$getFromArrayByIndex = getMethod('MailForm', 'getFromArrayByIndex');
		$getMailSubjectTemplateByIndex = getMethod('MailForm', 'getMailSubjectTemplateByIndex');
		$getMailBodyTemplateByIndex = getMethod('MailForm', 'getMailBodyTemplateByIndex');
		$getReplyToArrayByIndex = getMethod('MailForm', 'getReplyToArrayByIndex');
		$getReturnPathArrayByIndex = getMethod('MailForm', 'getReturnPathArrayByIndex');
		$ErrorCode = getProperty('MailForm', 'ErrorCode');
		$mailform = new MailForm();
		$this->assertFalse($mailform->setFormConfig("", "", 100000));
		$this->assertEquals(MailForm::ERROR_CODE_INVALID_FORM_CONFIG, $ErrorCode->getValue($mailform));
		
		// フォーマットに合わなくても、大丈夫
		$text = <<<EOT
{"from":"from@example.com"}
EOT;
		$this->assertTrue($mailform->setFormConfig($text, "", MailForm::MAILFORM_FILE_TYPE_JSON));
		
		// YAMLはテストしない
		/*
				$text = <<<EOT
		from: from@example.com
		mailconf:
		  to-admin:
			test: test
		EOT;
				$this->assertTrue($mailform->setFormConfig($text, "", MailForm::MAILFORM_FILE_TYPE_YAML));
		*/
		// フォーマットのパターンテスト
		$text = <<<EOT
  {
	"mailconf": {
	  "to-admin": {
		"from": "to-admin-from@example.com",
		"to": [
		  "to-admin-test1 <to-admin-test1@example.com>",
		  "to-admin-test2@example.com"
		],
		"subject": "to-admin-subject",
		"reply-to": "to-admin-reply <to-admin-reply@example.com>",
		"return-path": "to-admin-return@example.com",
		"allow-temp-files": true
	  },
	  "to-user": {
		"from": "to-user-from@example.com",
		"to": "to-user-test1@example.com",
		"body-template-path": "to-user-mail-body-template.txt",
		"allow-temp-files": false
	  }
	}
  }
EOT;
		$this->assertTrue($mailform->setFormConfig($text, dirname(__FILE__).'/conf', MailForm::MAILFORM_FILE_TYPE_JSON));

		$froms = $getFromArrayByIndex->invokeArgs($mailform, ['to-admin']);
		$this->assertCount(1, $froms);
		$this->assertArrayHasKey('to-admin-from@example.com', $froms);
		$this->assertEquals('', $froms['to-admin-from@example.com']);
		
		$subject = $getMailSubjectTemplateByIndex->invokeArgs($mailform, ['to-admin']);
		$this->assertEquals('to-admin-subject', $subject);

		$replytos = $getReplyToArrayByIndex->invokeArgs($mailform, ['to-admin']);
		$this->assertCount(1, $replytos);
		$this->assertArrayHasKey('to-admin-reply@example.com', $replytos);
		$this->assertEquals('to-admin-reply', $replytos['to-admin-reply@example.com']);

		$returnpaths = $getReturnPathArrayByIndex->invokeArgs($mailform, ['to-admin']);
		$this->assertCount(1, $returnpaths);
		$this->assertArrayHasKey('to-admin-return@example.com', $returnpaths);
		$this->assertEquals('', $returnpaths['to-admin-return@example.com']);

		$tos = $getToArrayByIndex->invokeArgs($mailform, ['to-admin']);
		$this->assertCount(2, $tos);
		$this->assertArrayHasKey('to-admin-test1@example.com', $tos);
		$this->assertEquals('to-admin-test1', $tos['to-admin-test1@example.com']);
		$this->assertArrayHasKey('to-admin-test2@example.com', $tos);
		$this->assertEquals('', $tos['to-admin-test2@example.com']);
		
		$this->assertTrue($getAllowTempFilesByIndex->invokeArgs($mailform, ['to-admin']));
		
		$froms = $getFromArrayByIndex->invokeArgs($mailform, ['to-user']);
		$this->assertCount(1, $froms);
		$this->assertArrayHasKey('to-user-from@example.com', $froms);
		$this->assertEquals('', $froms['to-user-from@example.com']);
		
		$tos = $getToArrayByIndex->invokeArgs($mailform, ['to-user']);
		$this->assertCount(1, $tos);
		$this->assertArrayHasKey('to-user-test1@example.com', $tos);
		$this->assertEquals('', $tos['to-user-test1@example.com']);
		
		$body = $getMailBodyTemplateByIndex->invokeArgs($mailform, ['to-user']);
		$this->assertEquals('to-user-mail-body-template', $body);
		$this->assertFalse($getAllowTempFilesByIndex->invokeArgs($mailform, ['to-user']));
	}
	
	public function testLoadFormConfig()
	{
		$mailform = $this->createCompatibleMock('MailForm', ['setFormConfig']);
		
		$filepath = dirname(__FILE__).'/conf/mailform_config.json';
		
		$expectation = $mailform->expects($this->once())
		->method('setFormConfig')
		->with(file_get_contents($filepath), 'templateDirectoryPath', MailForm::MAILFORM_FILE_TYPE_JSON);
		
		$this->setCompatibleReturnValue($expectation, true);
		
		$mailform->loadFormConfig($filepath, 'templateDirectoryPath', MailForm::MAILFORM_FILE_TYPE_JSON);
	}
	
	public function testParseMailAddressList()
	{
		$parseMailAddressList = getMethod('MailForm', 'parseMailAddressList');
		$mailform = new MailForm();
		
		$this->assertEquals(['test@example.com', ''], $parseMailAddressList->invokeArgs($mailform, ['  test@example.com  ']));
		$this->assertEquals(['test@example.com', ''], $parseMailAddressList->invokeArgs($mailform, ['<  test@example.com>']));
		$this->assertEquals(['test@example.com', 'test'], $parseMailAddressList->invokeArgs($mailform, ['  test < test@example.com >']));
		$this->assertEquals(['test < test@example.com', ''], $parseMailAddressList->invokeArgs($mailform, ['  test < test@example.com ']));
	}
	
	public function testGetStatusFromPostParam()
	{
		$getStatusFromPostParam = getMethod('MailForm', 'getStatusFromPostParam');
		$mailform = new MailForm();
		
		$this->assertEquals(MailForm::STATUS_NONE, $getStatusFromPostParam->invokeArgs($mailform, [[]]));
		$this->assertEquals(MailForm::STATUS_CONFIRMATION, $getStatusFromPostParam->invokeArgs($mailform, [['mailform-confirm-submit' => '1']]));
		$this->assertEquals(MailForm::STATUS_COMPLETE, $getStatusFromPostParam->invokeArgs($mailform, [['mailform-complete-submit' => '1']]));
		$this->assertEquals(MailForm::STATUS_INPUT, $getStatusFromPostParam->invokeArgs($mailform, [['mailform-input-submit' => '1']]));
	}
	
	public function testReplaceGreekingText()
	{
		mb_internal_encoding("UTF-8");
		$mailform = new MailForm();
		
		$this->assertEquals('(株)', $mailform->replaceGreekingText('㈱'));
		mb_internal_encoding("UTF-7");
		$this->assertEquals('㈱', $mailform->replaceGreekingText('㈱'));
	}
	
	public function testSetRequestParameter()
	{
		$Status = getProperty('MailForm', 'Status');
		$RequestParam = getProperty('MailForm', 'RequestParam');
		mb_internal_encoding("UTF-8");
		$ErrorCode = getProperty('MailForm', 'ErrorCode');
		$mailform = new MailForm();
		$mailform->setRequestParameter([]);
		$this->assertEquals(MailForm::ERROR_CODE_INVALID_FORM_CONFIG, $ErrorCode->getValue($mailform));
		
		$filepath = dirname(__FILE__).'/conf/mailform_config_for_RequestParameter.json';
		$mailform->loadFormConfig($filepath, 'templateDirectoryPath', MailForm::MAILFORM_FILE_TYPE_JSON);
		
		$param = ['mailform-confirm-submit' => '1', 'username' => '㈱テスト', 'other'=>'other'];
		$mailform->setRequestParameter($param);
		
		$requestParam = $RequestParam->getValue($mailform);
		$this->assertArrayHasKey('username', $requestParam);
		$this->assertEquals('(株)テスト', $requestParam['username']);
		$this->assertArrayNotHasKey('kana', $requestParam);
		$this->assertArrayNotHasKey('other', $requestParam);
		
		$status = $Status->getValue($mailform);
		$this->assertEquals(MailForm::STATUS_CONFIRMATION, $status);
	}
	
	public function testSetTemplatePage()
	{
		$setTemplatePage = getMethod('MailForm', 'setTemplatePage');
		$mailform = new MailForm();
		$filepath = dirname(__FILE__).'/conf/include_test.txt';
		ob_start();
		$setTemplatePage->invokeArgs($mailform, [$filepath]);
		$output = ob_get_clean();
		$this->assertEquals('include_test:'.$filepath, $output);
		
		$TemplateURL = getProperty('MailForm', 'TemplateURL');
		$this->assertEquals($filepath, $TemplateURL->getValue($mailform));
		
	}
	
	public function testGetTemplateURL()
	{
		$setTemplatePage = getMethod('MailForm', 'setTemplatePage');
		$mailform = new MailForm();
		$filepath = dirname(__FILE__).'/conf/include_test.txt';
		$setTemplatePage->invokeArgs($mailform, [$filepath]);
		$this->assertEquals($filepath, $mailform->getTemplateURL());
	}
	
	public function testShowInputPage()
	{
		$showInputPage = getMethod('MailForm', 'showInputPage');
		$url = 'STATUS_INPUT';
		
		$mailform = $this->createCompatibleMock('MailForm', ['setTemplatePage']);
		$mailform->expects($this->once())
		->method('setTemplatePage')
		->with($url);
		
		$mailform->InputURL = $url;
		
		$factory = new InnerSessionFactory();
		$mailform->setSessionFactory($factory);
		
		$showInputPage->invokeArgs($mailform, []);
		
		$session = $factory->getInstance();
		$this->assertEquals(MailForm::STATUS_INPUT, $session->getParamValue('mailform_status'));
	}
	
	public function testShowConfirmPage()
	{
		$showConfirmPage = getMethod('MailForm', 'showConfirmPage');
		$url = 'STATUS_CONFIRMATION';
		
		$mailform = $this->createCompatibleMock('MailForm', ['setTemplatePage']);
		
		$mailform->expects($this->once())
		->method('setTemplatePage')
		->with($url);
		
		$mailform->ConfirmURL = $url;
		
		$factory = new InnerSessionFactory();
		$mailform->setSessionFactory($factory);
		
		$showConfirmPage->invokeArgs($mailform, []);
		
		$session = $factory->getInstance();
		$this->assertEquals(MailForm::STATUS_CONFIRMATION, $session->getParamValue('mailform_status'));
	}
	
	public function testShowCompletePage()
	{
		$showCompletePage = getMethod('MailForm', 'showCompletePage');
		$url = 'STATUS_COMPLETED';
		
		$mailform = $this->createCompatibleMock('MailForm', ['setTemplatePage']);
		
		$mailform->expects($this->once())
		->method('setTemplatePage')
		->with($url);
		
		$mailform->CompleteURL = $url;
		
		$factory = new InnerSessionFactory();
		$mailform->setSessionFactory($factory);
		
		$showCompletePage->invokeArgs($mailform, []);
		
		$session = $factory->getInstance();
		$this->assertEquals(MailForm::STATUS_COMPLETED, $session->getParamValue('mailform_status'));
	}
	
	public function testShowErrorPage()
	{
		$showErrorPage = getMethod('MailForm', 'showErrorPage');
		$url = 'ERROR';
		
		$mailform = $this->createCompatibleMock('MailForm', ['setTemplatePage']);
		
		$mailform->expects($this->once())
		->method('setTemplatePage')
		->with($url);
		
		$mailform->ErrorURL = $url;
		
		$factory = new InnerSessionFactory();
		$mailform->setSessionFactory($factory);
		$session = $factory->getInstance();
		$session->setParam('mailform_status', MailForm::STATUS_COMPLETED);
		
		$showErrorPage->invokeArgs($mailform, []);
		
		$this->assertEquals(MailForm::STATUS_COMPLETED, $session->getParamValue('mailform_status'));
	}
	
	public function testIsCorrectAccess()
	{
		$Status = getProperty('MailForm', 'Status');
		$RequestParam = getProperty('MailForm', 'RequestParam');
		$isCorrectAccess = getMethod('MailForm', 'isCorrectAccess');
		$mailform = new MailForm();
		$mailform->loadFormConfig(dirname(__FILE__).'/conf/mailform_config.json', dirname(__FILE__).'/conf/', MailForm::MAILFORM_FILE_TYPE_JSON);
		$factory = new InnerSessionFactory();
		$mailform->setSessionFactory($factory);
		$session = $factory->getInstance();
		
		// セッション無し
		$session->setParam('item_values', null);
		$session->setParam('mailform_status', null);
		$Status->setValue($mailform, MailForm::STATUS_NONE);
		$this->assertTrue($isCorrectAccess->invokeArgs($mailform, []));
		
		$Status->setValue($mailform, MailForm::STATUS_INPUT);
		$this->assertTrue($isCorrectAccess->invokeArgs($mailform, []));
		
		$Status->setValue($mailform, MailForm::STATUS_CONFIRMATION);
		$this->assertFalse($isCorrectAccess->invokeArgs($mailform, []));
		
		$Status->setValue($mailform, MailForm::STATUS_COMPLETE);
		$this->assertFalse($isCorrectAccess->invokeArgs($mailform, []));
		
		$Status->setValue($mailform, MailForm::STATUS_COMPLETED);
		$this->assertTrue($isCorrectAccess->invokeArgs($mailform, []));
		
		// セッションステータス STATUS_INPUT
		$session->setParam('item_values', null);
		$session->setParam('mailform_status', MailForm::STATUS_INPUT);
		$Status->setValue($mailform, MailForm::STATUS_NONE);
		$this->assertTrue($isCorrectAccess->invokeArgs($mailform, []));
		
		$Status->setValue($mailform, MailForm::STATUS_INPUT);
		$this->assertTrue($isCorrectAccess->invokeArgs($mailform, []));
		
		$Status->setValue($mailform, MailForm::STATUS_CONFIRMATION);
		$RequestParam->setValue($mailform, null);
		$this->assertFalse($isCorrectAccess->invokeArgs($mailform, []));
		$mailform->setRequestParameter(['aaa' => 'test']);
		$Status->setValue($mailform, MailForm::STATUS_CONFIRMATION);
		$this->assertTrue($isCorrectAccess->invokeArgs($mailform, []));
		
		$Status->setValue($mailform, MailForm::STATUS_COMPLETE);
		$this->assertFalse($isCorrectAccess->invokeArgs($mailform, []));
		
		$Status->setValue($mailform, MailForm::STATUS_COMPLETED);
		$this->assertTrue($isCorrectAccess->invokeArgs($mailform, []));
		
		// セッションステータス STATUS_CONFIRMATION
		$session->setParam('item_values', null);
		$session->setParam('mailform_status', MailForm::STATUS_CONFIRMATION);
		$Status->setValue($mailform, MailForm::STATUS_NONE);
		$this->assertTrue($isCorrectAccess->invokeArgs($mailform, []));
		
		$Status->setValue($mailform, MailForm::STATUS_INPUT);
		$this->assertTrue($isCorrectAccess->invokeArgs($mailform, []));
		
		$RequestParam->setValue($mailform, null);
		$Status->setValue($mailform, MailForm::STATUS_CONFIRMATION);
		$this->assertFalse($isCorrectAccess->invokeArgs($mailform, []));
		$mailform->setRequestParameter(['aaa' => 'test']);
		$Status->setValue($mailform, MailForm::STATUS_CONFIRMATION);
		$this->assertTrue($isCorrectAccess->invokeArgs($mailform, []));
		
		$Status->setValue($mailform, MailForm::STATUS_COMPLETE);
		$RequestParam->setValue($mailform, null);
		$this->assertFalse($isCorrectAccess->invokeArgs($mailform, []));
		
		$mailform->setRequestParameter(['aaa' => 'test']);
		$Status->setValue($mailform, MailForm::STATUS_COMPLETE);
		$this->assertFalse($isCorrectAccess->invokeArgs($mailform, []));
		$session->setParam('item_values', []);
		$this->assertTrue($isCorrectAccess->invokeArgs($mailform, []));
		
		$Status->setValue($mailform, MailForm::STATUS_COMPLETED);
		$this->assertTrue($isCorrectAccess->invokeArgs($mailform, []));
		
		// セッションステータス STATUS_COMPLETED
		$session->setParam('mailform_status', MailForm::STATUS_COMPLETED);
		$Status->setValue($mailform, MailForm::STATUS_NONE);
		$this->assertTrue($isCorrectAccess->invokeArgs($mailform, []));
		$Status->setValue($mailform, MailForm::STATUS_INPUT);
		$this->assertTrue($isCorrectAccess->invokeArgs($mailform, []));
		
		$Status->setValue($mailform, MailForm::STATUS_CONFIRMATION);
		$this->assertFalse($isCorrectAccess->invokeArgs($mailform, []));
		
		$Status->setValue($mailform, MailForm::STATUS_COMPLETE);
		$this->assertFalse($isCorrectAccess->invokeArgs($mailform, []));
		
		$Status->setValue($mailform, MailForm::STATUS_COMPLETED);
		$this->assertTrue($isCorrectAccess->invokeArgs($mailform, []));
		
	}
	
	public function testSetParamToSession()
	{
	
		$setParamToSession = getMethod('MailForm', 'setParamToSession');
		$ErrorMessages = getProperty('MailForm', 'ErrorMessages');
		$mailform = new MailForm();
		$mailform->loadFormConfig(dirname(__FILE__).'/conf/mailform_config.json', dirname(__FILE__).'/conf/', MailForm::MAILFORM_FILE_TYPE_JSON);
		$factory = new InnerSessionFactory();
		$mailform->setSessionFactory($factory);
		$session = $factory->getInstance();
		
		$requestParameter = ['kind' => 1];
		$errorMessages = ['username', '「username」は必ずご入力ください'];
		$ErrorMessages->setValue($mailform, $errorMessages);
		$mailform->setRequestParameter($requestParameter);
		
		$setParamToSession->invokeArgs($mailform, []);
		
		$this->assertEquals($errorMessages, $session->getParamValue('error_messages'));
		$this->assertEquals($requestParameter, $session->getParamValue('item_values'));
		
		$session->setParam('item_values', ['kind' => 2, 'temp' => 'test.pdf']);
		$ErrorMessages->setValue($mailform, $errorMessages);
		$mailform->setRequestParameter($requestParameter);
		
		$setParamToSession->invokeArgs($mailform, []);
		
		$this->assertEquals($errorMessages, $session->getParamValue('error_messages'));
		$sessionItemValues = $session->getParamValue('item_values');
		$this->assertEquals('test.pdf', $sessionItemValues['temp']);
	
	}
	
	public function testLoadParamFromSession()
	{
		$ErrorMessages = getProperty('MailForm', 'ErrorMessages');
		$RequestParam = getProperty('MailForm', 'RequestParam');
		$loadParamFromSession = getMethod('MailForm', 'loadParamFromSession');
		$mailform = new MailForm();
		$factory = new InnerSessionFactory();
		$mailform->setSessionFactory($factory);
		$session = $factory->getInstance();
		
		$requestParam = ['kind' => 2, 'temp' => 'test.pdf'];
		$errorMessages = ['username', '「username」は必ずご入力ください'];
		$session->setParam('item_values', $requestParam);
		$session->setParam('error_messages', $errorMessages);
		
		$loadParamFromSession->invokeArgs($mailform, []);
		
		$this->assertEquals($requestParam, $RequestParam->getValue($mailform));
		$this->assertEquals($errorMessages, $ErrorMessages->getValue($mailform));
	}
	
	public function testSetTempDirPathToSession()
	{
		$setTempDirPathToSession = getMethod('MailForm', 'setTempDirPathToSession');
		$TmpDirectoryPath = getProperty('MailForm', 'TmpDirectoryPath');
	
		$mailform = new MailForm();
		$factory = new InnerSessionFactory();
		$mailform->setSessionFactory($factory);
		$session = $factory->getInstance();
		
		$date = new DateTime();
		$time = microtime();
		$setTempDirPathToSession->invokeArgs($mailform, []);
		
		$TmpDirectoryPath = $TmpDirectoryPath->getValue($mailform).$date->format('YmdHis').substr(explode('.', explode(' ', $time)[0])[1], 0, 3).'/';
		$this->assertEquals($TmpDirectoryPath, $session->getParamValue('mailform_temp_dir_path'));
	}
	
	public function testGetTempDirPathFromSession()
	{
		$_SESSION=[];
		$getTempDirPathFromSession = getMethod('MailForm', 'getTempDirPathFromSession');
		$TmpDirectoryPath = getProperty('MailForm', 'TmpDirectoryPath');
		
		$mailform = $this->createCompatibleMock('MailForm', ['setTempDirPathToSession']);
		$mailform->expects($this->once())
		->method('setTempDirPathToSession');
		
		$factory = new InnerSessionFactory();
		$mailform->setSessionFactory($factory);
		$session = $factory->getInstance();
		
		$getTempDirPathFromSession->invokeArgs($mailform, []);
		
		$mailform = new MailForm();
		$mailform->setSessionFactory($factory);
		$session->setParam('mailform_temp_dir_path', 'session_mailform_temp_dir_path');
		$this->assertEquals('session_mailform_temp_dir_path', $getTempDirPathFromSession->invokeArgs($mailform, []));
	}
	
	public function testMoveUploadFile()
	{
		$moveUploadFile = getMethod('MailForm', 'moveUploadFile');
		$mailform = new MailForm();
		
		$factory = new InnerSessionFactory();
		$mailform->setSessionFactory($factory);
		
		$FormConfig = getProperty('MailForm', 'FormConfig');
		$FormConfig->setValue($mailform, [
			'items' => [
				'test_file' => ['value' => 'file']
			]
		]);
		
		$_FILES = [];
		$moveUploadFile->invokeArgs($mailform, []);
		$this->assertFalse(isset($mailform->RequestParam['test_file']));
	}
	
	public function testUnsetSession()
	{
		$_SESSION=[];
		$getTempDirPathFromSession = getMethod('MailForm', 'getTempDirPathFromSession');
		$unsetSession = getMethod('MailForm', 'unsetSession');
		$TmpDirectoryPath = getProperty('MailForm', 'TmpDirectoryPath');
		
		$mailform = new MailForm();
		$mailform->TmpDirectoryPath = dirname(__FILE__).'/test_tmp/';
		$unsetSession->invokeArgs($mailform, []);
		$this->assertFalse(isset($_SESSION['item_values']));
		$this->assertFalse(isset($_SESSION['mailform_temp_dir_path']));
		
		$filebasepath = $getTempDirPathFromSession->invokeArgs($mailform, []);
		if (!is_dir($mailform->TmpDirectoryPath)) {
			mkdir($mailform->TmpDirectoryPath, 0700, true);
		}
		if (!is_dir($filebasepath)) {
			mkdir($filebasepath, 0700, true);
		}
		
		$this->assertTrue(file_exists($filebasepath));
		$unsetSession->invokeArgs($mailform, []);
		system("rm -rf {$mailform->TmpDirectoryPath}");
	}
	
	public function testExecute()
	{
		$_SERVER['SCRIPT_NAME'] = 'SCRIPT_NAME';
		$ErrorCode = getProperty('MailForm', 'ErrorCode');
		$ErrorMessages = getProperty('MailForm', 'ErrorMessages');
		$RequestParam = getProperty('MailForm', 'RequestParam');
		$FormConfig = getProperty('MailForm', 'FormConfig');
		$Status = getProperty('MailForm', 'Status');
		
		// IncludeTemplateURL=true
		$mailform = $this->createCompatibleMock('MailForm', ['main']);
		$mailform->expects($this->once())
		->method('main');
		
		$mailform->IncludeTemplateURL = true;
		$mailform->execute();
		
		// IncludeTemplateURL=false
		// エラーページ表示の場合
		$mailform = $this->createCompatibleMock('MailForm', ['loadParamFromSession', 'main']);
		
		$mailform->expects($this->once())
		->method('loadParamFromSession');
		$mailform->expects($this->never())
		->method('main');
		
		$mailform->IncludeTemplateURL = false;
		$mailform->ErrorURL = $_SERVER['SCRIPT_NAME'];
		$mailform->execute();
		
		// IncludeTemplateURL=false
		// 完了画面表示の場合
		$mailform = $this->createCompatibleMock('MailForm', ['showCompletePage', 'main']);
		
		$mailform->expects($this->once())
		->method('showCompletePage');
		$mailform->expects($this->never())
		->method('main');
		
		$mailform->IncludeTemplateURL = false;
		$mailform->CompleteURL = $_SERVER['SCRIPT_NAME'];
		$Status->setValue($mailform, MailForm::STATUS_COMPLETED);
		$mailform->execute();
		
		// IncludeTemplateURL=false
		// 完了画面表示の場合
		$mailform = $this->createCompatibleMock('MailForm', ['showCompletePage', 'main']);
		$mailform->expects($this->once())
		->method('showCompletePage');
		$mailform->expects($this->never())
		->method('main');
		
		$mailform->IncludeTemplateURL = false;
		$mailform->CompleteURL = $_SERVER['SCRIPT_NAME'];
		$Status->setValue($mailform, MailForm::STATUS_COMPLETED);
		$mailform->execute();
		
		// IncludeTemplateURL=false
		// TemplateURLが変わった場合
		$mailform = $this->createCompatibleMock('MailForm', ['main', 'getTemplateURL', 'redirect']);
		$mailform->expects($this->once())
		->method('main');
		$expectation = $mailform->expects($this->exactly(2))
		->method('getTemplateURL');
		$this->setCompatibleReturnValue($expectation, 'other_getTemplateURL');
		$mailform->expects($this->once())
		->method('redirect')
		->with('other_getTemplateURL');
		
		$mailform->IncludeTemplateURL = false;
		$Status->setValue($mailform, MailForm::STATUS_COMPLETED);
		$mailform->execute();
		
		// IncludeTemplateURL=false
		// TemplateURLが変わらなかった場合
		/*
		$mailform = $this->createCompatibleMock('MailForm', ['main', 'getTemplateURL', 'redirect']);
		$mailform->IncludeTemplateURL = false;
		$mailform->expects($this->once())
		->method('main');
		$expectatio = $mailform->expects($this->once())
		->method('getTemplateURL');
		$this->setCompatibleReturnValue($expectation, $_SERVER['SCRIPT_NAME']);
		$mailform->expects($this->never())
		->method('redirect');
		$Status->setValue($mailform, MailForm::STATUS_COMPLETED);
		$mailform->execute();*/
	}
		
	public function testMain()
	{
		$_SERVER['REQUEST_URI'] = 'REQUEST_URI';
		$ErrorCode = getProperty('MailForm', 'ErrorCode');
		$ErrorMessages = getProperty('MailForm', 'ErrorMessages');
		$main = getMethod('MailForm', 'main');
		$RequestParam = getProperty('MailForm', 'RequestParam');
		$FormConfig = getProperty('MailForm', 'FormConfig');
		$Status = getProperty('MailForm', 'Status');
		
		// 設定エラー
		$mailform = $this->createCompatibleMock('MailForm', ['showErrorPage']);
		$mailform->expects($this->once())
		->method('showErrorPage');
		
		$main->invokeArgs($mailform, []);
		$this->assertEquals(MailForm::ERROR_CODE_INVALID_FORM_CONFIG, $ErrorCode->getValue($mailform));
		$this->assertEquals('システム設定エラーが発生しました', $ErrorMessages->getValue($mailform)['base']);
		
		// 不正アクセス
		$mailform = $this->createCompatibleMock('MailForm', ['isCorrectAccess', 'unsetSession', 'redirect']);
		$expectation = $mailform->expects($this->once())
		->method('isCorrectAccess');
		$this->setCompatibleReturnValue($expectation, false);
		$mailform->expects($this->once())
		->method('unsetSession');
		$mailform->expects($this->once())
		->method('redirect')
		->with($_SERVER['REQUEST_URI']);
		
		$FormConfig->setValue($mailform, ['items' => []]);
		$main->invokeArgs($mailform, []);
		
		// 入力ページ表示
		$mailform = $this->createCompatibleMock('MailForm', ['isCorrectAccess', 'unsetSession', 'showInputPage']);
		$expectation = $mailform->expects($this->once())
		->method('isCorrectAccess');
		$this->setCompatibleReturnValue($expectation, true);
		$mailform->expects($this->once())
		->method('unsetSession');
		$mailform->expects($this->once())
		->method('showInputPage');
		
		$FormConfig->setValue($mailform, ['items' => []]);
		$Status->setValue($mailform, MailForm::STATUS_NONE);
		$main->invokeArgs($mailform, []);
		
		$mailform = $this->createCompatibleMock('MailForm', ['isCorrectAccess', 'showInputPage']);
		$expectation = $mailform->expects($this->once())
		->method('isCorrectAccess');
		$this->setCompatibleReturnValue($expectation, true);
		$mailform->expects($this->once())
		->method('showInputPage');
		
		$FormConfig->setValue($mailform, ['items' => []]);
		$Status->setValue($mailform, MailForm::STATUS_INPUT);
		$main->invokeArgs($mailform, []);
		
		// 確認画面表示
		$mailform = $this->createCompatibleMock('MailForm', ['isCorrectAccess', 'moveUploadFile', 'checkRequestParams', 'setParamToSession', 'showConfirmPage']);
		$expectation = $mailform->expects($this->once())
		->method('isCorrectAccess');
		$this->setCompatibleReturnValue($expectation, true);
		$mailform->expects($this->once())
		->method('moveUploadFile');
		$mailform->expects($this->once())
		->method('checkRequestParams');
		$mailform->expects($this->once())
		->method('setParamToSession');
		$mailform->expects($this->once())
		->method('showConfirmPage');
		
		$FormConfig->setValue($mailform, ['items' => []]);
		$Status->setValue($mailform, MailForm::STATUS_CONFIRMATION);
		$main->invokeArgs($mailform, []);
		
		// 完了画面表示
		$mailform = $this->createCompatibleMock('MailForm', ['isCorrectAccess', 'loadParamFromSession', 'checkRequestParams', 'sendMail', 'showCompletePage']);
		$expectation = $mailform->expects($this->once())
		->method('isCorrectAccess');
		$this->setCompatibleReturnValue($expectation, true);
		$mailform->expects($this->once())
		->method('loadParamFromSession');
		$expectation = $mailform->expects($this->once())
		->method('checkRequestParams');
		$this->setCompatibleReturnValue($expectation, true);
		$expectation = $mailform->expects($this->once())
		->method('sendMail');
		$this->setCompatibleReturnValue($expectation, true);
		$mailform->expects($this->once())
		->method('showCompletePage');
		
		$FormConfig->setValue($mailform, ['items' => []]);
		$Status->setValue($mailform, MailForm::STATUS_COMPLETE);
		$main->invokeArgs($mailform, []);
		
		// エラー画面表示、checkRequestParamsでエラー
		$mailform = $this->createCompatibleMock('MailForm', ['isCorrectAccess', 'loadParamFromSession', 'sendMail', 'setParamToSession', 'showErrorPage']);
		$expectation = $mailform->expects($this->once())
		->method('isCorrectAccess');
		$this->setCompatibleReturnValue($expectation, true);
		$mailform->expects($this->once())
		->method('loadParamFromSession');
		$expectation = $mailform->expects($this->never())
		->method('sendMail');
		$this->setCompatibleReturnValue($expectation, true);
		$mailform->expects($this->once())
		->method('setParamToSession');
		$mailform->expects($this->once())
		->method('showErrorPage');
		
		$FormConfig->setValue($mailform, ['items' => ['key' => []]]);
		$Status->setValue($mailform, MailForm::STATUS_COMPLETE);
		$main->invokeArgs($mailform, []);
	}
	
	public function testGetValue()
	{
		$RequestParam = getProperty('MailForm', 'RequestParam');
		$mailform = new MailForm();
		
		$RequestParam->setValue($mailform, []);
		$this->assertEquals('', $mailform->getValue('key'));
		
		$RequestParam->setValue($mailform, ['key' => 'value']);
		$this->assertEquals('value', $mailform->getValue('key'));
	}
	
	public function testGetSelectValues()
	{
		$FormConfig = getProperty('MailForm', 'FormConfig');
		$mailform = new MailForm();
		
		$FormConfig->setValue($mailform, []);
		$this->assertEquals('', $mailform->getSelectValues('key'));
		
		$selectValues = ['1' => 'option1', '2' => 'option2'];
		$FormConfig->setValue($mailform, ['items' => ['key' => ['selectvalues' => $selectValues]]]);
		$this->assertEquals($selectValues, $mailform->getSelectValues('key'));
	}
	
	public function testGetSelectValue()
	{
		$FormConfig = getProperty('MailForm', 'FormConfig');
		$mailform = new MailForm();
		
		$FormConfig->setValue($mailform, []);
		$this->assertEquals('', $mailform->getSelectValue('key', '1'));
		
		$selectValues = ['1' => 'option1', '2' => 'option2'];
		$FormConfig->setValue($mailform, ['items' => ['key' => ['selectvalues' => $selectValues]]]);
		$this->assertEquals('option1', $mailform->getSelectValue('key', '1'));
	}
	
	public function testGetSelectOptionHtml()
	{
		$FormConfig = getProperty('MailForm', 'FormConfig');
		$mailform = new MailForm();
		
		$FormConfig->setValue($mailform, []);
		$this->assertEquals('', $mailform->getSelectOptionHtml('key'));
		
		$selectValues = ['1' => 'option1', '2' => 'option2'];
		$FormConfig->setValue($mailform, ['items' => ['key' => ['selectvalues' => $selectValues]]]);
		$this->assertEquals("<option value=\"1\">option1</option>\n<option value=\"2\">option2</option>\n", $mailform->getSelectOptionHtml('key'));
		
		$mailform = $this->createCompatibleMock('MailForm', ['getValue']);
		$expectation = $mailform->expects($this->once())
		->method('getValue')
		->with('key');
		$this->setCompatibleReturnValue($expectation, '2');
		
		$FormConfig->setValue($mailform, ['items' => ['key' => ['selectvalues' => $selectValues]]]);
		$this->assertEquals("<option value=\"1\">option1</option>\n<option value=\"2\" selected=\"selected\">option2</option>\n", $mailform->getSelectOptionHtml('key'));
	}
	
	public function testIsExistKeyInSelectValues()
	{
		$FormConfig = getProperty('MailForm', 'FormConfig');
		$mailform = new MailForm();
		
		$selectValues = ['1' => 'option1', '2' => 'option2'];
		$FormConfig->setValue($mailform, ['items' => ['key' => ['selectvalues' => $selectValues]]]);
		
		$this->assertFalse($mailform->isExistKeyInSelectValues('key', '10'));
		$this->assertTrue($mailform->isExistKeyInSelectValues('key', '1'));
	}
	
	public function testGetSelectedValue()
	{
		$FormConfig = getProperty('MailForm', 'FormConfig');
		$mailform = new MailForm();
		$this->assertEquals('', $mailform->getSelectedValue('key'));
		
		$mailform = $this->createCompatibleMock('MailForm', ['getValue']);
		$expectation = $mailform->expects($this->once())
		->method('getValue')
		->with('key');
		$this->setCompatibleReturnValue($expectation, '2');
		$selectValues = ['empty' => 'empty', '1' => 'option1', '2' => 'option2'];
		$FormConfig->setValue($mailform, ['items' => ['key' => ['selectvalues' => $selectValues]]]);
		$this->assertEquals('option2', $mailform->getSelectedValue('key'));
	}
	
	public function testGetMultiSelectedValue()
	{
		$FormConfig = getProperty('MailForm', 'FormConfig');
		$mailform = new MailForm();
	
		$this->assertEquals('', $mailform->getMultiSelectedValue('key'));
		$selectValues = ['empty' => 'empty', '1' => 'option1', '2' => 'option2'];
		
		$FormConfig->setValue($mailform, ['items' => ['key' => ['selectvalues' => $selectValues]]]);
		$this->assertEquals('', $mailform->getMultiSelectedValue('key'));
		
		$mailform = $this->createCompatibleMock('MailForm', ['getValue']);
		$expectation = $mailform->expects($this->once())
		->method('getValue')
		->with('key');
		$this->setCompatibleReturnValue($expectation, ['1','2']);
		
		$FormConfig->setValue($mailform, ['items' => ['key' => ['selectvalues' => $selectValues]]]);
		$this->assertEquals('option1／option2', $mailform->getMultiSelectedValue('key'));
	}
	
	public function testIsSelectedValue()
	{
		$FormConfig = getProperty('MailForm', 'FormConfig');
		$mailform = new MailForm();
		
		$this->assertTrue($mailform->isSelectedValue('key', '1', true));
		$this->assertFalse($mailform->isSelectedValue('key', '1'));
		
		$mailform = $this->createCompatibleMock('MailForm', ['getValue']);
		$expectation = $this->expectsAny($mailform)
		->method('getValue')
		->with('key');
		$this->setCompatibleReturnValue($expectation, '1');
		
		$selectValues = ['1' => 'option1', '2' => 'option2', '3' => 'option3'];
		$FormConfig->setValue($mailform, ['items' => ['key' => ['selectvalues' => $selectValues]]]);
		$this->assertTrue($mailform->isSelectedValue('key', '1'));
		$this->assertFalse($mailform->isSelectedValue('key', '2'));
		
		$mailform = $this->createCompatibleMock('MailForm', ['getValue']);
		$expectation = $this->expectsAny($mailform)
		->method('getValue')
		->with('key');
		$this->setCompatibleReturnValue($expectation, ['1', '2']);
		$this->assertTrue($mailform->isSelectedValue('key', '1'));
		$this->assertFalse($mailform->isSelectedValue('key', '3'));
		
	}
	
	public function testIsPlusNumber()
	{
		$isPlusNumber = getMethod('MailForm', 'isPlusNumber');
		$mailform = new MailForm();
		$this->assertTrue($isPlusNumber->invokeArgs($mailform, [1]));
		$this->assertTrue($isPlusNumber->invokeArgs($mailform, [0]));
		$this->assertTrue($isPlusNumber->invokeArgs($mailform, ['5']));
		$this->assertTrue($isPlusNumber->invokeArgs($mailform, [10]));
		$this->assertTrue($isPlusNumber->invokeArgs($mailform, [11111]));
		$this->assertFalse($isPlusNumber->invokeArgs($mailform, [-1]));
		$this->assertFalse($isPlusNumber->invokeArgs($mailform, ['a']));
	}
	
	public function testIsEmailAddress()
	{
		$isEmailAddress = getMethod('MailForm', 'isEmailAddress');
		$mailform = new MailForm();
		$this->assertTrue($isEmailAddress->invokeArgs($mailform, ['test@samle.com']));
		$this->assertTrue($isEmailAddress->invokeArgs($mailform, ['test@samle.com']));
		$this->assertFalse($isEmailAddress->invokeArgs($mailform, ['test@samle']));
		$this->assertFalse($isEmailAddress->invokeArgs($mailform, ['']));
	}
	
	public function testIsKana()
	{
		$isKana = getMethod('MailForm', 'isKana');
		$mailform = new MailForm();
		$this->assertFalse($isKana->invokeArgs($mailform, ['てすと']));
		$this->assertTrue($isKana->invokeArgs($mailform, ['テスト']));
		$this->assertFalse($isKana->invokeArgs($mailform, ['漢字']));
		$this->assertFalse($isKana->invokeArgs($mailform, ['123']));
		$this->assertFalse($isKana->invokeArgs($mailform, ['abc']));
	}
	
	public function testIsYomi()
	{
		$isYomi = getMethod('MailForm', 'isYomi');
		$mailform = new MailForm();
		$this->assertTrue($isYomi->invokeArgs($mailform, ['てすと']));
		$this->assertFalse($isYomi->invokeArgs($mailform, ['テスト']));
		$this->assertFalse($isYomi->invokeArgs($mailform, ['漢字']));
		$this->assertFalse($isYomi->invokeArgs($mailform, ['123']));
		$this->assertFalse($isYomi->invokeArgs($mailform, ['abc']));
	}
	
	public function testIsTel()
	{
		$isTel = getMethod('MailForm', 'isTel');
		$mailform = new MailForm();
		$this->assertTrue($isTel->invokeArgs($mailform, ['+81-1234-1234']));
		$this->assertTrue($isTel->invokeArgs($mailform, ['03-1234-1234']));
		$this->assertTrue($isTel->invokeArgs($mailform, ['123456789']));
		$this->assertTrue($isTel->invokeArgs($mailform, ['------']));
		$this->assertFalse($isTel->invokeArgs($mailform, ['abcde']));
		$this->assertFalse($isTel->invokeArgs($mailform, ['てすと']));
		$this->assertFalse($isTel->invokeArgs($mailform, ['03-1234-1234abc']));
		$this->assertFalse($isTel->invokeArgs($mailform, ['03-1234-1234\n']));
	}
	
	public function testConvToTwoByte()
	{
		$convToTwoByte = getMethod('MailForm', 'convToTwoByte');
		$mailform = new MailForm();
		$this->assertEquals('１２３', $convToTwoByte->invokeArgs($mailform, ['123']));
		$this->assertEquals('ＡＢＣ', $convToTwoByte->invokeArgs($mailform, ['ABC']));
		$this->assertEquals('テスト', $convToTwoByte->invokeArgs($mailform, ['ﾃｽﾄ']));
		$this->assertEquals('－？＜＞＃', $convToTwoByte->invokeArgs($mailform, ['-?<>#']));
	}
	
	public function testConvToOneByte()
	{
		$convToOneByte = getMethod('MailForm', 'convToOneByte');
		$mailform = new MailForm();
		$this->assertEquals('123', $convToOneByte->invokeArgs($mailform, ['１２３']));
		$this->assertEquals('ABC', $convToOneByte->invokeArgs($mailform, ['ＡＢＣ']));
		$this->assertEquals('テスト', $convToOneByte->invokeArgs($mailform, ['テスト']));
		$this->assertEquals('-?<>#', $convToOneByte->invokeArgs($mailform, ['ー？＜＞＃']));
	}
	
	public function testConvToPlus()
	{
		$convToPlus = getMethod('MailForm', 'convToPlus');
		$mailform = new MailForm();
		$this->assertEquals('8112341234', $convToPlus->invokeArgs($mailform, ['+81-1234-1234']));
		$this->assertEquals('0312341234', $convToPlus->invokeArgs($mailform, ['03-1234-1234']));
		$this->assertEquals('123456789', $convToPlus->invokeArgs($mailform, ['123456789']));
		$this->assertEquals('', $convToPlus->invokeArgs($mailform, ['------']));
	}
	
	public function testConvertRequestParam()
	{
		$convertRequestParam = getMethod('MailForm', 'convertRequestParam');
		{
			$mailform = $this->createCompatibleMock('MailForm', ['convToTwoByte']);
			$expectation = $mailform->expects($this->once())
			->method('convToTwoByte')
			->with('value');
			$this->setCompatibleReturnValue($expectation, 'return');
			
			$this->assertEquals('return', $convertRequestParam->invokeArgs($mailform, ['two-byte','value']));
		}
		{
			$mailform = $this->createCompatibleMock('MailForm', ['convToOneByte']);
			$expectation = $mailform->expects($this->once())
			->method('convToOneByte')
			->with('value');
			$this->setCompatibleReturnValue($expectation, 'return');
			
			$this->assertEquals('return', $convertRequestParam->invokeArgs($mailform, ['one-byte','value']));
		}
		{
			$mailform = $this->createCompatibleMock('MailForm', ['convToPlus']);
			$expectation = $mailform->expects($this->once())
			->method('convToPlus')
			->with('value');
			$this->setCompatibleReturnValue($expectation, 'return');
			
			$this->assertEquals('return', $convertRequestParam->invokeArgs($mailform, ['plus','value']));
		}
		{
			$mailform = new MailForm();
			
			$this->assertEquals('testtest', $convertRequestParam->invokeArgs($mailform, ['none','testtest']));
		}
	}
	
	public function testCheckRequestParams()
	{
		$checkRequestParams = getMethod('MailForm', 'checkRequestParams');
		$FormConfig = getProperty('MailForm', 'FormConfig');
		
		$mailform = $this->createCompatibleMock('MailForm', ['checkRequestParam']);
		$expectation = $mailform->expects($this->once())
		->method('checkRequestParam')
		->with(true, 'key1', 'key1_array');
		$this->setCompatibleReturnValue($expectation, true);
		
		$FormConfig->setValue($mailform, ['items' => ['key1' => 'key1_array']]);
		$this->assertTrue($checkRequestParams->invokeArgs($mailform, []));
		
		$mailform = $this->createCompatibleMock('MailForm', ['checkRequestParam']);
		$expectation = $mailform->expects($this->once())
		->method('checkRequestParam')
		->with(true, 'key1', 'key1_array');
		$this->setCompatibleReturnValue($expectation, false);
		
		$FormConfig->setValue($mailform, ['items' => ['key1' => 'key1_array']]);
		$this->assertFalse($checkRequestParams->invokeArgs($mailform, []));
	}
	
	public function testCheckRequestParam()
	{
		$getTempDirPathFromSession = getMethod('MailForm', 'getTempDirPathFromSession');
		$checkRequestParam = getMethod('MailForm', 'checkRequestParam');
		$RequestParam = getProperty('MailForm', 'RequestParam');
		$FormConfig = getProperty('MailForm', 'FormConfig');
		$ErrorCode = getProperty('MailForm', 'ErrorCode');
		$ErrorMessages = getProperty('MailForm', 'ErrorMessages');
		
		$mailform = new MailForm();
		
		// valuesが空の場合
		$ret = true;
		$values = [];
		$this->assertFalse($checkRequestParam->invokeArgs($mailform, [&$ret, 'key', &$values]));
		$this->assertEquals(MailForm::ERROR_CODE_INVALID_FORM_CONFIG, $ErrorCode->getValue($mailform));
		
		// agreementで、入力が空の時
		$mailform = new MailForm();
		$ret = true;
		$values = ['value' => 'agreement', 'empty' => false, 'title'=> 'title'];
		$this->assertTrue($checkRequestParam->invokeArgs($mailform, [&$ret, 'key', &$values]));
		$this->assertFalse($ret);
		$this->assertEquals(MailForm::ERROR_CODE_NO_AGREEMENT, $ErrorCode->getValue($mailform));
		$this->assertEquals('同意していただく必要があります', $ErrorMessages->getValue($mailform)['key']);
		
		// stringで、入力が空の時
		$mailform = new MailForm();
		$ret = true;
		$values = ['value' => 'string', 'empty' => false, 'title'=> 'title'];
		$this->assertTrue($checkRequestParam->invokeArgs($mailform, [&$ret, 'key', &$values]));
		$this->assertFalse($ret);
		$this->assertEquals(MailForm::ERROR_CODE_NOT_NULL_PARAM, $ErrorCode->getValue($mailform));
		$this->assertEquals('「title」は必ずご入力ください', $ErrorMessages->getValue($mailform)['key']);
		
		// selectで、入力が空の時
		$mailform = new MailForm();
		$ret = true;
		$values = ['value' => 'select', 'empty' => false, 'title'=> 'title'];
		$this->assertTrue($checkRequestParam->invokeArgs($mailform, [&$ret, 'key', &$values]));
		$this->assertFalse($ret);
		$this->assertEquals(MailForm::ERROR_CODE_NEED_SELECT_PARAM, $ErrorCode->getValue($mailform));
		$this->assertEquals('「title」は必ず選択してください', $ErrorMessages->getValue($mailform)['key']);
		
		// multi-selectで、入力が空の時
		$mailform = new MailForm();
		$ret = true;
		$values = ['value' => 'multi-select', 'empty' => false, 'title'=> 'title'];
		$this->assertTrue($checkRequestParam->invokeArgs($mailform, [&$ret, 'key', &$values]));
		$this->assertFalse($ret);
		$this->assertEquals(MailForm::ERROR_CODE_NEED_SELECT_PARAM, $ErrorCode->getValue($mailform));
		$this->assertEquals('「title」は必ず選択してください', $ErrorMessages->getValue($mailform)['key']);
		
		// 値に数字が入ってきた時
		$mailform = new MailForm();
		$ret = true;
		$RequestParam->setValue($mailform, ['key' => 1]);
		$values = ['value' => 'string', 'empty' => false, 'title'=> 'title'];
		$this->assertTrue($checkRequestParam->invokeArgs($mailform, [&$ret, 'key', &$values]));
		$this->assertFalse($ret);
		$this->assertEquals(MailForm::ERROR_CODE_INVALID_PARAM, $ErrorCode->getValue($mailform));
		$this->assertEquals('「title」の値は入力できない値です', $ErrorMessages->getValue($mailform)['key']);
		
		// convのテスト
		$mailform = new MailForm();
		$ret = true;
		$RequestParam->setValue($mailform, ['key' => '１２３４５']);
		$values = ['value' => 'plus', 'empty' => true, 'title'=> 'title', 'conv'=> 'plus'];
		$this->assertTrue($checkRequestParam->invokeArgs($mailform, [&$ret, 'key', &$values]));
		$this->assertTrue($ret);
		$this->assertEquals('12345', $RequestParam->getValue($mailform)['key']);
		
		// sameで、値が違う時
		$mailform = new MailForm();
		$ret = true;
		$RequestParam->setValue($mailform, ['key' => 'value', 'same_check' => 'value2']);
		$values = ['value' => 'string', 'empty' => false, 'title'=> 'title', 'same' => 'same_check'];
		$FormConfig->setValue($mailform, ['items' => ['same_check' => ['title'=> 'same_check_title']]]);
		$this->assertTrue($checkRequestParam->invokeArgs($mailform, [&$ret, 'key', &$values]));
		$this->assertFalse($ret);
		$this->assertEquals(MailForm::ERROR_CODE_NOT_SAME_PARAM, $ErrorCode->getValue($mailform));
		$this->assertEquals('「title」が「same_check_title」と同じではありません', $ErrorMessages->getValue($mailform)['key']);
		
		// sameで、値が同じ場合
		$mailform = new MailForm();
		$ret = true;
		$RequestParam->setValue($mailform, ['key' => 'value', 'same_check' => 'value']);
		$values = ['value' => 'string', 'empty' => false, 'title'=> 'title', 'same' => 'same_check'];
		$this->assertTrue($checkRequestParam->invokeArgs($mailform, [&$ret, 'key', &$values]));
		$this->assertTrue($ret);
		
		// minlengthのチェック、minlengthと同じ長さ
		$mailform = new MailForm();
		$ret = true;
		$RequestParam->setValue($mailform, ['key' => '文字列']);
		$values = ['value' => 'string', 'empty' => false, 'title'=> 'title', 'minlength' => 3];
		$this->assertTrue($checkRequestParam->invokeArgs($mailform, [&$ret, 'key', &$values]));
		$this->assertTrue($ret);
		
		// minlengthのチェック、minlengthより短い
		$RequestParam->setValue($mailform, ['key' => '短い']);
		$values = ['value' => 'string', 'empty' => false, 'title'=> 'title', 'minlength' => 3];
		$this->assertTrue($checkRequestParam->invokeArgs($mailform, [&$ret, 'key', &$values]));
		$this->assertFalse($ret);
		$this->assertEquals(MailForm::ERROR_CODE_UNDER_MINLENGTH, $ErrorCode->getValue($mailform));
		$this->assertEquals('「title」は3文字以下でご入力ください', $ErrorMessages->getValue($mailform)['key']);
		
		// maxlengthのチェック、maxlengthと同じ長さ
		$mailform = new MailForm();
		$ret = true;
		$RequestParam->setValue($mailform, ['key' => '文字列']);
		$values = ['value' => 'string', 'empty' => false, 'title'=> 'title', 'maxlength' => 3];
		$this->assertTrue($checkRequestParam->invokeArgs($mailform, [&$ret, 'key', &$values]));
		$this->assertTrue($ret);
		
		// maxlengthのチェック、maxlengthより長い
		$RequestParam->setValue($mailform, ['key' => '長い文字列']);
		$values = ['value' => 'string', 'empty' => false, 'title'=> 'title', 'maxlength' => 3];
		$this->assertTrue($checkRequestParam->invokeArgs($mailform, [&$ret, 'key', &$values]));
		$this->assertFalse($ret);
		$this->assertEquals(MailForm::ERROR_CODE_OVER_MAXLENGTH, $ErrorCode->getValue($mailform));
		$this->assertEquals('「title」は3文字以上でご入力ください', $ErrorMessages->getValue($mailform)['key']);
		
		// value=selectで、selectvaluesがない場合
		$mailform = new MailForm();
		$ret = true;
		
		$RequestParam->setValue($mailform, ['key' => '1']);
		$selectValues = ['1' => 'option1', '2' => 'option2', '3' => 'option3'];
		$values = ['value' => 'select', 'empty' => false, 'title'=> 'title'];
		$this->assertFalse($checkRequestParam->invokeArgs($mailform, [&$ret, 'key', &$values]));
		$this->assertTrue($ret);
		$this->assertEquals(MailForm::ERROR_CODE_INVALID_FORM_CONFIG, $ErrorCode->getValue($mailform));
		$this->assertEquals('システム設定エラーが発生しました', $ErrorMessages->getValue($mailform)['base']);
		
		// value=selectで、入力が不正な値だった場合
		$mailform = new MailForm();
		$ret = true;
		
		$RequestParam->setValue($mailform, ['key' => '5']);
		$values = ['value' => 'select', 'empty' => false, 'title'=> 'title', 'selectvalues' => $selectValues];
		$this->assertTrue($checkRequestParam->invokeArgs($mailform, [&$ret, 'key', &$values]));
		$this->assertFalse($ret);
		$this->assertEquals(MailForm::ERROR_CODE_INVALID_SELECT_PARAM, $ErrorCode->getValue($mailform));
		$this->assertEquals('「title」に不正な値が入力されています', $ErrorMessages->getValue($mailform)['key']);
		
		// value=selectで、入力が正常だった場合
		$mailform = new MailForm();
		$ret = true;
		
		$RequestParam->setValue($mailform, ['key' => '1']);
		$values = ['value' => 'select', 'empty' => false, 'title'=> 'title', 'selectvalues' => $selectValues];
		$this->assertTrue($checkRequestParam->invokeArgs($mailform, [&$ret, 'key', &$values]));
		$this->assertTrue($ret);
		
		// value=multi-selectで、selectvaluesがない場合
		$mailform = new MailForm();
		$ret = true;
		
		$RequestParam->setValue($mailform, ['key' => ['1']]);
		$selectValues = ['1' => 'option1', '2' => 'option2', '3' => 'option3'];
		$values = ['value' => 'multi-select', 'empty' => false, 'title'=> 'title'];
		$this->assertFalse($checkRequestParam->invokeArgs($mailform, [&$ret, 'key', &$values]));
		$this->assertTrue($ret);
		$this->assertEquals(MailForm::ERROR_CODE_INVALID_FORM_CONFIG, $ErrorCode->getValue($mailform));
		$this->assertEquals('システム設定エラーが発生しました', $ErrorMessages->getValue($mailform)['base']);
		
		// value=multi-selectで、入力が不正な値だった場合
		$mailform = new MailForm();
		$ret = true;
		
		$RequestParam->setValue($mailform, ['key' => '5']);
		$values = ['value' => 'multi-select', 'empty' => false, 'title'=> 'title', 'selectvalues' => $selectValues];
		$this->assertTrue($checkRequestParam->invokeArgs($mailform, [&$ret, 'key', &$values]));
		$this->assertFalse($ret);
		$this->assertEquals(MailForm::ERROR_CODE_INVALID_SELECT_PARAM, $ErrorCode->getValue($mailform));
		$this->assertEquals('「title」に不正な値が入力されています', $ErrorMessages->getValue($mailform)['key']);
		
		// value=multi-selectで、入力が不正な値だった場合
		$mailform = new MailForm();
		$ret = true;
		
		$RequestParam->setValue($mailform, ['key' => ['5']]);
		$values = ['value' => 'multi-select', 'empty' => false, 'title'=> 'title', 'selectvalues' => $selectValues];
		$this->assertTrue($checkRequestParam->invokeArgs($mailform, [&$ret, 'key', &$values]));
		$this->assertFalse($ret);
		$this->assertEquals(MailForm::ERROR_CODE_INVALID_SELECT_PARAM, $ErrorCode->getValue($mailform));
		$this->assertEquals('「title」に不正な値が入力されています', $ErrorMessages->getValue($mailform)['key']);
		
		// value=multi-selectで、入力が正常だった場合
		$mailform = new MailForm();
		$ret = true;
		
		$RequestParam->setValue($mailform, ['key' => ['1','2']]);
		$values = ['value' => 'multi-select', 'empty' => false, 'title'=> 'title', 'selectvalues' => $selectValues];
		$this->assertTrue($checkRequestParam->invokeArgs($mailform, [&$ret, 'key', &$values]));
		$this->assertTrue($ret);
		
		// value=kanaで、入力値がカタカナでない場合
		$mailform = new MailForm();
		$ret = true;
		
		$RequestParam->setValue($mailform, ['key' => '123']);
		$values = ['value' => 'kana', 'empty' => false, 'title'=> 'title'];
		$this->assertTrue($checkRequestParam->invokeArgs($mailform, [&$ret, 'key', &$values]));
		$this->assertFalse($ret);
		$this->assertEquals(MailForm::ERROR_CODE_NOT_KANA_PARAM, $ErrorCode->getValue($mailform));
		$this->assertEquals('「title」は全角カナでご入力ください', $ErrorMessages->getValue($mailform)['key']);
		
		// value=kanaで、入力値がカタカナの場合
		$mailform = new MailForm();
		$ret = true;
		
		$RequestParam->setValue($mailform, ['key' => 'カナ']);
		$values = ['value' => 'kana', 'empty' => false, 'title'=> 'title'];
		$this->assertTrue($checkRequestParam->invokeArgs($mailform, [&$ret, 'key', &$values]));
		$this->assertTrue($ret);
		
		// value=yomiで、入力値がひらがなでない場合
		$mailform = new MailForm();
		$ret = true;
		
		$RequestParam->setValue($mailform, ['key' => '123']);
		$values = ['value' => 'yomi', 'empty' => false, 'title'=> 'title'];
		$this->assertTrue($checkRequestParam->invokeArgs($mailform, [&$ret, 'key', &$values]));
		$this->assertFalse($ret);
		$this->assertEquals(MailForm::ERROR_CODE_NOT_YOMI_PARAM, $ErrorCode->getValue($mailform));
		$this->assertEquals('「title」は全角かなでご入力ください', $ErrorMessages->getValue($mailform)['key']);
		
		// value=yomiで、入力値がひらがなの場合
		$mailform = new MailForm();
		$ret = true;
		
		$RequestParam->setValue($mailform, ['key' => 'よみ']);
		$values = ['value' => 'yomi', 'empty' => false, 'title'=> 'title'];
		$this->assertTrue($checkRequestParam->invokeArgs($mailform, [&$ret, 'key', &$values]));
		
		// value=plusで、入力値が数字でない場合
		$mailform = new MailForm();
		$ret = true;
		
		$RequestParam->setValue($mailform, ['key' => 'abc']);
		$values = ['value' => 'plus', 'empty' => false, 'title'=> 'title'];
		$this->assertTrue($checkRequestParam->invokeArgs($mailform, [&$ret, 'key', &$values]));
		$this->assertFalse($ret);
		$this->assertEquals(MailForm::ERROR_CODE_NOT_PLUS_PARAM, $ErrorCode->getValue($mailform));
		$this->assertEquals('「title」は半角数字でご入力ください', $ErrorMessages->getValue($mailform)['key']);
		
		// value=plusで、入力値が数字の場合
		$mailform = new MailForm();
		$ret = true;
		
		$RequestParam->setValue($mailform, ['key' => '123']);
		$values = ['value' => 'plus', 'empty' => false, 'title'=> 'title'];
		$this->assertTrue($checkRequestParam->invokeArgs($mailform, [&$ret, 'key', &$values]));
		
		// value=telで、入力値が数字でない場合
		$mailform = new MailForm();
		$ret = true;
		
		$RequestParam->setValue($mailform, ['key' => 'abc']);
		$values = ['value' => 'tel', 'empty' => false, 'title'=> 'title'];
		$this->assertTrue($checkRequestParam->invokeArgs($mailform, [&$ret, 'key', &$values]));
		$this->assertFalse($ret);
		$this->assertEquals(MailForm::ERROR_CODE_TEL_PARAM, $ErrorCode->getValue($mailform));
		$this->assertEquals('「title」は半角数字でご入力ください', $ErrorMessages->getValue($mailform)['key']);
		
		// value=telで、入力値が数字の場合
		$mailform = new MailForm();
		$ret = true;
		
		$RequestParam->setValue($mailform, ['key' => '123-1234-1234']);
		$values = ['value' => 'tel', 'empty' => false, 'title'=> 'title'];
		$this->assertTrue($checkRequestParam->invokeArgs($mailform, [&$ret, 'key', &$values]));
		
		// value=emailで、入力値が数字でない場合
		$mailform = new MailForm();
		$ret = true;
		
		$RequestParam->setValue($mailform, ['key' => 'abc']);
		$values = ['value' => 'email', 'empty' => false, 'title'=> 'title'];
		$this->assertTrue($checkRequestParam->invokeArgs($mailform, [&$ret, 'key', &$values]));
		$this->assertFalse($ret);
		$this->assertEquals(MailForm::ERROR_CODE_NOT_EMAIL_PARAM, $ErrorCode->getValue($mailform));
		$this->assertEquals('「title」のメールアドレスは正しい形式でご入力ください', $ErrorMessages->getValue($mailform)['key']);
		
		// value=emailで、入力値が数字の場合
		$mailform = new MailForm();
		$ret = true;
		
		$RequestParam->setValue($mailform, ['key' => 'mail@example.com']);
		$values = ['value' => 'tel', 'empty' => false, 'title'=> 'title'];
		$this->assertTrue($checkRequestParam->invokeArgs($mailform, [&$ret, 'key', &$values]));
		
		// value=agreementで、設定にequalがない場合
		$mailform = new MailForm();
		$ret = true;
		
		$RequestParam->setValue($mailform, ['key' => 'agree']);
		$values = ['value' => 'agreement', 'empty' => false, 'title'=> 'title'];
		$this->assertFalse($checkRequestParam->invokeArgs($mailform, [&$ret, 'key', &$values]));
		$this->assertTrue($ret);
		$this->assertEquals(MailForm::ERROR_CODE_INVALID_FORM_CONFIG, $ErrorCode->getValue($mailform));
		$this->assertEquals('システム設定エラーが発生しました', $ErrorMessages->getValue($mailform)['base']);
		
		// value=agreementで、入力値がequalの値と違う場合
		$mailform = new MailForm();
		$ret = true;
		
		$RequestParam->setValue($mailform, ['key' => '111']);
		$values = ['value' => 'agreement', 'empty' => false, 'title'=> 'title', 'equal' => 'agree'];
		$this->assertTrue($checkRequestParam->invokeArgs($mailform, [&$ret, 'key', &$values]));
		$this->assertFalse($ret);
		$this->assertEquals(MailForm::ERROR_CODE_NO_AGREEMENT, $ErrorCode->getValue($mailform));
		$this->assertEquals('同意していただく必要があります', $ErrorMessages->getValue($mailform)['key']);
		
		// value=agreementで、入力値がequalの値と同じ場合
		$mailform = new MailForm();
		$ret = true;
		
		$RequestParam->setValue($mailform, ['key' => 'agree']);
		$values = ['value' => 'agreement', 'empty' => false, 'title'=> 'title', 'equal' => 'agree'];
		$this->assertTrue($checkRequestParam->invokeArgs($mailform, [&$ret, 'key', &$values]));
		
		// value=fileで、ファイルがない場合
		$mailform = new MailForm();
		$mailform->TmpDirectoryPath = dirname(__FILE__).'/test_tmp/';
		
		$RequestParam->setValue($mailform, ['key' => 'test.txt']);
		$values = ['value' => 'file', 'empty' => false, 'title'=> 'title'];
		$this->assertFalse($checkRequestParam->invokeArgs($mailform, [&$ret, 'key', &$values]));
		$this->assertEquals(MailForm::ERROR_CODE_TEMP_FILE, $ErrorCode->getValue($mailform));
		$this->assertEquals('ファイルシステムエラーが発生しました', $ErrorMessages->getValue($mailform)['key']);
		
		// value=fileで、Mimeを指定しない場合
		$mailform = new MailForm();
		$mailform->TmpDirectoryPath = dirname(__FILE__).'/test_tmp/';
		
		$filebasepath = $getTempDirPathFromSession->invokeArgs($mailform, []);
		if (!is_dir($mailform->TmpDirectoryPath)) {
			mkdir($mailform->TmpDirectoryPath, 0700, true);
		}
		if (!is_dir($filebasepath)) {
			mkdir($filebasepath, 0700, true);
		}
		file_put_contents($filebasepath.'/test.txt', 'test text');
		
		$RequestParam->setValue($mailform, ['key' => 'test.txt']);
		$values = ['value' => 'file', 'empty' => false, 'title'=> 'title'];
		$this->assertTrue($checkRequestParam->invokeArgs($mailform, [&$ret, 'key', &$values]));
		$this->assertTrue($ret);
				
		// value=fileで、Mimeが一致した場合
		$values = ['value' => 'file', 'empty' => false, 'title'=> 'title', 'filetypes' => ['text/plain']];
		$this->assertTrue($checkRequestParam->invokeArgs($mailform, [&$ret, 'key', &$values]));
		$this->assertTrue($ret);
		
		// value=fileで、Mimeが合わない場合
		$values = ['value' => 'file', 'empty' => false, 'title'=> 'title', 'filetypes' => ['text/html']];
		$this->assertTrue($checkRequestParam->invokeArgs($mailform, [&$ret, 'key', &$values]));
		$this->assertFalse($ret);
		$this->assertEquals(MailForm::ERROR_CODE_UNKNOWN_FILE_FORMAT, $ErrorCode->getValue($mailform));
		$this->assertEquals('アップロードファイル「test.txt」のファイル形式には対応してません', $ErrorMessages->getValue($mailform)['key']);
		
		system("rm -rf {$mailform->TmpDirectoryPath}");
	}
	
	public function testSendMail()
	{
		$sendMail = getMethod('MailForm', 'sendMail');
		$MailConfigs = getProperty('MailForm', 'MailConfigs');
		
		$mailform = $this->createCompatibleMock('MailForm', ['sendMailByIndex']);
		$expectation = $mailform->expects($this->once())
		->method('sendMailByIndex')
		->with('mail_index');
		$this->setCompatibleReturnValue($expectation, true);
		
		$MailConfigs->setValue($mailform, ['mail_index' => 'mail_index_values']);
		$this->assertTrue($sendMail->invokeArgs($mailform, []));
		
		$mailform = $this->createCompatibleMock('MailForm', ['sendMailByIndex']);
		$expectation = $mailform->expects($this->once())
		->method('sendMailByIndex')
		->with('mail_index');
		$this->setCompatibleReturnValue($expectation, false);
		
		$MailConfigs->setValue($mailform, ['mail_index' => 'mail_index_values']);
		$this->assertFalse($sendMail->invokeArgs($mailform, []));
	}
	
	#[AllowMockObjectsWithoutExpectations]
	public function testSendMailByIndex()
	{
		$sendMailByIndex = getMethod('MailForm', 'sendMailByIndex');
		$FormConfig = getProperty('MailForm', 'FormConfig');
		$RequestParam = getProperty('MailForm', 'RequestParam');
		$MailerFactory = getProperty('MailForm', 'MailerFactory');
		$ErrorCode = getProperty('MailForm', 'ErrorCode');
		$ErrorMessages = getProperty('MailForm', 'ErrorMessages');
		
		$ToEncoding = getProperty('SimpleMailer', 'ToEncoding');
		$FromEncoding = getProperty('SimpleMailer', 'FromEncoding');
		$Encoding = getProperty('SimpleMailer', 'Encoding');
		$From = getProperty('SimpleMailer', 'From');
		$To = getProperty('SimpleMailer', 'To');
		$ReturnPath = getProperty('SimpleMailer', 'ReturnPath');
		$ReplyTo = getProperty('SimpleMailer', 'ReplyTo');
		$Subject = getProperty('SimpleMailer', 'Subject');
		$Body = getProperty('SimpleMailer', 'Body');
		
		$simpleMailer = new SimpleMailer();
		$mailform = $this->createCompatibleMock('MailForm', ['getMailer']);
		$expectation = $this->expectsAny($mailform)
		->method('getMailer');
		$this->setCompatibleReturnValue($expectation, $simpleMailer);
		
		$mailform->FromEncoding = 'SJIS';
		$mailform->CharSet = 'EUC-JP';
		$mailform->MailEncoding = 'UCS-2LE';
		
		$this->assertFalse($sendMailByIndex->invokeArgs($mailform, ['1']));
		$mailerFactory = $MailerFactory->getValue($mailform);
		
		$this->assertEquals('SJIS', $FromEncoding->getValue($simpleMailer));
		$this->assertEquals('EUC-JP', $ToEncoding->getValue($simpleMailer));
		$this->assertEquals('UCS-2LE', $Encoding->getValue($simpleMailer));
		
		$this->assertEquals(MailForm::ERROR_CODE_NOSET_TO, $ErrorCode->getValue($mailform));
		$this->assertEquals('システムエラーが発生しました', $ErrorMessages->getValue($mailform)['base']);
		
		$simpleMailer = $this->createCompatibleMock('SimpleMailer', ['send']);
		$expectation = $simpleMailer->expects($this->once())
		->method('send');
		$this->setCompatibleReturnValue($expectation, false);
		
		$mailform = $this->createCompatibleMock('MailForm', ['getMailer', 'getAllowTempFilesByIndex']);
		$expectation = $mailform->expects($this->once())
		->method('getMailer');
		$this->setCompatibleReturnValue($expectation, $simpleMailer);
		$expectation = $this->expectsAny($mailform)
		->method('getAllowTempFilesByIndex');
		$this->setCompatibleReturnValue($expectation, false);
		
		$mailform->DefaultEmailAddress = 'default@example.com';
		$this->assertFalse($sendMailByIndex->invokeArgs($mailform, ['1']));
		$this->assertEquals(MailForm::ERROR_CODE_CANNOT_SEND, $ErrorCode->getValue($mailform));
		$this->assertEquals('メールを送ることができませんでした', $ErrorMessages->getValue($mailform)['base']);
		$this->assertEquals('default@example.com', $To->getValue($simpleMailer)[0][0]);
		$this->assertEquals('default@example.com', $From->getValue($simpleMailer)[0]);
		
		$simpleMailer = $this->createCompatibleMock('SimpleMailer', ['send', 'AddAttachment']);
		$expectation = $simpleMailer->expects($this->once())
		->method('send');
		$this->setCompatibleReturnValue($expectation, true);
		$expectation = $simpleMailer->expects($this->once())
		->method('AddAttachment')
		->with('/path/filevalue');
		$this->setCompatibleReturnValue($expectation, false);
		
		$mailform = $this->createCompatibleMock('MailForm', ['getMailer', 'getTempDirPathFromSession']);
		$expectation = $this->expectsAny($mailform)
		->method('getMailer');
		$this->setCompatibleReturnValue($expectation, $simpleMailer);
		$expectation = $this->expectsAny($mailform)
		->method('getTempDirPathFromSession');
		$this->setCompatibleReturnValue($expectation, '/path/');
		
		$mailform->setFromByIndex('1', '{{{from}}}', 'from_name');
		$mailform->setReturnPathByIndex('1', 'returnpath@example.com', 'returnpath_name');
		$mailform->setReplyToByIndex('1', 'replyto@example.com', 'replyto_name');
		$mailform->addToByIndex('1', '{{{to}}}');
		$mailform->addToByIndex('1', 'addTo@example.com', 'add_to_name');
		$mailform->setMailSubjectTemplateByIndex('1', 'subject {{{key1}}}');
		$mailform->setMailBodyTemplateByIndex('1', 'body key2:{{{key2}}} file:{{{file}}} selectkey:{{{selectkey}}} multi-selectkey:{{{multi-selectkey}}}');
		$mailform->CharSet = 'UTF-8';
		
		$selectValues = ['1' => 'option1', '2' => 'option2', '3' => 'option3'];
		$FormConfig->setValue($mailform, ['items' => ['to' => ['value' => 'email'],
																'from' => ['value' => 'email'],
																'key1' => ['value' => 'string'],
																'key2' => ['value' => 'string'],
																'file' => ['value' => 'file'],
																'selectkey' => ['value' => 'select', 'selectvalues' => $selectValues],
																'multi-selectkey' => ['value' => 'multi-select', 'selectvalues' => $selectValues]]]);
		$RequestParam->setValue($mailform, ['to' => 'to@example.com', 'from' => 'from@example.com', 'key1' => 'value1', 'file' => 'filevalue', 'key2' => 'value2', 'selectkey' => '1', 'multi-selectkey' => ['2','3']]);
		
		$this->assertTrue($sendMailByIndex->invokeArgs($mailform, ['1']));
		$this->assertEquals('to@example.com', $To->getValue($simpleMailer)[0][0]);
		$this->assertEquals('', $To->getValue($simpleMailer)[0][1]);
		$this->assertEquals('addTo@example.com', $To->getValue($simpleMailer)[1][0]);
		$this->assertEquals('add_to_name', $To->getValue($simpleMailer)[1][1]);
		$this->assertEquals('from@example.com', $From->getValue($simpleMailer)[0]);
		$this->assertEquals('from_name', $From->getValue($simpleMailer)[1]);
		$this->assertEquals('returnpath@example.com', $ReturnPath->getValue($simpleMailer)[0]);
		$this->assertEquals('returnpath_name', $ReturnPath->getValue($simpleMailer)[1]);
		$this->assertEquals('replyto@example.com', $ReplyTo->getValue($simpleMailer)[0]);
		$this->assertEquals('replyto_name', $ReplyTo->getValue($simpleMailer)[1]);
		
		$this->assertEquals('subject value1', $Subject->getValue($simpleMailer));
		$this->assertEquals('body key2:value2 file:filevalue selectkey:option1 multi-selectkey:option2／option3', $Body->getValue($simpleMailer));
		
	}
	
	public function testGetCompleteMailSubjectByIndex()
	{
		$getCompleteMailSubjectByIndex = getMethod('MailForm', 'getCompleteMailSubjectByIndex');
		$mailform = $this->createCompatibleMock('MailForm', ['getCompleteMailSubject', 'getMailSubjectTemplateByIndex']);
		$expectation = $mailform->expects($this->once())
		->method('getMailSubjectTemplateByIndex')
		->with('index');
		$this->setCompatibleReturnValue($expectation, 'subject');
		$expectation = $mailform->expects($this->once())
		->method('getCompleteMailSubject')
		->with('subject');
		$this->setCompatibleReturnValue($expectation, 'return');
		
		$this->assertEquals('return', $getCompleteMailSubjectByIndex->invokeArgs($mailform, ['index']));
	}
	
	public function testGetCompleteMailSubject()
	{
		$getCompleteMailSubject = getMethod('MailForm', 'getCompleteMailSubject');
		$mailform = $this->createCompatibleMock('MailForm', ['replaceFieldTag']);
		$expectation = $mailform->expects($this->once())
		->method('replaceFieldTag')
		->with('value');
		$this->setCompatibleReturnValue($expectation, 'return');
		
		$this->assertEquals('return', $getCompleteMailSubject->invokeArgs($mailform, ['value']));
	}
	
	public function testGetCompleteMailBodyByIndex()
	{
		$getCompleteMailBodyByIndex = getMethod('MailForm', 'getCompleteMailBodyByIndex');
		$mailform = $this->createCompatibleMock('MailForm', ['getCompleteMailBody', 'getMailBodyTemplateByIndex']);
		$expectation = $mailform->expects($this->once())
		->method('getMailBodyTemplateByIndex')
		->with('index');
		$this->setCompatibleReturnValue($expectation, 'body');
		$expectation = $mailform->expects($this->once())
		->method('getCompleteMailBody')
		->with('body');
		$this->setCompatibleReturnValue($expectation, 'return');
		
		$this->assertEquals('return', $getCompleteMailBodyByIndex->invokeArgs($mailform, ['index']));
	}
	
	public function testGetValueByType()
	{
		$getValueByType = getMethod('MailForm', 'getValueByType');
		$RequestParam = getProperty('MailForm', 'RequestParam');
		$FormConfig = getProperty('MailForm', 'FormConfig');
		
		$mailform = new MailForm();
		$selectValues = ['1' => 'option1', '2' => 'option2', '3' => 'option3'];
		$FormConfig->setValue($mailform, ['items' => ['selectkey' => ['value' => 'select', 'selectvalues' => $selectValues],
																'multi-selectkey' => ['value' => 'multi-select', 'selectvalues' => $selectValues]]]);
		$RequestParam->setValue($mailform, ['key1' => 'value1', 'key2' => 'value2', 'selectkey' => '1', 'multi-selectkey' => ['2','3']]);
		
		$this->assertEquals('value1', $getValueByType->invokeArgs($mailform, ['key1', 'string']));
		$this->assertEquals('option1', $getValueByType->invokeArgs($mailform, ['selectkey', 'select']));
		$this->assertEquals('option2／option3', $getValueByType->invokeArgs($mailform, ['multi-selectkey', 'multi-select']));
		$this->assertEquals('', $getValueByType->invokeArgs($mailform, ['none', 'string']));
		$this->assertEquals('', $getValueByType->invokeArgs($mailform, ['none', 'select']));
		$this->assertEquals('', $getValueByType->invokeArgs($mailform, ['none', 'multi-selectkey']));
		
	}
	
	public function testGetCompleteMailBody()
	{
		$getCompleteMailBody = getMethod('MailForm', 'getCompleteMailBody');
		$mailform = $this->createCompatibleMock('MailForm', ['replaceFieldTag']);
		$expectation = $mailform->expects($this->once())
		->method('replaceFieldTag')
		->with('value');
		$this->setCompatibleReturnValue($expectation, 'return');
		
		$this->assertEquals('return', $getCompleteMailBody->invokeArgs($mailform, ['value']));
	}
	
	public function testReplaceFieldTag()
	{
		$replaceFieldTag = getMethod('MailForm', 'replaceFieldTag');
		$RequestParam = getProperty('MailForm', 'RequestParam');
		$FormConfig = getProperty('MailForm', 'FormConfig');
		
		$mailform = $this->createCompatibleMock('MailForm', ['getDateTime']);
		$expectation = $this->expectsAny($mailform)
		->method('getDateTime');
		$this->setCompatibleReturnValue($expectation, 'return_getDateTime');
		
		$selectValues = ['1' => 'option1', '2' => 'option2', '3' => 'option3'];
		$FormConfig->setValue($mailform, ['items' => ['key1' => ['value' => 'string'],
																'selectkey' => ['value' => 'select', 'selectvalues' => $selectValues],
																'multi-selectkey' => ['value' => 'multi-select', 'selectvalues' => $selectValues]]]);
		$RequestParam->setValue($mailform, ['key1' => 'value1', 'key2' => 'value2', 'selectkey' => '1', 'multi-selectkey' => ['2','3']]);
		
		$this->assertEquals('時間：return_getDateTime', $replaceFieldTag->invokeArgs($mailform, ['時間：{{{datetime}}}']));
		$this->assertEquals('key1=value1\nselectkey=option1\nmulti-selectkey=option2／option3', $replaceFieldTag->invokeArgs($mailform, ['key1={{{key1}}}\nselectkey={{{selectkey}}}\nmulti-selectkey={{{multi-selectkey}}}']));
		$RequestParam->setValue($mailform, []);
		$this->assertEquals('key1=\nselectkey=\nmulti-selectkey=', $replaceFieldTag->invokeArgs($mailform, ['key1={{{key1}}}\nselectkey={{{selectkey}}}\nmulti-selectkey={{{multi-selectkey}}}']));
		
	}
	
	public function testGetFieldTags()
	{
		$getFieldTags = getMethod('MailForm', 'getFieldTags');
	
		$mailform = new MailForm();
		$array = $getFieldTags->invokeArgs($mailform, ['testtesttest']);
		$this->assertCount(0, $array);
		$array = $getFieldTags->invokeArgs($mailform, ['{{{aaa}}}']);
		$this->assertCount(1, $array);
		$this->assertEquals('aaa', $array[0]);
		$array = $getFieldTags->invokeArgs($mailform, ['{{{aaa_1}}}runnrun{{{aaa_2}}}testtest']);
		$this->assertCount(2, $array);
		$this->assertEquals('aaa_1', $array[0]);
		$this->assertEquals('aaa_2', $array[1]);
		
	}
}
