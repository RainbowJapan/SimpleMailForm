<?php
require_once(dirname(__FILE__).'/helpers/common.php');
require_once(dirname(__FILE__).'/helpers/class.testmailer.php');
require_once(dirname(__FILE__).'/helpers/class.test_session.php');
require_once(dirname(__FILE__).'/../lib/class.mailform.php');

class InnerSessionTest extends PHPUnit_Framework_TestCase
{
	protected function setUp(){
		if(!isset($_SESSION)){
			$_SESSION=array();
		}
		$_SERVER=array('DOCUMENT_ROOT' => '/document/root');
	}
	
	public function test__construct() {
		$mailform = new MailForm();
		$FromEncoding = getProperty('MailForm', 'FromEncoding');
		$CharSet = getProperty('MailForm', 'CharSet');
		$Status = getProperty('MailForm', 'Status');
		$TmpDirectryPath = getProperty('MailForm', 'TmpDirectryPath');
		
		$this->assertEquals($mailform->FromEncoding, mb_internal_encoding());
		$this->assertEquals($mailform->CharSet, mb_internal_encoding());
		$this->assertEquals($Status->getValue($mailform), MailForm::STATUS_NONE);
		$this->assertEquals($mailform->TmpDirectryPath, '/document/root/tmp/');
	}
	
	public function testGetDateTime() {
		$mailform = new MailForm();
		$this->assertEquals(date ('Y/m/d(D) H:i'), $mailform->getDateTime());
	}
	
	public function testGetParamater() {
		$mailform = new MailForm();
		
		$this->assertNull($mailform->getParamater(array(), 'key'));
		$this->assertEquals('testtest', $mailform->getParamater(array('key' => "test\0test"), 'key'));
	}
	
	public function testSetMailerFactory() {
		$MailerFactory = getProperty('MailForm', 'MailerFactory');
		$mailform = new MailForm();
		$factory = new TestmailerFactory();
		$mailform->setMailerFactory($factory);
		$this->assertEquals($factory, $MailerFactory->getValue($mailform));
	}
	
	public function testGetMailer() {
		$getMailer = getMethod('MailForm', 'getMailer');
		$mailform = new MailForm();
		
		$this->assertInstanceOf('SimpleMailer', $getMailer->invokeArgs($mailform, array()));
		
		$mailform = new MailForm();
		$factory = new TestmailerFactory();
		$mailform->setMailerFactory($factory);
		$this->assertInstanceOf('TestMailer', $getMailer->invokeArgs($mailform, array()));
	}
	
	public function testSetSessionFactory() {
		$SessionFactory = getProperty('MailForm', 'SessionFactory');
		$mailform = new MailForm();
		$factory = new TestSessionFactory();
		$mailform->setSessionFactory($factory);
		$this->assertEquals($factory, $SessionFactory->getValue($mailform));
	}
	
	public function testGetSession() {
		$getSession = getMethod('MailForm', 'getSession');
		$mailform = new MailForm();
		$factory = new TestSessionFactory();
		$mailform->setSessionFactory($factory);
		$this->assertInstanceOf('TestSession', $getSession->invokeArgs($mailform, array()));
	}
	
	public function testSetErrorCodeMessage() {
		$ErrorCodeMessages = getProperty('MailForm', 'ErrorCodeMessages');
		$mailform = new MailForm();
		$mailform->setErrorCodeMessage(1000000, 'test');
		$errorCodeMessages = $ErrorCodeMessages->getValue($mailform);
		$this->assertArrayHasKey(1000000, $errorCodeMessages);
		$this->assertEquals('test', $errorCodeMessages[1000000]);
	}
	
	public function testGetStatus() {
		$Status = getProperty('MailForm', 'Status');
		$mailform = new MailForm();
		$Status->setValue($mailform, 100);
		$this->assertEquals(100, $mailform->getStatus());
	}
	
	public function testIsError() {
		$ErrorCode = getProperty('MailForm', 'ErrorCode');
		$mailform = new MailForm();
		$ErrorCode->setValue($mailform, MailForm::ERROR_CODE_OK);
		$this->assertFalse($mailform->isError());
		
		$ErrorCode->setValue($mailform, MailForm::ERROR_CODE_NOSET_TO);
		$this->assertTrue($mailform->isError());
		
	}
	
	public function testSetErrorMessage() {
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
		$this->assertEquals('「title」は必ず入力してください。', $errorMessages['key']);
		
		$mailform->setErrorMessage('key2', 100000, "title2");
		$this->assertEquals(100000, $ErrorCode->getValue($mailform));
		$errorMessages = $ErrorMessages->getValue($mailform);
		$this->assertCount(2, $errorMessages);
		$this->assertArrayHasKey('key2', $errorMessages);
		$this->assertEquals('エラーが発生しています。', $errorMessages['key2']);
		
		$mailform->setErrorMessage(MailForm::BASE_ERROR_KEY, MailForm::ERROR_CODE_INVALID_FORM_CONFIG);
		$this->assertEquals(MailForm::ERROR_CODE_INVALID_FORM_CONFIG, $ErrorCode->getValue($mailform));
		$errorMessages = $ErrorMessages->getValue($mailform);
		$this->assertCount(1, $errorMessages);
		$this->assertArrayHasKey(MailForm::BASE_ERROR_KEY, $errorMessages);
		$this->assertEquals('システムエラーが発生しました。', $errorMessages[MailForm::BASE_ERROR_KEY]);
	}
	
	public function testIsCriticalError() {
		$ErrorMessages = getProperty('MailForm', 'ErrorMessages');
		$mailform = new MailForm();
		
		$this->assertFalse($mailform->isCriticalError());
		
		$mailform->setErrorMessage('key', MailForm::ERROR_CODE_NOT_NULL_PARAM, "title");
		$this->assertFalse($mailform->isCriticalError());
		
		$mailform->setErrorMessage(MailForm::BASE_ERROR_KEY, MailForm::ERROR_CODE_INVALID_FORM_CONFIG);
		$this->assertTrue($mailform->isCriticalError());
	}
	
	public function testIsErrorByFieldName() {
		$ErrorMessages = getProperty('MailForm', 'ErrorMessages');
		$mailform = new MailForm();
		
		$this->assertFalse($mailform->isErrorByFieldName('key'));
		
		$mailform->setErrorMessage('key', MailForm::ERROR_CODE_NOT_NULL_PARAM, "title");
		$this->assertTrue($mailform->isErrorByFieldName('key'));
		$this->assertFalse($mailform->isErrorByFieldName('key1'));
	}
	
	public function testGetErrorMessage() {
		$mailform = new MailForm();
		$this->assertEquals('',$mailform->getErrorMessage());
		
		$mailform->setErrorMessage('key1', MailForm::ERROR_CODE_NOT_NULL_PARAM, "title1");
		$this->assertEquals('\n「title1」は必ず入力してください。',$mailform->getErrorMessage());
		$mailform->setErrorMessage('key2', MailForm::ERROR_CODE_NOT_NULL_PARAM, "title2");
		$this->assertEquals('\n「title1」は必ず入力してください。\n「title2」は必ず入力してください。',$mailform->getErrorMessage());
	}
	
	public function testGetErrorMessages() {
		$mailform = new MailForm();
		$this->assertEquals(array(),$mailform->getErrorMessages());
		
		$mailform->setErrorMessage('key1', MailForm::ERROR_CODE_NOT_NULL_PARAM, "title1");
		$this->assertEquals(array('「title1」は必ず入力してください。'),$mailform->getErrorMessages());
		
		$mailform->setErrorMessage('key2', MailForm::ERROR_CODE_NOT_NULL_PARAM, "title1");
		$this->assertEquals(array('「title1」は必ず入力してください。'),$mailform->getErrorMessages());
		
		$mailform->setErrorMessage('key3', MailForm::ERROR_CODE_NOT_NULL_PARAM, "title3");
		$this->assertEquals(array('「title1」は必ず入力してください。','「title3」は必ず入力してください。'),$mailform->getErrorMessages());
	}
	
	public function testGetMailConfigByIndex() {
		$getMailConfigByIndex = getMethod('MailForm', 'getMailConfigByIndex');
		$mailform = new MailForm();
		$test1Config = $getMailConfigByIndex->invokeArgs($mailform, array('test1'));
		$test1Config->ToAdresses['test1@example.com'] = 'test1';
		$test2Config = $getMailConfigByIndex->invokeArgs($mailform, array('test2'));
		$test2Config->ToAdresses['test2@example.com'] = 'test2';
		
		$this->assertEquals($test1Config, $getMailConfigByIndex->invokeArgs($mailform, array('test1')));
		$this->assertEquals($test2Config, $getMailConfigByIndex->invokeArgs($mailform, array('test2')));
		
	}
	
	public function testAddToAndGetToArray() {
		$getToArray = getMethod('MailForm', 'getToArray');
		$mailform = new MailForm();
		$mailform->addTo('to1@example.com', 'to1');
		$tos = $getToArray->invokeArgs($mailform, array());
		$this->assertCount(1, $tos);
		$this->assertArrayHasKey('to1@example.com', $tos);
		$this->assertEquals('to1', $tos['to1@example.com']);
		
		$mailform->addTo('to2@example.com', 'to2');
		$tos = $getToArray->invokeArgs($mailform, array());
		$this->assertCount(2, $tos);
		$this->assertArrayHasKey('to2@example.com', $tos);
		$this->assertEquals('to2', $tos['to2@example.com']);
	}
	
	public function testAddToByIndexAndGetToArrayByIndex() {
		$getToArrayByIndex = getMethod('MailForm', 'getToArrayByIndex');
		$mailform = new MailForm();
		
		$mailform->addToByIndex(1, 'to1@example.com', 'to1');
		$tos = $getToArrayByIndex->invokeArgs($mailform, array(1));
		$this->assertCount(1, $tos);
		$this->assertArrayHasKey('to1@example.com', $tos);
		$this->assertEquals('to1', $tos['to1@example.com']);
		
		$mailform->addToByIndex(1, 'to2@example.com', 'to2');
		$tos = $getToArrayByIndex->invokeArgs($mailform, array(1));
		$this->assertCount(2, $tos);
		$this->assertArrayHasKey('to2@example.com', $tos);
		$this->assertEquals('to2', $tos['to2@example.com']);
		
		$mailform->addToByIndex(2, 'to3@example.com', 'to3');
		$tos = $getToArrayByIndex->invokeArgs($mailform, array(2));
		$this->assertCount(1, $tos);
		$this->assertArrayHasKey('to3@example.com', $tos);
		$this->assertEquals('to3', $tos['to3@example.com']);
	}
	
	public function testAddFromAndGetFromArray() {
		$getFromArray = getMethod('MailForm', 'getFromArray');
		$mailform = new MailForm();
		$mailform->setFrom('from1@example.com', 'from1');
		$froms = $getFromArray->invokeArgs($mailform, array());
		$this->assertCount(1, $froms);
		$this->assertArrayHasKey('from1@example.com', $froms);
		$this->assertEquals('from1', $froms['from1@example.com']);
		
		$mailform->setFrom('from2@example.com', 'from2');
		$froms = $getFromArray->invokeArgs($mailform, array());
		$this->assertCount(1, $froms);
		$this->assertArrayHasKey('from2@example.com', $froms);
		$this->assertEquals('from2', $froms['from2@example.com']);
	}
	
	public function testAddFromByIndexAndGetFromArrayByIndex() {
		$getFromArrayByIndex = getMethod('MailForm', 'getFromArrayByIndex');
		$mailform = new MailForm();
		
		$mailform->setFromByIndex(1, 'from1@example.com', 'from1');
		$froms = $getFromArrayByIndex->invokeArgs($mailform, array(1));
		$this->assertCount(1, $froms);
		$this->assertArrayHasKey('from1@example.com', $froms);
		$this->assertEquals('from1', $froms['from1@example.com']);
		
		$mailform->setFromByIndex(1, 'from2@example.com', 'from2');
		$froms = $getFromArrayByIndex->invokeArgs($mailform, array(1));
		$this->assertCount(1, $froms);
		$this->assertArrayHasKey('from2@example.com', $froms);
		$this->assertEquals('from2', $froms['from2@example.com']);
		
		$mailform->setFromByIndex(2, 'from3@example.com', 'from3');
		$froms = $getFromArrayByIndex->invokeArgs($mailform, array(2));
		$this->assertCount(1, $froms);
		$this->assertArrayHasKey('from3@example.com', $froms);
		$this->assertEquals('from3', $froms['from3@example.com']);
	}
	
