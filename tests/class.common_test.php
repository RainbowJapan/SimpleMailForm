<?php
require_once(dirname(__FILE__).'/../lib/common.php');

class CommonTest extends PHPUnit_Framework_TestCase
{
  public function test__getBaseUrl()
  {
    $_SERVER['SERVER_NAME'] = 'www.test.co.jp';
    $this->assertEquals(__getBaseUrl(), 'http://www.test.co.jp');
    $_SERVER['HTTPS'] = 'off';
    $this->assertEquals(__getBaseUrl(), 'http://www.test.co.jp');
    $_SERVER['HTTPS'] = 'on';
    $this->assertEquals(__getBaseUrl(), 'https://www.test.co.jp');
  }
  public function test__replaceGreekingText()
  {
    $this->markTestSkipped(
      '変換テーブルを確認してテスト'
    );
  }
}
?>