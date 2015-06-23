<?php

namespace Phrest\SDK\Generator\Exception;

use Phrest\SDK\Generator;
use Phrest\SDK\Generator\AbstractGenerator;
use Phrest\SDK\Generator\Helper\ClassGen;
use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\DocBlock\Tag;
use Zend\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\GeneratorInterface;
use Zend\Code\Generator\ParameterGenerator;
use Zend\Code\Generator\PropertyGenerator;
use Zend\Code\Reflection\DocBlock\Tag\PropertyTag;

class ExceptionGenerator extends AbstractGenerator
{
  /**
   * @var string
   */
  protected $namespace;

  /**
   * @var string
   */
  protected $extends;

  /**
   * @var string
   */
  protected $entityName;

  /**
   * @var string
   */
  protected $exception;

  /**
   * @var string
   */
  protected $message;

  /**
   * @param string $version
   * @param string $entityName
   * @param        $exception
   * @param        $message
   * @param        $extends
   */
  public function __construct(
    $version,
    $entityName,
    $exception,
    $message,
    $extends
  )
  {
    $this->entityName = $entityName;
    $this->exception = $exception;
    $this->message = $message;
    $this->extends = $extends;
    parent::__construct($version, $entityName);
  }

  /**
   * @return ClassGenerator
   */
  public function create()
  {
    $class = ClassGen::classGen(
      $this->exception,
      $this->namespace
    );

    if (!empty($this->extends))
    {
      $class->addUse('Phrest\\API\\Exceptions\\' . $this->extends);
      $class->setExtendedClass($this->extends);
    }

    return $class;
  }

  /**
   * @param string $namespace
   *
   * @return ExceptionGenerator
   */
  public function setNamespace($namespace)
  {
    $this->namespace = $namespace;

    return $this;
  }

  /**
   * @return string
   */
  public function getEntityName()
  {
    return $this->entityName;
  }

  /**
   * @param array $extends
   *
   * @return ExceptionGenerator
   */
  public function setExtends($extends)
  {
    $this->extends = $extends;

    return $this;
}

  /**
   * @return string
   */
  public function getException()
  {
    return $this->exception;
  }
}