	public function testAddReturnPathAndGetReturnPathArray() {
		$getReturnPathArray = getMethod('MailForm', 'getReturnPathArray');
		$mailform = new MailForm();
		$mailform->setReturnPath('path1@example.com', 'path1');
		$paths = $getReturnPathArray->invokeArgs($mailform, array());
		$this->assertCount(1, $paths);
		$this->assertArrayHasKey('path1@example.com', $paths);
		$this->assertEquals('path1', $paths['path1@example.com']);
		
		$mailform->setReturnPath('path2@example.com', 'path2');
		$paths = $getReturnPathArray->invokeArgs($mailform, array());
		$this->assertCount(1, $paths);
		$this->assertArrayHasKey('path2@example.com', $paths);
		$this->assertEquals('path2', $paths['path2@example.com']);
	}
	
	public function testAddReturnPathByIndexAndGetReturnPathArrayByIndex() {
		$getReturnPathArrayByIndex = getMethod('MailForm', 'getReturnPathArrayByIndex');
		$mailform = new MailForm();
		
		$mailform->setReturnPathByIndex(1, 'path1@example.com', 'path1');
		$paths = $getReturnPathArrayByIndex->invokeArgs($mailform, array(1));
		$this->assertCount(1, $paths);
		$this->assertArrayHasKey('path1@example.com', $paths);
		$this->assertEquals('path1', $paths['path1@example.com']);
		
		$mailform->setReturnPathByIndex(1, 'path2@example.com', 'path2');
		$paths = $getReturnPathArrayByIndex->invokeArgs($mailform, array(1));
		$this->assertCount(1, $paths);
		$this->assertArrayHasKey('path2@example.com', $paths);
		$this->assertEquals('path2', $paths['path2@example.com']);
		
		$mailform->setReturnPathByIndex(2, 'path3@example.com', 'path3');
		$paths = $getReturnPathArrayByIndex->invokeArgs($mailform, array(2));
		$this->assertCount(1, $paths);
		$this->assertArrayHasKey('path3@example.com', $paths);
		$this->assertEquals('path3', $paths['path3@example.com']);
	}
	
	public function testSetMailBodyTemplateAndGetMailBodyTemplate() {
		$getMailBodyTemplate = getMethod('MailForm', 'getMailBodyTemplate');
		$mailform = new MailForm();
		$mailform->setMailBodyTemplate('body1');
		$body = $getMailBodyTemplate->invokeArgs($mailform, array());
		$this->assertEquals('body1', $body);
		
		$mailform->setMailBodyTemplate('body2');
		$body = $getMailBodyTemplate->invokeArgs($mailform, array());
		$this->assertEquals('body2', $body);
	}
	
	public function testSetMailBodyTemplateByIndexAndGetMailBodyTemplateByIndex() {
		$getMailBodyTemplateByIndex = getMethod('MailForm', 'getMailBodyTemplateByIndex');
		$mailform = new MailForm();
		$mailform->setMailBodyTemplateByIndex(1,'body1');
		$body = $getMailBodyTemplateByIndex->invokeArgs($mailform, array(1));
		$this->assertEquals('body1', $body);
		
		$mailform->setMailBodyTemplateByIndex(1,'body2');
		$body = $getMailBodyTemplateByIndex->invokeArgs($mailform, array(1));
		$this->assertEquals('body2', $body);
		
		$mailform->setMailBodyTemplateByIndex(2,'body3');
		$body = $getMailBodyTemplateByIndex->invokeArgs($mailform, array(2));
		$this->assertEquals('body3', $body);
	}
	
	public function testSetMailSubjectTemplateAndGetMailSubjectTemplate() {
		$getMailSubjectTemplate = getMethod('MailForm', 'getMailSubjectTemplate');
		$mailform = new MailForm();
		$mailform->setMailSubjectTemplate('subject1');
		$subject = $getMailSubjectTemplate->invokeArgs($mailform, array());
		$this->assertEquals('subject1', $subject);
		
		$mailform->setMailSubjectTemplate('subject2');
		$subject = $getMailSubjectTemplate->invokeArgs($mailform, array());
		$this->assertEquals('subject2', $subject);
	}
	
	public function testSetMailSubjectTemplateByIndexAndGetMailSubjectTemplateByIndex() {
		$getMailSubjectTemplateByIndex = getMethod('MailForm', 'getMailSubjectTemplateByIndex');
		$mailform = new MailForm();
		$mailform->setMailSubjectTemplateByIndex(1,'subject1');
		$subject = $getMailSubjectTemplateByIndex->invokeArgs($mailform, array(1));
		$this->assertEquals('subject1', $subject);
		
		$mailform->setMailSubjectTemplateByIndex(1,'subject2');
		$subject = $getMailSubjectTemplateByIndex->invokeArgs($mailform, array(1));
		$this->assertEquals('subject2', $subject);
		
		$mailform->setMailSubjectTemplateByIndex(2,'subject3');
		$subject = $getMailSubjectTemplateByIndex->invokeArgs($mailform, array(2));
		$this->assertEquals('subject3', $subject);
	}
	
	public function testSetArrowTempFilesAndGetArrowTempFiles() {
		$getArrowTempFiles = getMethod('MailForm', 'getArrowTempFiles');
		$mailform = new MailForm();
		$mailform->setArrowTempFiles(true);
		$flag = $getArrowTempFiles->invokeArgs($mailform, array());
		$this->assertEquals(true, $flag);
		
		$mailform->setArrowTempFiles(false);
		$flag = $getArrowTempFiles->invokeArgs($mailform, array());
		$this->assertEquals(false, $flag);
	}
	
	public function testSetArrowTempFilesByIndexAndGetArrowTempFilesByIndex() {
		$getArrowTempFilesByIndex = getMethod('MailForm', 'getArrowTempFilesByIndex');
		$mailform = new MailForm();
		$mailform->setArrowTempFilesByIndex(1,true);
		$flag = $getArrowTempFilesByIndex->invokeArgs($mailform, array(1));
		$this->assertEquals(true, $flag);
		
		$mailform->setArrowTempFilesByIndex(1,false);
		$flag = $getArrowTempFilesByIndex->invokeArgs($mailform, array(1));
		$this->assertEquals(false, $flag);
		
		$mailform->setArrowTempFilesByIndex(2,true);
		$flag = $getArrowTempFilesByIndex->invokeArgs($mailform, array(2));
		$this->assertEquals(true, $flag);
	}
	
	public function testSettingTextParse() {
		$mailform = new MailForm();
		$this->assertNull($mailform->settingTextParse("test", 100000));
		
		$text = <<<EOT
from: from@example.com
EOT;
		$this->assertEquals(array('from' => 'from@example.com'), $mailform->settingTextParse($text, MailForm::MAILEFORM_FILE_TYPE_YAML));
		
		$text = <<<EOT
{"from":"from@example.com"}
EOT;
		$this->assertEquals(array('from' => 'from@example.com'), $mailform->settingTextParse($text, MailForm::MAILEFORM_FILE_TYPE_JSON));
	}
	
	public function testSetFormConfig() {
		$getArrowTempFilesByIndex = getMethod('MailForm', 'getArrowTempFilesByIndex');
		$getToArrayByIndex = getMethod('MailForm', 'getToArrayByIndex');
		$getFromArrayByIndex = getMethod('MailForm', 'getFromArrayByIndex');
		$getMailSubjectTemplateByIndex = getMethod('MailForm', 'getMailSubjectTemplateByIndex');
		$getMailBodyTemplateByIndex = getMethod('MailForm', 'getMailBodyTemplateByIndex');
		$ErrorCode = getProperty('MailForm', 'ErrorCode');
		$mailform = new MailForm();
		$this->assertFalse($mailform->setFormConfig("","",100000));
		$this->assertEquals(MailForm::ERROR_CODE_INVALID_FORM_CONFIG, $ErrorCode->getValue($mailform));
		
		// フォーマットに合わなくても、大丈夫
		$text = <<<EOT
{"from":"from@example.com"}
EOT;
		$this->assertTrue($mailform->setFormConfig($text,"", MailForm::MAILEFORM_FILE_TYPE_JSON));

		$text = <<<EOT
from: from@example.com
mailconf:
  to-admin:
    test: test 
EOT;
	$this->assertTrue($mailform->setFormConfig($text,"", MailForm::MAILEFORM_FILE_TYPE_YAML));
	
	
		// フォーマットのパターンテスト
		$text = <<<EOT
mailconf:
  to-admin:
    from: to-admin-from@example.com
    to: 
      -  to-admin-test1 <to-admin-test1@example.com>
      -  to-admin-test2@example.com
    subject: "to-admin-subject"
    arrow-temp-files: true
  to-user:
    from:  to-user-from@example.com
    to: to-user-test1@example.com
    body-template-path: "to-user-mail-body-template.txt"
    arrow-temp-files: false
EOT;
		$this->assertTrue($mailform->setFormConfig($text, dirname(__FILE__).'/conf'));

		$froms = $getFromArrayByIndex->invokeArgs($mailform, array('to-admin'));
		$this->assertCount(1, $froms);
		$this->assertArrayHasKey('to-admin-from@example.com', $froms);
		$this->assertEquals('', $froms['to-admin-from@example.com']);
		
		$subject = $getMailSubjectTemplateByIndex->invokeArgs($mailform, array('to-admin'));
		$this->assertEquals('to-admin-subject', $subject);
		
		$tos = $getToArrayByIndex->invokeArgs($mailform, array('to-admin'));
		$this->assertCount(2, $tos);
		$this->assertArrayHasKey('to-admin-test1@example.com', $tos);
		$this->assertEquals('to-admin-test1', $tos['to-admin-test1@example.com']);
		$this->assertArrayHasKey('to-admin-test2@example.com', $tos);
		$this->assertEquals('', $tos['to-admin-test2@example.com']);
		
		$this->assertTrue($getArrowTempFilesByIndex->invokeArgs($mailform, array('to-admin')));
		
		$froms = $getFromArrayByIndex->invokeArgs($mailform, array('to-user'));
		$this->assertCount(1, $froms);
		$this->assertArrayHasKey('to-user-from@example.com', $froms);
		$this->assertEquals('', $froms['to-user-from@example.com']);
		
		$tos = $getToArrayByIndex->invokeArgs($mailform, array('to-user'));
		$this->assertCount(1, $tos);
		$this->assertArrayHasKey('to-user-test1@example.com', $tos);
		$this->assertEquals('', $tos['to-user-test1@example.com']);
		
		$body = $getMailBodyTemplateByIndex->invokeArgs($mailform, array('to-user'));
		$this->assertEquals('to-user-mail-body-template', $body);
		
		$this->assertFalse($getArrowTempFilesByIndex->invokeArgs($mailform, array('to-user')));
	}
	
	
	public function testLoadFormConfig() {
		$mailform = $this->getMockBuilder('MailForm')
		->setMethods(array('setFormConfig'))
		->getMock();
		$filepath = dirname(__FILE__).'/conf/mailform_config.yml';
		
		$mailform->expects($this->once())
		->method('setFormConfig')
		->with(file_get_contents($filepath),'templateDirectoryPath',MailForm::MAILEFORM_FILE_TYPE_JSON)
		->will($this->returnValue(true));
		
		$mailform->loadFormConfig($filepath, 'templateDirectoryPath', MailForm::MAILEFORM_FILE_TYPE_JSON);
	}
	
