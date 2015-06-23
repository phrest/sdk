<?php

namespace Phrest\SDK\Generator\Helper;

use Phrest\SDK\Generator;
use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\DocBlock\Tag;
use Zend\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\ParameterGenerator;
use Zend\Code\Generator\PropertyGenerator;

class ClassGen
{

  /**
   * @param        $name
   * @param        $namespace
   * @param        $uses
   * @param string $extends
   * @param array  $implements
   *
   * @return ClassGenerator
   */
  public static function classGen(
    $name,
    $namespace,
    $uses = [],
    $extends = '',
    $implements = []
  )
  {
    $class = new ClassGenerator();

    $class
    ->setName($name)
    ->setNamespaceName($namespace)
    ->setExtendedClass($extends)
    ->setImplementedInterfaces($implements)
    ->setIndentation(Generator::$indentation);

    foreach ($uses as $use)
    {
      $class->addUse($use);
    }

    return $class;
  }

  /**
   * @param $name
   * @param $visibility
   * @param $default
   * @param $type
   *
   * @return PropertyGenerator
   */
  public static function property(
    $name,
    $visibility = 'public',
    $default = null,
    $type = 'mixed'
  )
  {
    $property = (new PropertyGenerator($name, $default))
    ->setVisibility($visibility);

    $property->setIndentation(Generator::$indentation);

    $docBlock = new DocBlockGenerator();
    $docBlock->setIndentation(Generator::$indentation);

    $tag = new Tag();
    $tag->setName('var');
    $tag->setContent($type);
    $tag->setIndentation(Generator::$indentation);

    $docBlock->setTag($tag);

    $property->setDocBlock($docBlock);

    return $property;
  }

  /**
   * @param                          $name
   * @param ParameterGenerator[]     $params
   * @param string                   $visibility
   * @param string                   $body
   * @param string|DocBlockGenerator $docblock
   *
   * @return \Zend\Code\Generator\MethodGenerator
   */
  public static function method(
    $name,
    $params = [],
    $visibility = 'public',
    $body = '//todo',
    $docblock = ''
  )
  {
    if (empty($docblock))
    {
      if (!empty($params))
      {
        $docblock = new DocBlockGenerator();
        foreach ($params as $param)
        {
          $tag = new Tag('param', $param->getType() . ' $' . $param->getName());
          $docblock->setTag($tag);
        }
      }
    }

    return (new MethodGenerator($name, $params))
    ->setBody($body)
    ->setVisibility($visibility)
    ->setDocBlock($docblock)
    ->setIndentation(Generator::$indentation);
  }

  /**
   * @param ParameterGenerator[] $params
   * @return MethodGenerator
   */
  public static function constructor($params)
  {
    $constructor = self::method('__construct', $params);

    $body = '';

    foreach ($params as $param)
    {
      $body .= "\$this->{$param->getName()} = \${$param->getName()};" . PHP_EOL;
    }

    $constructor->setBody($body);
    return $constructor;
  }
}
