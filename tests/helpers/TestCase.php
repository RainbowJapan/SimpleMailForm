<?php

// PHPのバージョンに応じて、パース可能なファイルだけを動的に読み込む
if (PHP_VERSION_ID >= 70100) {
	require_once __DIR__ . '/SetupTraitV7.php';
} else {
	require_once __DIR__ . '/SetupTraitV5.php';
}

abstract class SimpleMailForm_TestCase extends PHPUnit_Framework_TestCase
{
	use SimpleMailForm_SetUpTrait;

	protected function setUpCompat()
	{
	}

	protected function tearDownCompat()
	{
	}
	
	/**
	 * 【PHP 5.5〜8.x 両対応】
	 * 新旧のPHPUnitバージョンを自動判別して、安全にモックオブジェクトを作ります
	 */
	protected function createCompatibleMock($className, array $methods)
	{
		$mockBuilder = $this->getMockBuilder($className);
		
		if (method_exists($mockBuilder, 'onlyMethods')) {
			// 現代の開発環境（PHPUnit 10以降）用
			$mockBuilder->onlyMethods($methods);
		} else {
			// 本番・古い環境（PHPUnit 9以下）用
			$mockBuilder->setMethods($methods);
		}
		
		return $mockBuilder->getMock();
	}
	
	/**
	 * 【PHP 5.5〜8.x 両対応】
	 * 新旧のPHPUnitバージョンを自動判別して、安全にモックの戻り値をセットします
	 */
	protected function setCompatibleReturnValue($expectation, $value)
	{
		
		if (method_exists($this, 'returnValue')) {
			// 本番・古い環境（PHPUnit 9以下）用
			$expectation->will($this->returnValue($value));
		} else {
			// 現代の開発環境（PHPUnit 10以降）用
			$expectation->willReturn($value);
		}
	}

	/**
	 * 【PHP 5.5〜8.x 両対応】
	 * 新旧のPHPUnitバージョンを自動判別して、any関数呼び出しをサポート
	 */
	protected function expectsAny($mailform)
	{
		if (class_exists(\PHPUnit\Framework\MockObject\Runtime\PropertyHook::class) || !method_exists($this, 'getMockBuilder')) {
			// PHPUnit 10以降
			return $mailform->expects($this->atLeastOnce());
		} else {
			// PHPUnit 9以前（$this->any() がそのまま使えます）
			return $mailform->expects($this->any());
		}
	}
}