	public function testParseMailAddressList() {
		$parseMailAddressList = getMethod('MailForm', 'parseMailAddressList');
		$mailform = new MailForm();
		
		$this->assertEquals(array('test@example.com', ''), $parseMailAddressList->invokeArgs($mailform, array('  test@example.com  ')));
		$this->assertEquals(array('test@example.com', ''), $parseMailAddressList->invokeArgs($mailform, array('<  test@example.com>')));
		$this->assertEquals(array('test@example.com', 'test'), $parseMailAddressList->invokeArgs($mailform, array('  test < test@example.com >')));
		$this->assertEquals(array('test < test@example.com', ''), $parseMailAddressList->invokeArgs($mailform, array('  test < test@example.com ')));
	}
	
	
	public function testGetStatusFromPostParam() {
		$getStatusFromPostParam = getMethod('MailForm', 'getStatusFromPostParam');
		$mailform = new MailForm();
		
		$this->assertEquals(MailForm::STATUS_NONE, $getStatusFromPostParam->invokeArgs($mailform, array(array())));
		$this->assertEquals(MailForm::STATUS_CONFIRMATION, $getStatusFromPostParam->invokeArgs($mailform, array(array('mailform-confirm-submit' => '1'))));
		$this->assertEquals(MailForm::STATUS_COMPLEATE, $getStatusFromPostParam->invokeArgs($mailform, array(array('mailform-complete-submit' => '1'))));
		$this->assertEquals(MailForm::STATUS_INPUT, $getStatusFromPostParam->invokeArgs($mailform, array(array('mailform-input-submit' => '1'))));
	}
	
	public function testReplaceGreekingText() {
		mb_internal_encoding("UTF-8");
		$mailform = new MailForm();
		
		$this->assertEquals('(株)', $mailform->replaceGreekingText('㈱'));
		mb_internal_encoding("UTF-7");
		$this->assertEquals('㈱', $mailform->replaceGreekingText('㈱'));
	}
	
	public function testSetRequestParameter() {
		$Status = getProperty('MailForm', 'Status');
		$RequestParam = getProperty('MailForm', 'RequestParam');
		mb_internal_encoding("UTF-8");
		$ErrorCode = getProperty('MailForm', 'ErrorCode');
		$mailform = new MailForm();
		$mailform->setRequestParameter(array());
		$this->assertEquals(MailForm::ERROR_CODE_INVALID_FORM_CONFIG, $ErrorCode->getValue($mailform));
		
		
		$filepath = dirname(__FILE__).'/conf/mailform_config_for_RequestParameter.yml';
		$mailform->loadFormConfig($filepath, 'templateDirectoryPath');
		
		$param = array('mailform-confirm-submit' => '1', 'username' => '㈱テスト', 'other'=>'other');
		$mailform->setRequestParameter($param);
		
		
		$requestParam = $RequestParam->getValue($mailform);
		$this->assertArrayHasKey('username', $requestParam);
		$this->assertEquals('(株)テスト', $requestParam['username']);
		$this->assertArrayNotHasKey('kana', $requestParam);
		$this->assertArrayNotHasKey('other', $requestParam);
		
		$status = $Status->getValue($mailform);
		$this->assertEquals(MailForm::STATUS_CONFIRMATION, $status);
	}
	
	public function testSetTemplatePage() {
		$setTemplatePage = getMethod('MailForm', 'setTemplatePage');
		$mailform = new MailForm();
		$filepath = dirname(__FILE__).'/conf/include_test.txt';
		ob_start();
		$setTemplatePage->invokeArgs($mailform, array($filepath));
		$output = ob_get_clean();
		$this->assertEquals('include_test:'.$filepath, $output);
		
		$TemplateURL = getProperty('MailForm', 'TemplateURL');
		$this->assertEquals($filepath, $TemplateURL->getValue($mailform));
		
	}
	
	public function testGetTemplateURL() {
		$setTemplatePage = getMethod('MailForm', 'setTemplatePage');
		$mailform = new MailForm();
		$filepath = dirname(__FILE__).'/conf/include_test.txt';
		$setTemplatePage->invokeArgs($mailform, array($filepath));
		$this->assertEquals($filepath, $mailform->getTemplateURL());
	}
	
	public function testShowInputPage() {
		$showInputPage = getMethod('MailForm', 'showInputPage');
		$url = 'STATUS_INPUT';
		
		$mailform = $this->getMockBuilder('MailForm')
		->setMethods(array('setTemplatePage'))
		->getMock();
		$mailform->expects($this->once())
		->method('setTemplatePage')
		->with($url);
		
		$mailform->InputURL = $url;
		
		$factory = new InnerSessionFactory();
		$mailform->setSessionFactory($factory);
		
		$showInputPage->invokeArgs($mailform, array());
		
		$session = $factory->getInstance();
		$this->assertEquals(MailForm::STATUS_INPUT, $session->getParamValue('mailform_status'));
	}
	
	public function testShowConfirmPage() {
		$showConfirmPage = getMethod('MailForm', 'showConfirmPage');
		$url = 'STATUS_CONFIRMATION';
		
		$mailform = $this->getMockBuilder('MailForm')
		->setMethods(array('setTemplatePage'))
		->getMock();
		$mailform->expects($this->once())
		->method('setTemplatePage')
		->with($url);
		
		$mailform->ConfirmURL = $url;
		
		$factory = new InnerSessionFactory();
		$mailform->setSessionFactory($factory);
		
		$showConfirmPage->invokeArgs($mailform, array());
		
		$session = $factory->getInstance();
		$this->assertEquals(MailForm::STATUS_CONFIRMATION, $session->getParamValue('mailform_status'));
	}
	
	public function testShowCompletePage() {
		$showCompletePage = getMethod('MailForm', 'showCompletePage');
		$url = 'STATUS_COMPLEATED';
		
		$mailform = $this->getMockBuilder('MailForm')
		->setMethods(array('setTemplatePage'))
		->getMock();
		$mailform->expects($this->once())
		->method('setTemplatePage')
		->with($url);
		
		$mailform->CompleteURL = $url;
		
		$factory = new InnerSessionFactory();
		$mailform->setSessionFactory($factory);
		
		$showCompletePage->invokeArgs($mailform, array());
		
		$session = $factory->getInstance();
		$this->assertEquals(MailForm::STATUS_COMPLEATED, $session->getParamValue('mailform_status'));
	}
	
	public function testShowErrorPage() {
		$showErrorPage = getMethod('MailForm', 'showErrorPage');
		$url = 'ERROR';
		
		$mailform = $this->getMockBuilder('MailForm')
		->setMethods(array('setTemplatePage'))
		->getMock();
		$mailform->expects($this->once())
		->method('setTemplatePage')
		->with($url);
		
		$mailform->ErrorURL = $url;
		
		$factory = new InnerSessionFactory();
		$mailform->setSessionFactory($factory);
		$session = $factory->getInstance();
		$session->setParam('mailform_status', MailForm::STATUS_COMPLEATED);
		
		$showErrorPage->invokeArgs($mailform, array());
		
		$this->assertEquals(MailForm::STATUS_COMPLEATED, $session->getParamValue('mailform_status'));
	}
	
	public function testIsCorrectAccess() {
		$Status = getProperty('MailForm', 'Status');
		$RequestParam = getProperty('MailForm', 'RequestParam');
		$isCorrectAccess = getMethod('MailForm', 'isCorrectAccess');
		$mailform = new MailForm();
		$mailform->loadFormConfig(dirname(__FILE__).'/conf/mailform_config.yml', dirname(__FILE__).'/conf/');
		$factory = new InnerSessionFactory();
		$mailform->setSessionFactory($factory);
		$session = $factory->getInstance();
		
		// セッション無し
		$session->setParam('item_values', NULL);
		$session->setParam('mailform_status', NULL);
		$Status->setValue($mailform, MailForm::STATUS_NONE);
		$this->assertTrue($isCorrectAccess->invokeArgs($mailform, array()));
		
		$Status->setValue($mailform, MailForm::STATUS_INPUT);
		$this->assertTrue($isCorrectAccess->invokeArgs($mailform, array()));
		
		$Status->setValue($mailform, MailForm::STATUS_CONFIRMATION);
		$this->assertFalse($isCorrectAccess->invokeArgs($mailform, array()));
		
		$Status->setValue($mailform, MailForm::STATUS_COMPLEATE);
		$this->assertFalse($isCorrectAccess->invokeArgs($mailform, array()));
		
		$Status->setValue($mailform, MailForm::STATUS_COMPLEATED);
		$this->assertTrue($isCorrectAccess->invokeArgs($mailform, array()));
		
		// セッションステータス STATUS_INPUT
		$session->setParam('item_values', NULL);
		$session->setParam('mailform_status', MailForm::STATUS_INPUT);
		$Status->setValue($mailform, MailForm::STATUS_NONE);
		$this->assertTrue($isCorrectAccess->invokeArgs($mailform, array()));
		
		$Status->setValue($mailform, MailForm::STATUS_INPUT);
		$this->assertTrue($isCorrectAccess->invokeArgs($mailform, array()));
		
		$Status->setValue($mailform, MailForm::STATUS_CONFIRMATION);
		$RequestParam->setValue($mailform, NULL);
		$this->assertFalse($isCorrectAccess->invokeArgs($mailform, array()));
		$mailform->setRequestParameter(array('aaa' => 'test'));
		$Status->setValue($mailform, MailForm::STATUS_CONFIRMATION);
		$this->assertTrue($isCorrectAccess->invokeArgs($mailform, array()));
		
		$Status->setValue($mailform, MailForm::STATUS_COMPLEATE);
		$this->assertFalse($isCorrectAccess->invokeArgs($mailform, array()));
		
		$Status->setValue($mailform, MailForm::STATUS_COMPLEATED);
		$this->assertTrue($isCorrectAccess->invokeArgs($mailform, array()));
		
		// セッションステータス STATUS_CONFIRMATION
		$session->setParam('item_values', NULL);
		$session->setParam('mailform_status', MailForm::STATUS_CONFIRMATION);
		$Status->setValue($mailform, MailForm::STATUS_NONE);
		$this->assertTrue($isCorrectAccess->invokeArgs($mailform, array()));
		
		$Status->setValue($mailform, MailForm::STATUS_INPUT);
		$this->assertTrue($isCorrectAccess->invokeArgs($mailform, array()));
		
		$RequestParam->setValue($mailform, NULL);
		$Status->setValue($mailform, MailForm::STATUS_CONFIRMATION);
		$this->assertFalse($isCorrectAccess->invokeArgs($mailform, array()));
		$mailform->setRequestParameter(array('aaa' => 'test'));
		$Status->setValue($mailform, MailForm::STATUS_CONFIRMATION);
		$this->assertTrue($isCorrectAccess->invokeArgs($mailform, array()));
		
		$Status->setValue($mailform, MailForm::STATUS_COMPLEATE);
		$RequestParam->setValue($mailform, NULL);
		$this->assertFalse($isCorrectAccess->invokeArgs($mailform, array()));
		
		$mailform->setRequestParameter(array('aaa' => 'test'));
		$Status->setValue($mailform, MailForm::STATUS_COMPLEATE);
		$this->assertFalse($isCorrectAccess->invokeArgs($mailform, array()));
		$session->setParam('item_values', array());
		$this->assertTrue($isCorrectAccess->invokeArgs($mailform, array()));
		
		$Status->setValue($mailform, MailForm::STATUS_COMPLEATED);
		$this->assertTrue($isCorrectAccess->invokeArgs($mailform, array()));
		
		// セッションステータス STATUS_COMPLEATED
		$session->setParam('mailform_status', MailForm::STATUS_COMPLEATED);
		$Status->setValue($mailform, MailForm::STATUS_NONE);
		$this->assertTrue($isCorrectAccess->invokeArgs($mailform, array()));
		$Status->setValue($mailform, MailForm::STATUS_INPUT);
		$this->assertTrue($isCorrectAccess->invokeArgs($mailform, array()));
		
		$Status->setValue($mailform, MailForm::STATUS_CONFIRMATION);
		$this->assertFalse($isCorrectAccess->invokeArgs($mailform, array()));
		
		$Status->setValue($mailform, MailForm::STATUS_COMPLEATE);
		$this->assertFalse($isCorrectAccess->invokeArgs($mailform, array()));
		
		$Status->setValue($mailform, MailForm::STATUS_COMPLEATED);
		$this->assertTrue($isCorrectAccess->invokeArgs($mailform, array()));
		
	}
	
