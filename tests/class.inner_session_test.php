<?php
require_once(dirname(__FILE__).'/../lib/class.inner_session.php');

class InnerSessionTest extends PHPUnit_Framework_TestCase
{
  protected function setUp(){
    if(!isset($_SESSION)){
        $_SESSION=array(  );
    }
  }
  
  public function testInnerSessionFactory_getInstance()
  {
    $factory = new InnerSessionFactory();
    $session1 = $factory->getInstance();
    $session2 = $factory->getInstance();
    $this->assertInstanceOf('InnerSession', $session1);
    $this->assertEquals($session1, $session2);
  }
  
  public function testStartSession()
  {
     // チェックできず
  }
  
  public function testDestroySession()
  {
     // チェックできず
  }
  
  public function testIsStartSession()
  {
    $factory = new InnerSessionFactory();
    $session = $factory->getInstance();
    $this->assertEquals($session->isStartSession(), true);
    $_SESSION = NULL;
    $this->assertEquals($session->isStartSession(), false);
    $_SESSION=array(  );
  }
  
  public function testIsSetParam()
  {
    $factory = new InnerSessionFactory();
    $session = $factory->getInstance();
    $this->assertEquals($session->isSetParam('test'), false);
    $_SESSION['test'] = 1;
    $this->assertEquals($session->isSetParam('test'), true);
  }
  
  public function testUnnsetParam()
  {
    $factory = new InnerSessionFactory();
    $session = $factory->getInstance();
    $session->unnsetParam('none');
    $_SESSION['test'] = 1;
    $session->unnsetParam('test');
    $this->assertEquals(isset($_SESSION['test']), false);
  }
  
  public function testSetParam()
  {
    $factory = new InnerSessionFactory();
    $session = $factory->getInstance();
    $session->setParam('test', 'setParam');
    $this->assertEquals(isset($_SESSION['test']), true);
    $this->assertEquals($_SESSION['test'], 'setParam');
  }
  
  public function testGetParamValue()
  {
    $factory = new InnerSessionFactory();
    $session = $factory->getInstance();
    $_SESSION['test'] = 'getParamValue';
    $this->assertEquals($session->getParamValue('test'), 'getParamValue');
    $this->assertEquals($session->getParamValue('none'), NULL);
    $_SESSION = NULL;
    $this->assertEquals($session->getParamValue('none'), NULL);
  }
  
}
?>