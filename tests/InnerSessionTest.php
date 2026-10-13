<?php
require_once(dirname(__FILE__).'/../lib/class.inner_session.php');

class InnerSessionTest extends SimpleMailForm_TestCase
{
	/** @var InnerSession */
	protected $session;
	
	protected function setUpCompat()
	{
		parent::setUpCompat();
		if (!isset($_SESSION) || $_SESSION === null) {
			$_SESSION = [];
		}
		
		$factory = new InnerSessionFactory();
		$this->session = $factory->getInstance();
	}

	protected function tearDownCompat()
	{
		$_SESSION = [];
		parent::tearDownCompat();
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
		$_SESSION = null;
		$this->assertFalse($this->session->isStartSession());
		$this->session->startSession();
		$this->assertTrue($this->session->isStartSession());
	}

	public function testDestroySession()
	{
		$this->session->startSession();
		$_SESSION = ['foo' => 'bar'];
		$this->assertTrue($this->session->isStartSession());
		$this->session->destroySession();
		$this->assertEquals([], $_SESSION);
	}

	public function testIsStartSession()
	{
		$this->session->startSession();
		$this->assertTrue($this->session->isStartSession());
		$_SESSION = null;
		$this->assertFalse($this->session->isStartSession());
	}

	public function testIsSetParam()
	{
		$this->session->startSession();
		$this->assertFalse($this->session->isSetParam('test'));
		$_SESSION['test'] = 1;
		$this->assertTrue($this->session->isSetParam('test'));
	}

	public function testUnsetParam()
	{
		$this->session->startSession();
		$this->session->unsetParam('none');
		$_SESSION['test'] = 1;
		$this->session->unsetParam('test');
		$this->assertFalse(isset($_SESSION['test']));
	}

	public function testSetParam()
	{
		$this->session->startSession();
		$this->session->setParam('test', 'setParam');
		$this->assertTrue(isset($_SESSION['test']));
		$this->assertEquals('setParam', $_SESSION['test']);
	}

	public function testGetParamValue()
	{
		$this->session->startSession();
		$_SESSION['test'] = 'getParamValue';
		$this->assertEquals('getParamValue', $this->session->getParamValue('test'));
		$this->assertNull($this->session->getParamValue('none'));
		$_SESSION = null;
		$this->assertNull($this->session->getParamValue('none'));
	}
}