	public function testSetParamToSession() {
	
		$setParamToSession = getMethod('MailForm', 'setParamToSession');
		$ErrorMessages = getProperty('MailForm', 'ErrorMessages');
		$mailform = new MailForm();
		$mailform->loadFormConfig(dirname(__FILE__).'/conf/mailform_config.yml', dirname(__FILE__).'/conf/');
		$factory = new InnerSessionFactory();
		$mailform->setSessionFactory($factory);
		$session = $factory->getInstance();
		
		$requestParameter = array('kind' => 1);
		$errorMessages = array('username', '「username」は必ず入力してください。');
		$ErrorMessages->setValue($mailform, $errorMessages);
		$mailform->setRequestParameter($requestParameter);
		
		$setParamToSession->invokeArgs($mailform, array());
		
		$this->assertEquals($errorMessages, $session->getParamValue('error_messages'));
		$this->assertEquals($requestParameter, $session->getParamValue('item_values'));
		
		
		$session->setParam('item_values', array('kind' => 2, 'temp' => 'test.pdf'));
		$ErrorMessages->setValue($mailform, $errorMessages);
		$mailform->setRequestParameter($requestParameter);
		
		$setParamToSession->invokeArgs($mailform, array());
		
		$this->assertEquals($errorMessages, $session->getParamValue('error_messages'));
		$sessionItemValues = $session->getParamValue('item_values');
		$this->assertEquals('test.pdf', $sessionItemValues['temp']);
	
	}
	
	public function testLoadParamFromSession() {
		$ErrorMessages = getProperty('MailForm', 'ErrorMessages');
		$RequestParam = getProperty('MailForm', 'RequestParam');
		$loadParamFromSession = getMethod('MailForm', 'loadParamFromSession');
		$mailform = new MailForm();
		$factory = new InnerSessionFactory();
		$mailform->setSessionFactory($factory);
		$session = $factory->getInstance();
		
		$requestParam = array('kind' => 2, 'temp' => 'test.pdf');
		$errorMessages = array('username', '「username」は必ず入力してください。');
		$session->setParam('item_values', $requestParam);
		$session->setParam('error_messages', $errorMessages);
		
		$loadParamFromSession->invokeArgs($mailform, array());
		
		$this->assertEquals($requestParam, $RequestParam->getValue($mailform));
		$this->assertEquals($errorMessages, $ErrorMessages->getValue($mailform));
	}
	
	public function testSetTempDirPathToSession() {
		$setTempDirPathToSession = getMethod('MailForm', 'setTempDirPathToSession');
		$TmpDirectryPath = getProperty('MailForm', 'TmpDirectryPath');
	
		$mailform = new MailForm();
		$factory = new InnerSessionFactory();
		$mailform->setSessionFactory($factory);
		$session = $factory->getInstance();
		
		$date = new DateTime();
		$time = microtime();
		$setTempDirPathToSession->invokeArgs($mailform, array());
		
		$tmpDirectryPath = $TmpDirectryPath->getValue($mailform).$date->format('YmdHis').substr(explode('.',explode(' ',$time)[0])[1],0,3).'/';
		$this->assertEquals($tmpDirectryPath, $session->getParamValue('mailform_temp_dir_path'));
	}
	
	public function testGetTempDirPathFromSession() {
		$_SESSION=array();
		$getTempDirPathFromSession = getMethod('MailForm', 'getTempDirPathFromSession');
		$TmpDirectryPath = getProperty('MailForm', 'TmpDirectryPath');
		
		$mailform = $this->getMockBuilder('MailForm')
		->setMethods(array('setTempDirPathToSession'))
		->getMock();
		$mailform->expects($this->once())
		->method('setTempDirPathToSession');
		
		$factory = new InnerSessionFactory();
		$mailform->setSessionFactory($factory);
		$session = $factory->getInstance();
		
		$getTempDirPathFromSession->invokeArgs($mailform, array());
		
		$mailform = new MailForm();
		$mailform->setSessionFactory($factory);
		$session->setParam('mailform_temp_dir_path', 'session_mailform_temp_dir_path');
		$this->assertEquals('session_mailform_temp_dir_path', $getTempDirPathFromSession->invokeArgs($mailform, array()));
	}
	
	public function testMoveUploadFile() {
		// skip テストのやりかたを考える
	}
	
	public function testUnsetSession() {
		$_SESSION=array();
		$getTempDirPathFromSession = getMethod('MailForm', 'getTempDirPathFromSession');
		$unsetSession = getMethod('MailForm', 'unsetSession');
		$TmpDirectryPath = getProperty('MailForm', 'TmpDirectryPath');
		
		$mailform = new MailForm();
		$mailform->TmpDirectryPath = dirname(__FILE__).'/test_tmp/';
		$unsetSession->invokeArgs($mailform, array());
		$this->assertFalse(isset($_SESSION['item_values']));
		$this->assertFalse(isset($_SESSION['mailform_temp_dir_path']));
		
		$filebasepath = $getTempDirPathFromSession->invokeArgs($mailform, array());
		if (!is_dir($mailform->TmpDirectryPath)) mkdir($mailform->TmpDirectryPath, 0700, true);
		if (!is_dir($filebasepath)) mkdir($filebasepath, 0700, true);
		
		$this->assertTrue(file_exists($filebasepath));
		$unsetSession->invokeArgs($mailform, array());
		system("rm -rf {$mailform->TmpDirectryPath}");
	}
	
	public function testExecute() {
		$_SERVER['SCRIPT_NAME'] = 'SCRIPT_NAME';
		$ErrorCode = getProperty('MailForm', 'ErrorCode');
		$ErrorMessages = getProperty('MailForm', 'ErrorMessages');
		$RequestParam = getProperty('MailForm', 'RequestParam');
		$FormConfig = getProperty('MailForm', 'FormConfig');
		$Status = getProperty('MailForm', 'Status');
		
		// IncludeTemplateURL=true
		$mailform = $this->getMockBuilder('MailForm')
		->setMethods(array('main'))
		->getMock();
		$mailform->expects($this->once())
		->method('main');
		
		$mailform->IncludeTemplateURL = true;
		$mailform->execute();
		
		
		// IncludeTemplateURL=false
		// エラーページ表示の場合
		$mailform = $this->getMockBuilder('MailForm')
		->setMethods(array('loadParamFromSession', 'main'))
		->getMock();
		$mailform->expects($this->once())
		->method('loadParamFromSession');
		$mailform->expects($this->never())
		->method('main');
		
		$mailform->IncludeTemplateURL = false;
		$mailform->ErrorURL = $_SERVER['SCRIPT_NAME'];
		$mailform->execute();
		
		// IncludeTemplateURL=false
		// 完了画面表示の場合
		$mailform = $this->getMockBuilder('MailForm')
		->setMethods(array('showCompletePage', 'main'))
		->getMock();
		$mailform->expects($this->once())
		->method('showCompletePage');
		$mailform->expects($this->never())
		->method('main');
		
		$mailform->IncludeTemplateURL = false;
		$mailform->CompleteURL = $_SERVER['SCRIPT_NAME'];
		$Status->setValue($mailform, MailForm::STATUS_COMPLEATED);
		$mailform->execute();
		
		// IncludeTemplateURL=false
		// 完了画面表示の場合
		$mailform = $this->getMockBuilder('MailForm')
		->setMethods(array('showCompletePage', 'main'))
		->getMock();
		$mailform->expects($this->once())
		->method('showCompletePage');
		$mailform->expects($this->never())
		->method('main');
		
		$mailform->IncludeTemplateURL = false;
		$mailform->CompleteURL = $_SERVER['SCRIPT_NAME'];
		$Status->setValue($mailform, MailForm::STATUS_COMPLEATED);
		$mailform->execute();
		
		// IncludeTemplateURL=false
		// TemplateURLが変わった場合
		$mailform = $this->getMockBuilder('MailForm')
		->setMethods(array('main', 'getTemplateURL', 'redirect'))
		->getMock();
		$mailform->expects($this->once())
		->method('main');
		$mailform->expects($this->exactly(2))
		->method('getTemplateURL')
		->will($this->returnValue('other_getTemplateURL'));
		$mailform->expects($this->once())
		->method('redirect')
		->with('other_getTemplateURL');
		
		$mailform->IncludeTemplateURL = false;
		$Status->setValue($mailform, MailForm::STATUS_COMPLEATED);
		$mailform->execute();
		
		// IncludeTemplateURL=false
		// TemplateURLが変わらなかった場合
		$mailform = $this->getMockBuilder('MailForm')
		->setMethods(array('main', 'getTemplateURL', 'redirect'))
		->getMock();
		$mailform->expects($this->once())
		->method('main');
		$mailform->expects($this->once())
		->method('getTemplateURL')
		->will($this->returnValue($_SERVER['SCRIPT_NAME']));
		$mailform->expects($this->never())
		->method('redirect');
		
		$mailform->IncludeTemplateURL = false;
		$Status->setValue($mailform, MailForm::STATUS_COMPLEATED);
		$mailform->execute();
	}
		
	public function testMain() {
		$_SERVER['REQUEST_URI'] = 'REQUEST_URI';
		$ErrorCode = getProperty('MailForm', 'ErrorCode');
		$ErrorMessages = getProperty('MailForm', 'ErrorMessages');
		$main = getMethod('MailForm', 'main');
		$RequestParam = getProperty('MailForm', 'RequestParam');
		$FormConfig = getProperty('MailForm', 'FormConfig');
		$Status = getProperty('MailForm', 'Status');
		
		// 設定エラー
		$mailform = $this->getMockBuilder('MailForm')
		->setMethods(array('showErrorPage'))
		->getMock();
		$mailform->expects($this->once())
		->method('showErrorPage');
		
		$main->invokeArgs($mailform, array());
		$this->assertEquals(MailForm::ERROR_CODE_INVALID_FORM_CONFIG, $ErrorCode->getValue($mailform));
		$this->assertEquals('システムエラーが発生しました。', $ErrorMessages->getValue($mailform)['base']);
		
		// 不正アクセス
		$mailform = $this->getMockBuilder('MailForm')
		->setMethods(array('isCorrectAccess', 'unsetSession', 'redirect'))
		->getMock();
		$mailform->expects($this->once())
		->method('isCorrectAccess')
		->will($this->returnValue(false));
		$mailform->expects($this->once())
		->method('unsetSession');
		$mailform->expects($this->once())
		->method('redirect')
		->with($_SERVER['REQUEST_URI']);
		
		$FormConfig->setValue($mailform, array('items' => array()));
		$main->invokeArgs($mailform, array());
		
		// 入力ページ表示
		$mailform = $this->getMockBuilder('MailForm')
		->setMethods(array('isCorrectAccess', 'unsetSession', 'showInputPage'))
		->getMock();
		$mailform->expects($this->once())
		->method('isCorrectAccess')
		->will($this->returnValue(true));
		$mailform->expects($this->once())
		->method('unsetSession');
		$mailform->expects($this->once())
		->method('showInputPage');
		
		$FormConfig->setValue($mailform, array('items' => array()));
		$Status->setValue($mailform, MailForm::STATUS_NONE);
		$main->invokeArgs($mailform, array());
		
		$mailform = $this->getMockBuilder('MailForm')
		->setMethods(array('isCorrectAccess', 'showInputPage'))
		->getMock();
		$mailform->expects($this->once())
		->method('isCorrectAccess')
		->will($this->returnValue(true));
		$mailform->expects($this->once())
		->method('showInputPage');
		
		$FormConfig->setValue($mailform, array('items' => array()));
		$Status->setValue($mailform, MailForm::STATUS_INPUT);
		$main->invokeArgs($mailform, array());
		
		// 確認画面表示
		$mailform = $this->getMockBuilder('MailForm')
		->setMethods(array('isCorrectAccess', 'moveUploadFile', 'checkRequestParams', 'setParamToSession', 'showConfirmPage'))
		->getMock();
		$mailform->expects($this->once())
		->method('isCorrectAccess')
		->will($this->returnValue(true));
		$mailform->expects($this->once())
		->method('moveUploadFile');
		$mailform->expects($this->once())
		->method('checkRequestParams');
		$mailform->expects($this->once())
		->method('setParamToSession');
		$mailform->expects($this->once())
		->method('showConfirmPage');
		
		$FormConfig->setValue($mailform, array('items' => array()));
		$Status->setValue($mailform, MailForm::STATUS_CONFIRMATION);
		$main->invokeArgs($mailform, array());
		
		// 完了画面表示
		$mailform = $this->getMockBuilder('MailForm')
		->setMethods(array('isCorrectAccess', 'loadParamFromSession', 'checkRequestParams', 'sendMail', 'showCompletePage'))
		->getMock();
		$mailform->expects($this->once())
		->method('isCorrectAccess')
		->will($this->returnValue(true));
		$mailform->expects($this->once())
		->method('loadParamFromSession');
		$mailform->expects($this->once())
		->method('checkRequestParams')
		->will($this->returnValue(true));
		$mailform->expects($this->once())
		->method('sendMail')
		->will($this->returnValue(true));
		$mailform->expects($this->once())
		->method('showCompletePage');
		
		$FormConfig->setValue($mailform, array('items' => array()));
		$Status->setValue($mailform, MailForm::STATUS_COMPLEATE);
		$main->invokeArgs($mailform, array());
		
		// エラー画面表示、checkRequestParamsでエラー
		$mailform = $this->getMockBuilder('MailForm')
		->setMethods(array('isCorrectAccess', 'loadParamFromSession', 'sendMail', 'setParamToSession', 'showErrorPage'))
		->getMock();
		$mailform->expects($this->once())
		->method('isCorrectAccess')
		->will($this->returnValue(true));
		$mailform->expects($this->once())
		->method('loadParamFromSession');
		$mailform->expects($this->never())
		->method('sendMail')
		->will($this->returnValue(true));
		$mailform->expects($this->once())
		->method('setParamToSession');
		$mailform->expects($this->once())
		->method('showErrorPage');
		
		$FormConfig->setValue($mailform, array('items' => array('key' => array())));
		$Status->setValue($mailform, MailForm::STATUS_COMPLEATE);
		$main->invokeArgs($mailform, array());
	}
	
