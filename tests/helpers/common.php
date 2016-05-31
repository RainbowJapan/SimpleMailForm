<?php

function getMethod($className, $name)
{
  $class = new ReflectionClass($className);

  $method = $class->getMethod($name);
  $method->setAccessible(true);
  return $method;
}

function getProperty($className, $name)
{
  $class = new ReflectionClass($className);

  $property = $class->getProperty($name);
  $property->setAccessible(true);

  return $property;
}
?>