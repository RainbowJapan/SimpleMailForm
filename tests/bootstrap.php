<?php
$vendorAutoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($vendorAutoload)) {
	require_once $vendorAutoload;
}

if (!class_exists('PHPUnit_Framework_TestCase')) {
	if (class_exists('\\PHPUnit\\Framework\\TestCase')) {
		class_alias('\\PHPUnit\\Framework\\TestCase', 'PHPUnit_Framework_TestCase');
	} else {
		throw new RuntimeException('PHPUnit base TestCase class not found.');
	}
}

require_once __DIR__ . '/helpers/TestCase.php';