	public function testGetValue() {
		$RequestParam = getProperty('MailForm', 'RequestParam');
		$mailform = new MailForm();
		
		$RequestParam->setValue($mailform, array());
		$this->assertEquals('', $mailform->getValue('key'));
		
		$RequestParam->setValue($mailform, array('key' => 'value'));
		$this->assertEquals('value', $mailform->getValue('key'));
	}
	
	public function testGetSelectValues() {
		$FormConfig = getProperty('MailForm', 'FormConfig');
		$mailform = new MailForm();
		
		$FormConfig->setValue($mailform, array());
		$this->assertEquals('', $mailform->getSelectValues('key'));
		
		$selectValues = array('1' => 'option1', '2' => 'option2');
		$FormConfig->setValue($mailform, array('items' => array('key' => array('selectvalues' => $selectValues))));
		$this->assertEquals($selectValues, $mailform->getSelectValues('key'));
	}
	
	public function testGetSelectValue() {
		$FormConfig = getProperty('MailForm', 'FormConfig');
		$mailform = new MailForm();
		
		$FormConfig->setValue($mailform, array());
		$this->assertEquals('', $mailform->getSelectValue('key', '1'));
		
		$selectValues = array('1' => 'option1', '2' => 'option2');
		$FormConfig->setValue($mailform, array('items' => array('key' => array('selectvalues' => $selectValues))));
		$this->assertEquals('option1', $mailform->getSelectValue('key', '1'));
	}
	
	public function testGetSelectOptionHtml() {
		$FormConfig = getProperty('MailForm', 'FormConfig');
		$mailform = new MailForm();
		
		$FormConfig->setValue($mailform, array());
		$this->assertEquals('', $mailform->getSelectOptionHtml('key'));
		
		$selectValues = array('1' => 'option1', '2' => 'option2');
		$FormConfig->setValue($mailform, array('items' => array('key' => array('selectvalues' => $selectValues))));
		$this->assertEquals("<option value=\"1\">option1</option>\n<option value=\"2\">option2</option>\n", $mailform->getSelectOptionHtml('key'));
		
		$mailform = $this->getMockBuilder('MailForm')
		->setMethods(array('getValue'))
		->getMock();
		$mailform->expects($this->once())
		->method('getValue')
		->with('key')
		->will($this->returnValue('2'));
		
		$FormConfig->setValue($mailform, array('items' => array('key' => array('selectvalues' => $selectValues))));
		$this->assertEquals("<option value=\"1\">option1</option>\n<option value=\"2\" selected=\"selected\" >option2</option>\n", $mailform->getSelectOptionHtml('key'));
	}
	
	public function testIsExistKeyInSelectValues() {
		$FormConfig = getProperty('MailForm', 'FormConfig');
		$mailform = new MailForm();
		
		$selectValues = array('1' => 'option1', '2' => 'option2');
		$FormConfig->setValue($mailform, array('items' => array('key' => array('selectvalues' => $selectValues))));
		
		$this->assertFalse( $mailform->isExistKeyInSelectValues('key', '10'));
		$this->assertTrue( $mailform->isExistKeyInSelectValues('key', '1'));
	}
	
	public function testGetSelectedValue() {
		$FormConfig = getProperty('MailForm', 'FormConfig');
		$mailform = new MailForm();
		$this->assertEquals('', $mailform->getSelectedValue('key'));
		
		
		$mailform = $this->getMockBuilder('MailForm')
		->setMethods(array('getValue'))
		->getMock();
		$mailform->expects($this->once())
		->method('getValue')
		->with('key')
		->will($this->returnValue('2'));
		$selectValues = array('empty' => 'empty', '1' => 'option1', '2' => 'option2');
		$FormConfig->setValue($mailform, array('items' => array('key' => array('selectvalues' => $selectValues))));
		$this->assertEquals('option2', $mailform->getSelectedValue('key'));
	}
	
	public function testGetMultiSelectedValue() {
		$FormConfig = getProperty('MailForm', 'FormConfig');
		$mailform = new MailForm();
	
		$this->assertEquals('', $mailform->getMultiSelectedValue('key'));
		$selectValues = array('empty' => 'empty', '1' => 'option1', '2' => 'option2');
		
		$FormConfig->setValue($mailform, array('items' => array('key' => array('selectvalues' => $selectValues))));
		$this->assertEquals('', $mailform->getMultiSelectedValue('key'));
		
		$mailform = $this->getMockBuilder('MailForm')
		->setMethods(array('getValue'))
		->getMock();
		$mailform->expects($this->once())
		->method('getValue')
		->with('key')
		->will($this->returnValue(array('1','2')));
		
		$FormConfig->setValue($mailform, array('items' => array('key' => array('selectvalues' => $selectValues))));
		$this->assertEquals('option1／option2', $mailform->getMultiSelectedValue('key'));
	}
	
	public function testIsSelectedValue() {
		$FormConfig = getProperty('MailForm', 'FormConfig');
		$mailform = new MailForm();
		
		$this->assertTrue($mailform->isSelectedValue('key', '1', true));
		$this->assertFalse($mailform->isSelectedValue('key', '1'));
		
		$mailform = $this->getMockBuilder('MailForm')
		->setMethods(array('getValue'))
		->getMock();
		$mailform->expects($this->any())
		->method('getValue')
		->with('key')
		->will($this->returnValue('1'));
		
		$selectValues = array('1' => 'option1', '2' => 'option2', '3' => 'option3');
		$FormConfig->setValue($mailform, array('items' => array('key' => array('selectvalues' => $selectValues))));
		$this->assertTrue($mailform->isSelectedValue('key', '1'));
		$this->assertFalse($mailform->isSelectedValue('key', '2'));
		
		$mailform->expects($this->any())
		->method('getValue')
		->with('key')
		->will($this->returnValue('1','2'));
		$this->assertTrue($mailform->isSelectedValue('key', '1'));
		$this->assertFalse($mailform->isSelectedValue('key', '3'));
		
	}
	
	public function testIsPlusNumber() {
		$isPlusNumber = getMethod('MailForm', 'isPlusNumber');
		$mailform = new MailForm();
		$this->assertTrue($isPlusNumber->invokeArgs($mailform, array(1)));
		$this->assertTrue($isPlusNumber->invokeArgs($mailform, array(0)));
		$this->assertTrue($isPlusNumber->invokeArgs($mailform, array('5')));
		$this->assertTrue($isPlusNumber->invokeArgs($mailform, array(10)));
		$this->assertTrue($isPlusNumber->invokeArgs($mailform, array(11111)));
		$this->assertFalse($isPlusNumber->invokeArgs($mailform, array(-1)));
		$this->assertFalse($isPlusNumber->invokeArgs($mailform, array('a')));
	}
	
	public function testIsEmailAddress() {
		$isEmailAddress = getMethod('MailForm', 'isEmailAddress');
		$mailform = new MailForm();
		$this->assertTrue($isEmailAddress->invokeArgs($mailform, array('test@samle.com')));
		$this->assertTrue($isEmailAddress->invokeArgs($mailform, array('test@samle.com')));
		$this->assertFalse($isEmailAddress->invokeArgs($mailform, array('test@samle')));
		$this->assertFalse($isEmailAddress->invokeArgs($mailform, array('')));
	}
	
	public function testIsKana() {
		$isKana = getMethod('MailForm', 'isKana');
		$mailform = new MailForm();
		$this->assertFalse($isKana->invokeArgs($mailform, array('てすと')));
		$this->assertTrue($isKana->invokeArgs($mailform, array('テスト')));
		$this->assertFalse($isKana->invokeArgs($mailform, array('漢字')));
		$this->assertFalse($isKana->invokeArgs($mailform, array('123')));
		$this->assertFalse($isKana->invokeArgs($mailform, array('abc')));
	}
	
	public function testIsYomi() {
		$isYomi = getMethod('MailForm', 'isYomi');
		$mailform = new MailForm();
		$this->assertTrue($isYomi->invokeArgs($mailform, array('てすと')));
		$this->assertFalse($isYomi->invokeArgs($mailform, array('テスト')));
		$this->assertFalse($isYomi->invokeArgs($mailform, array('漢字')));
		$this->assertFalse($isYomi->invokeArgs($mailform, array('123')));
		$this->assertFalse($isYomi->invokeArgs($mailform, array('abc')));
	}
	
	public function testIsTel() {
		$isTel = getMethod('MailForm', 'isTel');
		$mailform = new MailForm();
		$this->assertTrue($isTel->invokeArgs($mailform, array('+81-1234-1234')));
		$this->assertTrue($isTel->invokeArgs($mailform, array('03-1234-1234')));
		$this->assertTrue($isTel->invokeArgs($mailform, array('123456789')));
		$this->assertTrue($isTel->invokeArgs($mailform, array('------')));
		$this->assertFalse($isTel->invokeArgs($mailform, array('abcde')));
		$this->assertFalse($isTel->invokeArgs($mailform, array('てすと')));
	}
	
	public function testConvToTwoByte() {
		$convToTwoByte = getMethod('MailForm', 'convToTwoByte');
		$mailform = new MailForm();
		$this->assertEquals('１２３', $convToTwoByte->invokeArgs($mailform, array('123')));
		$this->assertEquals('ＡＢＣ', $convToTwoByte->invokeArgs($mailform, array('ABC')));
		$this->assertEquals('テスト', $convToTwoByte->invokeArgs($mailform, array('ﾃｽﾄ')));
		$this->assertEquals('－？＜＞＃', $convToTwoByte->invokeArgs($mailform, array('-?<>#')));
	}
	
	public function testConvToOneByte() {
		$convToOneByte = getMethod('MailForm', 'convToOneByte');
		$mailform = new MailForm();
		$this->assertEquals('123', $convToOneByte->invokeArgs($mailform, array('１２３')));
		$this->assertEquals('ABC', $convToOneByte->invokeArgs($mailform, array('ＡＢＣ')));
		$this->assertEquals('テスト', $convToOneByte->invokeArgs($mailform, array('テスト')));
		$this->assertEquals('-?<>#', $convToOneByte->invokeArgs($mailform, array('ー？＜＞＃')));
	}
	
