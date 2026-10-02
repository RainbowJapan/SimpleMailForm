<?php
require_once(dirname(__FILE__).'/../lib/common.php');

class CommonTest extends SimpleMailForm_TestCase
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
		// 配列を渡した場合は空文字が返ること
		$this->assertEquals('', __replaceGreekingText(['test']));

		// 機種依存文字・特殊記号の変換チェック
		$this->assertEquals('No.1', __replaceGreekingText('№1'));
		$this->assertEquals('1/3', __replaceGreekingText('⅓'));
		$this->assertEquals('III', __replaceGreekingText('Ⅲ'));
		$this->assertEquals('(1)', __replaceGreekingText('①'));
		$this->assertEquals('(株)', __replaceGreekingText('㈱'));
		$this->assertEquals('平成', __replaceGreekingText('㍻'));
		$this->assertEquals('TEL 00-0000', __replaceGreekingText('℡ 00-0000'));

		// 変換対象外の通常文字列はそのまま
		$this->assertEquals('Hello, World! テスト 123', __replaceGreekingText('Hello, World! テスト 123'));
	}
}
