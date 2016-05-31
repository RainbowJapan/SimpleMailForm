<?php
require_once(dirname(__FILE__).'/../lib/class.request_checker.php');
require_once(dirname(__FILE__).'/helpers/class.test_session.php');
require_once(dirname(__FILE__).'/helpers/common.php');

class RequestCheckerTest extends PHPUnit_Framework_TestCase
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
    $this->assertInstanceOf('TestSession', $getSession->invokeArgs($checker, array()));
  }
  
  public function testCreateCsrfToken()
  {
    $createCsrfToken = getMethod('RequestChecker', 'createCsrfToken');
    $checker = new RequestChecker();
    $factory = new TestSessionFactory();
    $checker->setSessionFactory($factory);
    
    $token = $createCsrfToken->invokeArgs($checker, array());
    $this->assertNotEmpty($token);
    $this->assertEquals(strlen($token), 64);
    $this->assertNotEquals($createCsrfToken->invokeArgs($checker, array()), $createCsrfToken->invokeArgs($checker, array()));
    $this->assertNotEquals($createCsrfToken->invokeArgs($checker, array()), $createCsrfToken->invokeArgs($checker, array()));
  }
  
  public function testGetCsrfToken()
  {
    $_SESSION=array(  );
    $getCsrfToken = getMethod('RequestChecker', 'getCsrfToken');
    $checker1 = new RequestChecker();
    
    $token = $getCsrfToken->invokeArgs($checker1, array());
    $this->assertNotEmpty($token);
    $this->assertEquals(strlen($token), 64);
    $this->assertEquals($token, $getCsrfToken->invokeArgs($checker1, array()));
    $checker2 = new RequestChecker();
    $this->assertEquals($token, $getCsrfToken->invokeArgs($checker2, array()));
    
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
    $array = array('test' => '');
    
    $this->assertEquals($checker->check(NULL), true);
    $this->assertEquals($checker->check(array()), true);
    
    $_SERVER['HTTP_REFERER'] = 'http://www.test2.co.jp/test1';
    $_SERVER['HTTPS'] = 'off';
    $_SERVER['SERVER_NAME'] = 'www.test.co.jp';
    $this->assertEquals($checker->check($array), false);
    
    $_SERVER['HTTP_REFERER'] = 'http://www.test.co.jp/test1';
    $this->assertEquals($checker->check($array), false);
    
    $getCsrfToken = getMethod('RequestChecker', 'getCsrfToken');
    $token = $getCsrfToken->invokeArgs($checker, array());
    $array = array('csrf_token' => $token);
    $this->assertEquals($checker->check($array), true);
    
  }
  
  public function testGetCSRFHiddenForm()
  {
    $checker = new RequestChecker();
    $factory = new TestSessionFactory();
    $checker->setSessionFactory($factory);
    $getCsrfToken = getMethod('RequestChecker', 'getCsrfToken');
    $token = $getCsrfToken->invokeArgs($checker, array());
    $this->assertEquals($checker->getCSRFHiddenForm(), '<input type="hidden" name="csrf_token" value="'.$token.'" >');
    
  }
}
?>