	public function testConvToPlus() {
		$convToPlus = getMethod('MailForm', 'convToPlus');
		$mailform = new MailForm();
		$this->assertEquals('8112341234', $convToPlus->invokeArgs($mailform, array('+81-1234-1234')));
		$this->assertEquals('0312341234', $convToPlus->invokeArgs($mailform, array('03-1234-1234')));
		$this->assertEquals('123456789', $convToPlus->invokeArgs($mailform, array('123456789')));
		$this->assertEquals('', $convToPlus->invokeArgs($mailform, array('------')));
	}
	
	public function testConvertRequestParam() {
		$convertRequestParam = getMethod('MailForm', 'convertRequestParam');
		{
			$mailform = $this->getMockBuilder('MailForm')
			->setMethods(array('convToTwoByte'))
			->getMock();
			$mailform->expects($this->once())
			->method('convToTwoByte')
			->with('value')
			->will($this->returnValue('return'));
			
			$this->assertEquals('return', $convertRequestParam->invokeArgs($mailform, array('two-byte','value')));
		}
		{
			$mailform = $this->getMockBuilder('MailForm')
			->setMethods(array('convToOneByte'))
			->getMock();
			$mailform->expects($this->once())
			->method('convToOneByte')
			->with('value')
			->will($this->returnValue('return'));
			
			$this->assertEquals('return', $convertRequestParam->invokeArgs($mailform, array('one-byte','value')));
		}
		{
			$mailform = $this->getMockBuilder('MailForm')
			->setMethods(array('convToPlus'))
			->getMock();
			$mailform->expects($this->once())
			->method('convToPlus')
			->with('value')
			->will($this->returnValue('return'));
			
			$this->assertEquals('return', $convertRequestParam->invokeArgs($mailform, array('plus','value')));
		}
		{
			$mailform = new MailForm();
			
			$this->assertEquals('testtest', $convertRequestParam->invokeArgs($mailform, array('none','testtest')));
		}
	}
	
	public function testCheckRequestParams() {
		$checkRequestParams = getMethod('MailForm', 'checkRequestParams');
		$FormConfig = getProperty('MailForm', 'FormConfig');
		
		$mailform = $this->getMockBuilder('MailForm')
		->setMethods(array('checkRequestParam'))
		->getMock();
		$mailform->expects($this->once())
		->method('checkRequestParam')
		->with(true, 'key1', 'key1_array')
		->will($this->returnValue(true));
		
		$FormConfig->setValue($mailform, array('items' => array('key1' => 'key1_array')));
		$this->assertTrue($checkRequestParams->invokeArgs($mailform, array()));
		
		$mailform = $this->getMockBuilder('MailForm')
		->setMethods(array('checkRequestParam'))
		->getMock();
		$mailform->expects($this->once())
		->method('checkRequestParam')
		->with(true, 'key1', 'key1_array')
		->will($this->returnValue(false));
		
		$FormConfig->setValue($mailform, array('items' => array('key1' => 'key1_array')));
		$this->assertFalse($checkRequestParams->invokeArgs($mailform, array()));
	}
	
