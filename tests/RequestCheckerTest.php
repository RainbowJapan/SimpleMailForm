<?php
require_once(dirname(__FILE__).'/../lib/class.request_checker.php');
require_once(dirname(__FILE__).'/helpers/TestSession.php');
require_once(dirname(__FILE__).'/helpers/common.php');

class RequestCheckerTest extends SimpleMailForm_TestCase
{
	public function testSetSessionFactory()
	{
		$checker = new RequestChecker();
		$factory = new TestSessionFactory();
		$checker->setSessionFactory($factory);
		$arr = (array)$checker;
		$this->assertEquals($factory, $arr["\0*\0SessionFactory"]);
	}
  
	public function testGetSession()
	{
		$getSession = getMethod('RequestChecker', 'getSession');
		$checker = new RequestChecker();
		$factory = new TestSessionFactory();
		$checker->setSessionFactory($factory);
		$this->assertInstanceOf('TestSession', $getSession->invokeArgs($checker, []));
	}
  
	public function testCreateCsrfToken()
	{
		$createCsrfToken = getMethod('RequestChecker', 'createCsrfToken');
		$checker = new RequestChecker();
		$factory = new TestSessionFactory();
		$checker->setSessionFactory($factory);
	
		$token = $createCsrfToken->invokeArgs($checker, []);
		$this->assertNotEmpty($token);
		$this->assertEquals(strlen($token), 64);
		$this->assertNotEquals($createCsrfToken->invokeArgs($checker, []), $createCsrfToken->invokeArgs($checker, []));
		$this->assertNotEquals($createCsrfToken->invokeArgs($checker, []), $createCsrfToken->invokeArgs($checker, []));
	}
  
	public function testGetCsrfToken()
	{
		$_SESSION=[  ];
		$getCsrfToken = getMethod('RequestChecker', 'getCsrfToken');
		$checker1 = new RequestChecker();
	
		$token = $getCsrfToken->invokeArgs($checker1, []);
		$this->assertNotEmpty($token);
		$this->assertEquals(strlen($token), 64);
		$this->assertEquals($token, $getCsrfToken->invokeArgs($checker1, []));
		$checker2 = new RequestChecker();
		$this->assertEquals($token, $getCsrfToken->invokeArgs($checker2, []));
	
	}
  
	public function testCheckReferer()
	{
		$_SERVER['HTTP_REFERER'] = 'http://www.test.co.jp/test1';
		$_SERVER['HTTPS'] = 'off';
		$_SERVER['SERVER_NAME'] = 'www.test.co.jp';
		$checker = new RequestChecker();
		$factory = new TestSessionFactory();
		$checker->setSessionFactory($factory);
		$this->assertEquals($checker->checkReferer(), true);
	
		$_SERVER['HTTP_REFERER'] = 'http://www.test2.co.jp/test1';
		$this->assertEquals($checker->checkReferer(), false);
	}
  
	public function testCheck()
	{
		$checker = new RequestChecker();
		$factory = new TestSessionFactory();
		$checker->setSessionFactory($factory);
		$array = ['test' => ''];
	
		$this->assertEquals($checker->check(null), true);
		$this->assertEquals($checker->check([]), true);
	
		$_SERVER['HTTP_REFERER'] = 'http://www.test2.co.jp/test1';
		$_SERVER['HTTPS'] = 'off';
		$_SERVER['SERVER_NAME'] = 'www.test.co.jp';
		$this->assertEquals($checker->check($array), false);
	
		$_SERVER['HTTP_REFERER'] = 'http://www.test.co.jp/test1';
		$this->assertEquals($checker->check($array), false);
	
		$getCsrfToken = getMethod('RequestChecker', 'getCsrfToken');
		$token = $getCsrfToken->invokeArgs($checker, []);
		$array = ['csrf_token' => $token];
		$this->assertEquals($checker->check($array), true);
	
	}
  
	public function testGetCSRFHiddenForm()
	{
		$checker = new RequestChecker();
		$factory = new TestSessionFactory();
		$checker->setSessionFactory($factory);
		$getCsrfToken = getMethod('RequestChecker', 'getCsrfToken');
		$token = $getCsrfToken->invokeArgs($checker, []);
		$this->assertEquals($checker->getCSRFHiddenForm(), '<input type="hidden" name="csrf_token" value="'.$token.'" >');
	
	}
}
