<?php

function getMethod($className, $name)
{
	$class = new ReflectionClass($className);

	$method = $class->getMethod($name);
	if (PHP_VERSION_ID < 80100) {
		// PHP 8.1未満（PHP 5.5など）の環境のときだけ実行する
		$method->setAccessible(true);
	}
	return $method;
}

function getProperty($className, $name)
{
	$class = new ReflectionClass($className);

	$property = $class->getProperty($name);
	if (PHP_VERSION_ID < 80100) {
		// PHP 8.1未満（PHP 5.5など）の環境のときだけ実行する
		$property->setAccessible(true);
	}

	return $property;
}