	public function testCheckRequestParam() {
		$getTempDirPathFromSession = getMethod('MailForm', 'getTempDirPathFromSession');
		$checkRequestParam = getMethod('MailForm', 'checkRequestParam');
		$RequestParam = getProperty('MailForm', 'RequestParam');
		$FormConfig = getProperty('MailForm', 'FormConfig');
		$ErrorCode = getProperty('MailForm', 'ErrorCode');
		$ErrorMessages = getProperty('MailForm', 'ErrorMessages');
		
		$mailform = new MailForm();
		
		// valuesが空の場合
		$ret = true;
		$values = array();
		$this->assertFalse($checkRequestParam->invokeArgs($mailform, array(&$ret, 'key', &$values)));
		$this->assertEquals(MailForm::ERROR_CODE_INVALID_FORM_CONFIG, $ErrorCode->getValue($mailform));
		
		// agreementで、入力が空の時
		$mailform = new MailForm();
		$ret = true;
		$values = array('value' => 'agreement', 'empty' => false, 'title'=> 'title');
		$this->assertTrue($checkRequestParam->invokeArgs($mailform, array(&$ret, 'key', &$values)));
		$this->assertFalse($ret);
		$this->assertEquals(MailForm::ERROR_CODE_NO_AGREEMENT, $ErrorCode->getValue($mailform));
		$this->assertEquals('同意していただく必要があります。', $ErrorMessages->getValue($mailform)['key']);
		
		// stringで、入力が空の時
		$mailform = new MailForm();
		$ret = true;
		$values = array('value' => 'string', 'empty' => false, 'title'=> 'title');
		$this->assertTrue($checkRequestParam->invokeArgs($mailform, array(&$ret, 'key', &$values)));
		$this->assertFalse($ret);
		$this->assertEquals(MailForm::ERROR_CODE_NOT_NULL_PARAM, $ErrorCode->getValue($mailform));
		$this->assertEquals('「title」は必ず入力してください。', $ErrorMessages->getValue($mailform)['key']);
		
		// selectで、入力が空の時
		$mailform = new MailForm();
		$ret = true;
		$values = array('value' => 'select', 'empty' => false, 'title'=> 'title');
		$this->assertTrue($checkRequestParam->invokeArgs($mailform, array(&$ret, 'key', &$values)));
		$this->assertFalse($ret);
		$this->assertEquals(MailForm::ERROR_CODE_NEED_SELECT_PARAM, $ErrorCode->getValue($mailform));
		$this->assertEquals('「title」は必ず選択してください。', $ErrorMessages->getValue($mailform)['key']);
		
		// multi-selectで、入力が空の時
		$mailform = new MailForm();
		$ret = true;
		$values = array('value' => 'multi-select', 'empty' => false, 'title'=> 'title');
		$this->assertTrue($checkRequestParam->invokeArgs($mailform, array(&$ret, 'key', &$values)));
		$this->assertFalse($ret);
		$this->assertEquals(MailForm::ERROR_CODE_NEED_SELECT_PARAM, $ErrorCode->getValue($mailform));
		$this->assertEquals('「title」は必ず選択してください。', $ErrorMessages->getValue($mailform)['key']);
		
		// 値に数字が入ってきた時
		$mailform = new MailForm();
		$ret = true;
		$RequestParam->setValue($mailform, array('key' => 1));
		$values = array('value' => 'string', 'empty' => false, 'title'=> 'title');
		$this->assertTrue($checkRequestParam->invokeArgs($mailform, array(&$ret, 'key', &$values)));
		$this->assertFalse($ret);
		$this->assertEquals(MailForm::ERROR_CODE_INVALID_PARAM, $ErrorCode->getValue($mailform));
		$this->assertEquals('「title」の値は入力できない値です。', $ErrorMessages->getValue($mailform)['key']);
		
		// convのテスト
		$mailform = new MailForm();
		$ret = true;
		$RequestParam->setValue($mailform, array('key' => '１２３４５'));
		$values = array('value' => 'plus', 'empty' => true, 'title'=> 'title', 'conv'=> 'plus');
		$this->assertTrue($checkRequestParam->invokeArgs($mailform, array(&$ret, 'key', &$values)));
		$this->assertTrue($ret);
		$this->assertEquals('12345', $RequestParam->getValue($mailform)['key']);
		
		// sameで、値が違う時
		$mailform = new MailForm();
		$ret = true;
		$RequestParam->setValue($mailform, array('key' => 'value', 'same_check' => 'value2'));
		$values = array('value' => 'string', 'empty' => false, 'title'=> 'title', 'same' => 'same_check');
		$FormConfig->setValue($mailform, array('items' => array('same_check' => array('title'=> 'same_check_title'))));
		$this->assertTrue($checkRequestParam->invokeArgs($mailform, array(&$ret, 'key', &$values)));
		$this->assertFalse($ret);
		$this->assertEquals(MailForm::ERROR_CODE_NOT_SAME_PARAM, $ErrorCode->getValue($mailform));
		$this->assertEquals('「title」は「same_check_title」と同じ値を入力してください。', $ErrorMessages->getValue($mailform)['key']);
		
		// sameで、値が同じ場合
		$mailform = new MailForm();
		$ret = true;
		$RequestParam->setValue($mailform, array('key' => 'value', 'same_check' => 'value'));
		$values = array('value' => 'string', 'empty' => false, 'title'=> 'title', 'same' => 'same_check');
		$this->assertTrue($checkRequestParam->invokeArgs($mailform, array(&$ret, 'key', &$values)));
		$this->assertTrue($ret);
		
		// maxlengthのチェック、maxlengthと同じ長さ
		$mailform = new MailForm();
		$ret = true;
		$RequestParam->setValue($mailform, array('key' => '文字列'));
		$values = array('value' => 'string', 'empty' => false, 'title'=> 'title', 'maxlength' => 3);
		$this->assertTrue($checkRequestParam->invokeArgs($mailform, array(&$ret, 'key', &$values)));
		$this->assertTrue($ret);
		
		// maxlengthのチェック、maxlengthより長い
		$RequestParam->setValue($mailform, array('key' => '長い文字列'));
		$values = array('value' => 'string', 'empty' => false, 'title'=> 'title', 'maxlength' => 3);
		$this->assertTrue($checkRequestParam->invokeArgs($mailform, array(&$ret, 'key', &$values)));
		$this->assertFalse($ret);
		$this->assertEquals(MailForm::ERROR_CODE_OVER_MAXLENGTH, $ErrorCode->getValue($mailform));
		$this->assertEquals('「title」は、3文字以内でご記入ください。', $ErrorMessages->getValue($mailform)['key']);
		
		// value=selectで、selectvaluesがない場合
		$mailform = new MailForm();
		$ret = true;
		
		$RequestParam->setValue($mailform, array('key' => '1'));
		$selectValues = array('1' => 'option1', '2' => 'option2', '3' => 'option3');
		$values = array('value' => 'select', 'empty' => false, 'title'=> 'title');
		$this->assertFalse($checkRequestParam->invokeArgs($mailform, array(&$ret, 'key', &$values)));
		$this->assertTrue($ret);
		$this->assertEquals(MailForm::ERROR_CODE_INVALID_FORM_CONFIG, $ErrorCode->getValue($mailform));
		$this->assertEquals('システムエラーが発生しました。', $ErrorMessages->getValue($mailform)['base']);
		
		// value=selectで、入力が不正な値だった場合
		$mailform = new MailForm();
		$ret = true;
		
		$RequestParam->setValue($mailform, array('key' => '5'));
		$values = array('value' => 'select', 'empty' => false, 'title'=> 'title', 'selectvalues' => $selectValues);
		$this->assertTrue($checkRequestParam->invokeArgs($mailform, array(&$ret, 'key', &$values)));
		$this->assertFalse($ret);
		$this->assertEquals(MailForm::ERROR_CODE_INVALID_SELECT_PARAM, $ErrorCode->getValue($mailform));
		$this->assertEquals('「title」に不正な値が入力されています。', $ErrorMessages->getValue($mailform)['key']);
		
		// value=selectで、入力が正常だった場合
		$mailform = new MailForm();
		$ret = true;
		
		$RequestParam->setValue($mailform, array('key' => '1'));
		$values = array('value' => 'select', 'empty' => false, 'title'=> 'title', 'selectvalues' => $selectValues);
		$this->assertTrue($checkRequestParam->invokeArgs($mailform, array(&$ret, 'key', &$values)));
		$this->assertTrue($ret);
		
		// value=multi-selectで、selectvaluesがない場合
		$mailform = new MailForm();
		$ret = true;
		
		$RequestParam->setValue($mailform, array('key' => array('1')));
		$selectValues = array('1' => 'option1', '2' => 'option2', '3' => 'option3');
		$values = array('value' => 'multi-select', 'empty' => false, 'title'=> 'title');
		$this->assertFalse($checkRequestParam->invokeArgs($mailform, array(&$ret, 'key', &$values)));
		$this->assertTrue($ret);
		$this->assertEquals(MailForm::ERROR_CODE_INVALID_FORM_CONFIG, $ErrorCode->getValue($mailform));
		$this->assertEquals('システムエラーが発生しました。', $ErrorMessages->getValue($mailform)['base']);
		
		
		// value=multi-selectで、入力が不正な値だった場合
		$mailform = new MailForm();
		$ret = true;
		
		$RequestParam->setValue($mailform, array('key' => '5'));
		$values = array('value' => 'multi-select', 'empty' => false, 'title'=> 'title', 'selectvalues' => $selectValues);
		$this->assertTrue($checkRequestParam->invokeArgs($mailform, array(&$ret, 'key', &$values)));
		$this->assertFalse($ret);
		$this->assertEquals(MailForm::ERROR_CODE_INVALID_SELECT_PARAM, $ErrorCode->getValue($mailform));
		$this->assertEquals('「title」に不正な値が入力されています。', $ErrorMessages->getValue($mailform)['key']);
		
		// value=multi-selectで、入力が不正な値だった場合
		$mailform = new MailForm();
		$ret = true;
		
		$RequestParam->setValue($mailform, array('key' => array('5')));
		$values = array('value' => 'multi-select', 'empty' => false, 'title'=> 'title', 'selectvalues' => $selectValues);
		$this->assertTrue($checkRequestParam->invokeArgs($mailform, array(&$ret, 'key', &$values)));
		$this->assertFalse($ret);
		$this->assertEquals(MailForm::ERROR_CODE_INVALID_SELECT_PARAM, $ErrorCode->getValue($mailform));
		$this->assertEquals('「title」に不正な値が入力されています。', $ErrorMessages->getValue($mailform)['key']);
		
		// value=multi-selectで、入力が正常だった場合
		$mailform = new MailForm();
		$ret = true;
		
		$RequestParam->setValue($mailform, array('key' => array('1','2')));
		$values = array('value' => 'multi-select', 'empty' => false, 'title'=> 'title', 'selectvalues' => $selectValues);
		$this->assertTrue($checkRequestParam->invokeArgs($mailform, array(&$ret, 'key', &$values)));
		$this->assertTrue($ret);
		
		// value=kanaで、入力値がカタカナでない場合
		$mailform = new MailForm();
		$ret = true;
		
		$RequestParam->setValue($mailform, array('key' => '123'));
		$values = array('value' => 'kana', 'empty' => false, 'title'=> 'title');
		$this->assertTrue($checkRequestParam->invokeArgs($mailform, array(&$ret, 'key', &$values)));
		$this->assertFalse($ret);
		$this->assertEquals(MailForm::ERROR_CODE_NOT_KANA_PARAM, $ErrorCode->getValue($mailform));
		$this->assertEquals('「title」はカタカナで入力してください。', $ErrorMessages->getValue($mailform)['key']);
		
		// value=kanaで、入力値がカタカナの場合
		$mailform = new MailForm();
		$ret = true;
		
		$RequestParam->setValue($mailform, array('key' => 'カナ'));
		$values = array('value' => 'kana', 'empty' => false, 'title'=> 'title');
		$this->assertTrue($checkRequestParam->invokeArgs($mailform, array(&$ret, 'key', &$values)));
		$this->assertTrue($ret);
		
		// value=yomiで、入力値がひらがなでない場合
		$mailform = new MailForm();
		$ret = true;
		
		$RequestParam->setValue($mailform, array('key' => '123'));
		$values = array('value' => 'yomi', 'empty' => false, 'title'=> 'title');
		$this->assertTrue($checkRequestParam->invokeArgs($mailform, array(&$ret, 'key', &$values)));
		$this->assertFalse($ret);
		$this->assertEquals(MailForm::ERROR_CODE_NOT_YOMI_PARAM, $ErrorCode->getValue($mailform));
		$this->assertEquals('「title」はひらがなで入力してください。', $ErrorMessages->getValue($mailform)['key']);
		
		// value=yomiで、入力値がひらがなの場合
		$mailform = new MailForm();
		$ret = true;
		
		$RequestParam->setValue($mailform, array('key' => 'よみ'));
		$values = array('value' => 'yomi', 'empty' => false, 'title'=> 'title');
		$this->assertTrue($checkRequestParam->invokeArgs($mailform, array(&$ret, 'key', &$values)));
		
		// value=plusで、入力値が数字でない場合
		$mailform = new MailForm();
		$ret = true;
		
		$RequestParam->setValue($mailform, array('key' => 'abc'));
		$values = array('value' => 'plus', 'empty' => false, 'title'=> 'title');
		$this->assertTrue($checkRequestParam->invokeArgs($mailform, array(&$ret, 'key', &$values)));
		$this->assertFalse($ret);
		$this->assertEquals(MailForm::ERROR_CODE_NOT_PLUS_PARAM, $ErrorCode->getValue($mailform));
		$this->assertEquals('「title」は数字で入力してください。', $ErrorMessages->getValue($mailform)['key']);
		
		// value=plusで、入力値が数字の場合
		$mailform = new MailForm();
		$ret = true;
		
		$RequestParam->setValue($mailform, array('key' => '123'));
		$values = array('value' => 'plus', 'empty' => false, 'title'=> 'title');
		$this->assertTrue($checkRequestParam->invokeArgs($mailform, array(&$ret, 'key', &$values)));
		
		// value=telで、入力値が数字でない場合
		$mailform = new MailForm();
		$ret = true;
		
		$RequestParam->setValue($mailform, array('key' => 'abc'));
		$values = array('value' => 'tel', 'empty' => false, 'title'=> 'title');
		$this->assertTrue($checkRequestParam->invokeArgs($mailform, array(&$ret, 'key', &$values)));
		$this->assertFalse($ret);
		$this->assertEquals(MailForm::ERROR_CODE_TEL_PARAM, $ErrorCode->getValue($mailform));
		$this->assertEquals('「title」は電話番号を入力してください。', $ErrorMessages->getValue($mailform)['key']);
		
		// value=telで、入力値が数字の場合
		$mailform = new MailForm();
		$ret = true;
		
		$RequestParam->setValue($mailform, array('key' => '123-1234-1234'));
		$values = array('value' => 'tel', 'empty' => false, 'title'=> 'title');
		$this->assertTrue($checkRequestParam->invokeArgs($mailform, array(&$ret, 'key', &$values)));
		
		// value=emailで、入力値が数字でない場合
		$mailform = new MailForm();
		$ret = true;
		
		$RequestParam->setValue($mailform, array('key' => 'abc'));
		$values = array('value' => 'email', 'empty' => false, 'title'=> 'title');
		$this->assertTrue($checkRequestParam->invokeArgs($mailform, array(&$ret, 'key', &$values)));
		$this->assertFalse($ret);
		$this->assertEquals(MailForm::ERROR_CODE_NOT_EMAIL_PARAM, $ErrorCode->getValue($mailform));
		$this->assertEquals('「title」は正しいメールアドレスを入力してください。', $ErrorMessages->getValue($mailform)['key']);
		
		// value=emailで、入力値が数字の場合
		$mailform = new MailForm();
		$ret = true;
		
		$RequestParam->setValue($mailform, array('key' => 'mail@example.com'));
		$values = array('value' => 'tel', 'empty' => false, 'title'=> 'title');
		$this->assertTrue($checkRequestParam->invokeArgs($mailform, array(&$ret, 'key', &$values)));
		
		// value=agreementで、設定にequalがない場合
		$mailform = new MailForm();
		$ret = true;
		
		$RequestParam->setValue($mailform, array('key' => 'agree'));
		$values = array('value' => 'agreement', 'empty' => false, 'title'=> 'title');
		$this->assertFalse($checkRequestParam->invokeArgs($mailform, array(&$ret, 'key', &$values)));
		$this->assertTrue($ret);
		$this->assertEquals(MailForm::ERROR_CODE_INVALID_FORM_CONFIG, $ErrorCode->getValue($mailform));
		$this->assertEquals('システムエラーが発生しました。', $ErrorMessages->getValue($mailform)['base']);
		
		// value=agreementで、入力値がequalの値と違う場合
		$mailform = new MailForm();
		$ret = true;
		
		$RequestParam->setValue($mailform, array('key' => '111'));
		$values = array('value' => 'agreement', 'empty' => false, 'title'=> 'title', 'equal' => 'agree');
		$this->assertTrue($checkRequestParam->invokeArgs($mailform, array(&$ret, 'key', &$values)));
		$this->assertFalse($ret);
		$this->assertEquals(MailForm::ERROR_CODE_NO_AGREEMENT, $ErrorCode->getValue($mailform));
		$this->assertEquals('同意していただく必要があります。', $ErrorMessages->getValue($mailform)['key']);
		
		// value=agreementで、入力値がequalの値と同じ場合
		$mailform = new MailForm();
		$ret = true;
		
		$RequestParam->setValue($mailform, array('key' => 'agree'));
		$values = array('value' => 'agreement', 'empty' => false, 'title'=> 'title', 'equal' => 'agree');
		$this->assertTrue($checkRequestParam->invokeArgs($mailform, array(&$ret, 'key', &$values)));
		
		// value=fileで、ファイルがない場合
		$mailform = new MailForm();
		$mailform->TmpDirectryPath = dirname(__FILE__).'/test_tmp/';
		
		$RequestParam->setValue($mailform, array('key' => 'test.txt'));
		$values = array('value' => 'file', 'empty' => false, 'title'=> 'title');
		$this->assertFalse($checkRequestParam->invokeArgs($mailform, array(&$ret, 'key', &$values)));
		$this->assertEquals(MailForm::ERROR_CODE_TEMP_FILE, $ErrorCode->getValue($mailform));
		$this->assertEquals('システムエラーが発生しました。', $ErrorMessages->getValue($mailform)['key']);
		
		// value=fileで、Mimeを指定しない場合
		$mailform = new MailForm();
		$mailform->TmpDirectryPath = dirname(__FILE__).'/test_tmp/';
		
		$filebasepath = $getTempDirPathFromSession->invokeArgs($mailform, array());
		if (!is_dir($mailform->TmpDirectryPath)) mkdir($mailform->TmpDirectryPath, 0700, true);
		if (!is_dir($filebasepath)) mkdir($filebasepath, 0700, true);
		file_put_contents($filebasepath.'/test.txt', 'test text');
		
		$RequestParam->setValue($mailform, array('key' => 'test.txt'));
		$values = array('value' => 'file', 'empty' => false, 'title'=> 'title');
		$this->assertTrue($checkRequestParam->invokeArgs($mailform, array(&$ret, 'key', &$values)));
		$this->assertTrue($ret);
				
		// value=fileで、Mimeが一致した場合
		$values = array('value' => 'file', 'empty' => false, 'title'=> 'title', 'filetypes' => array('text/plain'));
		$this->assertTrue($checkRequestParam->invokeArgs($mailform, array(&$ret, 'key', &$values)));
		$this->assertTrue($ret);
		
		// value=fileで、Mimeが合わない場合
		$values = array('value' => 'file', 'empty' => false, 'title'=> 'title', 'filetypes' => array('text/html'));
		$this->assertTrue($checkRequestParam->invokeArgs($mailform, array(&$ret, 'key', &$values)));
		$this->assertFalse($ret);
		$this->assertEquals(MailForm::ERROR_CODE_UNKOWN_FILE_FORMAT, $ErrorCode->getValue($mailform));
		$this->assertEquals('アップロードファイル「test.txt」のファイル形式には対応してません。', $ErrorMessages->getValue($mailform)['key']);
		
		system("rm -rf {$mailform->TmpDirectryPath}");
	}
	
	public function testSendMail() {
		$sendMail = getMethod('MailForm', 'sendMail');
		$MailConfigs = getProperty('MailForm', 'MailConfigs');
		
		$mailform = $this->getMockBuilder('MailForm')
		->setMethods(array('sendMailByIndex'))
		->getMock();
		$mailform->expects($this->once())
		->method('sendMailByIndex')
		->with('mail_index')
		->will($this->returnValue(true));
		
		$MailConfigs->setValue($mailform, array('mail_index' => 'mail_index_values'));
		$this->assertTrue($sendMail->invokeArgs($mailform, array()));
		
		$mailform = $this->getMockBuilder('MailForm')
		->setMethods(array('sendMailByIndex'))
		->getMock();
		$mailform->expects($this->once())
		->method('sendMailByIndex')
		->with('mail_index')
		->will($this->returnValue(false));
		
		$MailConfigs->setValue($mailform, array('mail_index' => 'mail_index_values'));
		$this->assertFalse($sendMail->invokeArgs($mailform, array()));
	}
	
