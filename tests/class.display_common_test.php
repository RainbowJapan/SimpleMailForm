<?php
require_once(dirname(__FILE__).'/../lib/display_common.php');

class DisplayCommonTest extends PHPUnit_Framework_TestCase
{
  public function test__htmlescape()
  {
    $this->assertEquals(__htmlescape("<br />\n"), "&lt;br /&gt;\n");
  }
  
  public function test__e()
  {
    ob_start();
    __e("<br />\n");
    $output = ob_get_clean();
    $this->assertEquals("&lt;br /&gt;\n", $output);
  }
  
  public function test__ebr()
  {
    ob_start();
    __ebr("<br />\n");
    $output = ob_get_clean();
    $this->assertEquals("&lt;br /&gt;<br />", $output);
  }
  
  public function test__p()
  {
    ob_start();
    __p("<br />\n");
    $output = ob_get_clean();
    $this->assertEquals("<br />\n", $output);
  }
  
  public function test__pchecked()
  {
    ob_start();
    __pchecked(true);
    $output = ob_get_clean();
    $this->assertEquals('checked', $output);
    ob_start();
    __pchecked(false);
    $output = ob_get_clean();
    $this->assertEquals('', $output);
  }
}
?>