	public function testSendMailByIndex() {
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
		$Subject = getProperty('SimpleMailer', 'Subject');
		$Body = getProperty('SimpleMailer', 'Body');
		
		$simpleMailer = new SimpleMailer();
		$mailform = $this->getMockBuilder('MailForm')
		->setMethods(array('getMailer'))
		->getMock();
		$mailform->expects($this->any())
		->method('getMailer')
		->will($this->returnValue($simpleMailer));
		
		$mailform->FromEncoding = 'SJIS';
		$mailform->CharSet = 'EUC-JP';
		$mailform->MailEncoding = 'UCS-2LE';
		
		$this->assertFalse($sendMailByIndex->invokeArgs($mailform, array('1')));
		$mailerFactory = $MailerFactory->getValue($mailform);
		
		$this->assertEquals('SJIS', $FromEncoding->getValue($simpleMailer));
		$this->assertEquals('EUC-JP', $ToEncoding->getValue($simpleMailer));
		$this->assertEquals('UCS-2LE', $Encoding->getValue($simpleMailer));
		
		$this->assertEquals(MailForm::ERROR_CODE_NOSET_TO, $ErrorCode->getValue($mailform));
		$this->assertEquals('システムエラーが発生しました。', $ErrorMessages->getValue($mailform)['base']);
		
		
		$simpleMailer = $this->getMockBuilder('SimpleMailer')
		->setMethods(array('send'))
		->getMock();
		$simpleMailer->expects($this->once())
		->method('send')
		->will($this->returnValue(false));
		
		$mailform = $this->getMockBuilder('MailForm')
		->setMethods(array('getMailer', 'getArrowTempFilesByIndex'))
		->getMock();
		$mailform->expects($this->once())
		->method('getMailer')
		->will($this->returnValue($simpleMailer));
		$mailform->expects($this->any())
		->method('getArrowTempFilesByIndex')
		->will($this->returnValue(false));
		
		
		$mailform->DefaultEmailAddress = 'default@example.com';
		$this->assertFalse($sendMailByIndex->invokeArgs($mailform, array('1')));
		$this->assertEquals(MailForm::ERROR_CODE_CANNOT_SEND, $ErrorCode->getValue($mailform));
		$this->assertEquals('メールを送ることができませんでした。', $ErrorMessages->getValue($mailform)['base']);
		$this->assertEquals('default@example.com', $To->getValue($simpleMailer)[0][0]);
		$this->assertEquals('default@example.com', $From->getValue($simpleMailer)[0]);
		
		
		$simpleMailer = $this->getMockBuilder('SimpleMailer')
		->setMethods(array('send', 'AddAttachment'))
		->getMock();
		$simpleMailer->expects($this->once())
		->method('send')
		->will($this->returnValue(true));
		$simpleMailer->expects($this->once())
		->method('AddAttachment')
		->with('/path/filevalue')
		->will($this->returnValue(false));
		
		$mailform = $this->getMockBuilder('MailForm')
		->setMethods(array('getMailer', 'getTempDirPathFromSession'))
		->getMock();
		$mailform->expects($this->any())
		->method('getMailer')
		->will($this->returnValue($simpleMailer));
		$mailform->expects($this->any())
		->method('getTempDirPathFromSession')
		->will($this->returnValue('/path/'));
		
		$mailform->setFromByIndex('1', '{{{from}}}', 'from_name');
		$mailform->setReturnPathByIndex('1', 'returnpath@example.com', 'returnpath_name');
		$mailform->addToByIndex('1', '{{{to}}}');
		$mailform->addToByIndex('1', 'addTo@example.com', 'add_to_name');
		$mailform->setMailSubjectTemplateByIndex('1', 'subject {{{key1}}}');
		$mailform->setMailBodyTemplateByIndex('1', 'body key2:{{{key2}}} file:{{{file}}} selectkey:{{{selectkey}}} multi-selectkey:{{{multi-selectkey}}}');
		$mailform->CharSet = 'UTF-8';
		
		$selectValues = array('1' => 'option1', '2' => 'option2', '3' => 'option3');
		$FormConfig->setValue($mailform, array('items' => array('to' => array('value' => 'email'),
																'from' => array('value' => 'email'),
																'key1' => array('value' => 'string'),
																'key2' => array('value' => 'string'),
																'file' => array('value' => 'file'),
																'selectkey' => array('value' => 'select', 'selectvalues' => $selectValues),
																'multi-selectkey' => array('value' => 'multi-select', 'selectvalues' => $selectValues))));
		$RequestParam->setValue($mailform, array('to' => 'to@example.com', 'from' => 'from@example.com', 'key1' => 'value1', 'file' => 'filevalue', 'key2' => 'value2', 'selectkey' => '1', 'multi-selectkey' => array('2','3')));
		
		$this->assertTrue($sendMailByIndex->invokeArgs($mailform, array('1')));
		$this->assertEquals('to@example.com', $To->getValue($simpleMailer)[0][0]);
		$this->assertEquals('', $To->getValue($simpleMailer)[0][1]);
		$this->assertEquals('addTo@example.com', $To->getValue($simpleMailer)[1][0]);
		$this->assertEquals('add_to_name', $To->getValue($simpleMailer)[1][1]);
		$this->assertEquals('from@example.com', $From->getValue($simpleMailer)[0]);
		$this->assertEquals('from_name', $From->getValue($simpleMailer)[1]);
		$this->assertEquals('returnpath@example.com', $ReturnPath->getValue($simpleMailer)[0]);
		$this->assertEquals('returnpath_name', $ReturnPath->getValue($simpleMailer)[1]);
		
		$this->assertEquals('subject value1', $Subject->getValue($simpleMailer));
		$this->assertEquals('body key2:value2 file:filevalue selectkey:option1 multi-selectkey:option2／option3', $Body->getValue($simpleMailer));
		
	}
	
	public function testGetCompleteMailSubjectByIndex() {
		$getCompleteMailSubjectByIndex = getMethod('MailForm', 'getCompleteMailSubjectByIndex');
		$mailform = $this->getMockBuilder('MailForm')
		->setMethods(array('getCompleteMailSubject', 'getMailSubjectTemplateByIndex'))
		->getMock();
		$mailform->expects($this->once())
		->method('getMailSubjectTemplateByIndex')
		->with('index')
		->will($this->returnValue('subject'));
		$mailform->expects($this->once())
		->method('getCompleteMailSubject')
		->with('subject')
		->will($this->returnValue('return'));
		
		$this->assertEquals('return', $getCompleteMailSubjectByIndex->invokeArgs($mailform, array('index')));
	}
	
	public function testGetCompleteMailSubject() {
		$getCompleteMailSubject = getMethod('MailForm', 'getCompleteMailSubject');
		$mailform = $this->getMockBuilder('MailForm')
		->setMethods(array('replaceFieldTag'))
		->getMock();
		$mailform->expects($this->once())
		->method('replaceFieldTag')
		->with('value')
		->will($this->returnValue('return'));
		
		$this->assertEquals('return', $getCompleteMailSubject->invokeArgs($mailform, array('value')));
	}
	
	public function testGetCompleteMailBodyByIndex() {
		$getCompleteMailBodyByIndex = getMethod('MailForm', 'getCompleteMailBodyByIndex');
		$mailform = $this->getMockBuilder('MailForm')
		->setMethods(array('getCompleteMailBody', 'getMailBodyTemplateByIndex'))
		->getMock();
		$mailform->expects($this->once())
		->method('getMailBodyTemplateByIndex')
		->with('index')
		->will($this->returnValue('body'));
		$mailform->expects($this->once())
		->method('getCompleteMailBody')
		->with('body')
		->will($this->returnValue('return'));
		
		$this->assertEquals('return', $getCompleteMailBodyByIndex->invokeArgs($mailform, array('index')));
	}
	
	public function testGetValueByType() {
		$getValueByType = getMethod('MailForm', 'getValueByType');
		$RequestParam = getProperty('MailForm', 'RequestParam');
		$FormConfig = getProperty('MailForm', 'FormConfig');
		
		$mailform = new MailForm();
		$selectValues = array('1' => 'option1', '2' => 'option2', '3' => 'option3');
		$FormConfig->setValue($mailform, array('items' => array('selectkey' => array('value' => 'select', 'selectvalues' => $selectValues),
																'multi-selectkey' => array('value' => 'multi-select', 'selectvalues' => $selectValues))));
		$RequestParam->setValue($mailform, array('key1' => 'value1', 'key2' => 'value2', 'selectkey' => '1', 'multi-selectkey' => array('2','3')));
		
		$this->assertEquals('value1', $getValueByType->invokeArgs($mailform, array('key1', 'string')));
		$this->assertEquals('option1', $getValueByType->invokeArgs($mailform, array('selectkey', 'select')));
		$this->assertEquals('option2／option3', $getValueByType->invokeArgs($mailform, array('multi-selectkey', 'multi-select')));
		$this->assertEquals('', $getValueByType->invokeArgs($mailform, array('none', 'string')));
		$this->assertEquals('', $getValueByType->invokeArgs($mailform, array('none', 'select')));
		$this->assertEquals('', $getValueByType->invokeArgs($mailform, array('none', 'multi-selectkey')));
		
	}
	
	public function testGetCompleteMailBody() {
		$getCompleteMailBody = getMethod('MailForm', 'getCompleteMailBody');
		$mailform = $this->getMockBuilder('MailForm')
		->setMethods(array('replaceFieldTag'))
		->getMock();
		$mailform->expects($this->once())
		->method('replaceFieldTag')
		->with('value')
		->will($this->returnValue('return'));
		
		$this->assertEquals('return', $getCompleteMailBody->invokeArgs($mailform, array('value')));
	}
	
	public function testReplaceFieldTag() {
		$replaceFieldTag = getMethod('MailForm', 'replaceFieldTag');
		$RequestParam = getProperty('MailForm', 'RequestParam');
		$FormConfig = getProperty('MailForm', 'FormConfig');
		
		$mailform = $this->getMockBuilder('MailForm')
		->setMethods(array('getDateTime'))
		->getMock();
		$mailform->expects($this->any())
		->method('getDateTime')
		->will($this->returnValue('return_getDateTime'));
		
		
		$selectValues = array('1' => 'option1', '2' => 'option2', '3' => 'option3');
		$FormConfig->setValue($mailform, array('items' => array('key1' => array('value' => 'string'),
																'selectkey' => array('value' => 'select', 'selectvalues' => $selectValues),
																'multi-selectkey' => array('value' => 'multi-select', 'selectvalues' => $selectValues))));
		$RequestParam->setValue($mailform, array('key1' => 'value1', 'key2' => 'value2', 'selectkey' => '1', 'multi-selectkey' => array('2','3')));
		
		$this->assertEquals('時間：return_getDateTime', $replaceFieldTag->invokeArgs($mailform, array('時間：{{{datetime}}}')));
		$this->assertEquals('key1=value1\nselectkey=option1\nmulti-selectkey=option2／option3', $replaceFieldTag->invokeArgs($mailform, array('key1={{{key1}}}\nselectkey={{{selectkey}}}\nmulti-selectkey={{{multi-selectkey}}}')));
		$RequestParam->setValue($mailform, array());
		$this->assertEquals('key1=\nselectkey=\nmulti-selectkey=', $replaceFieldTag->invokeArgs($mailform, array('key1={{{key1}}}\nselectkey={{{selectkey}}}\nmulti-selectkey={{{multi-selectkey}}}')));
		
		
	}
	
	public function testGetFieldTags() {
		$getFieldTags = getMethod('MailForm', 'getFieldTags');
	
		$mailform = new MailForm();
		$array = $getFieldTags->invokeArgs($mailform, array('testtesttest'));
		$this->assertCount(0, $array);
		$array = $getFieldTags->invokeArgs($mailform, array('{{{aaa}}}'));
		$this->assertCount(1, $array);
		$this->assertEquals('aaa', $array[0]);
		$array = $getFieldTags->invokeArgs($mailform, array('{{{aaa_1}}}runnrun{{{aaa_2}}}testtest'));
		$this->assertCount(2, $array);
		$this->assertEquals('aaa_1', $array[0]);
		$this->assertEquals('aaa_2', $array[1]);
		
	}
}
?>