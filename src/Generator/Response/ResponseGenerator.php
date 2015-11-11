<?php

namespace Phrest\SDK\Generator\Response;

use Phrest\SDK\Generator\AbstractGenerator;
use Phrest\SDK\Generator\Helper\ClassGen;
use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\GeneratorInterface;
use Zend\Code\Generator\ParameterGenerator;

class ResponseGenerator extends AbstractGenerator
{
  /**
   * @var string
   */
  protected $name;

  /**
   * @var array
   */
  protected $columns;

  /**
   * @var string
   */
  protected $namespace;

  /**
   * @var int
   */
  protected $type;

  const SINGULAR = 1;
  const PLURAL = 2;

  /**
   * @param string $version
   * @param string $entityName
   * @param        $entityDefinition
   */
  public function __construct($version, $entityName, $entityDefinition)
  {
    $this->name = $entityName;
    $this->columns = $entityDefinition;
    parent::__construct($version, $entityName);
  }

  /**
   * @return ClassGenerator
   */
  public function create()
  {
    if ($this->type == self::SINGULAR)
    {
      return $this->generateSingular();
    }
    elseif ($this->type == self::PLURAL)
    {
      return $this->generatePlural();
    }
  }

  /**
   * @return ClassGenerator
   */
  public function generateSingular()
  {
    $class = ClassGen::classGen(
      $this->name . 'Response',
      $this->namespace,
      ['Phrest\API\Response\Response'],
      'Response'
    );

    foreach ($this->columns as $name => $type)
    {
      $property = ClassGen::property($name, 'public', null, $type);
      $class->addPropertyFromGenerator($property);
      $params[] = new ParameterGenerator($name, $type);
    }

    $constructor = ClassGen::constructor($params);

    $body = $constructor->getBody();
    $body .= 'parent::__construct();' . PHP_EOL;

    $constructor->setBody($body);

    $class->addMethodFromGenerator($constructor);

    return $class;
  }

  /**
   * @return ClassGenerator
   */
  public function generatePlural()
  {
    $class = ClassGen::classGen(
      $this->name . 'sResponse',
      $this->namespace,
      ['Phrest\API\Response\ResponseArray'],
      'ResponseArray'
    );

    return $class;
  }

  /**
   * @param string $namespace
   *
   * @return ResponseGenerator
   */
  public function setNamespace($namespace)
  {
    $this->namespace = $namespace;

    return $this;
  }

  /**
   * @return string
   */
  public function getName()
  {
    return $this->name;
  }

  /**
   * @return array
   */
  public function getColumns()
  {
    return $this->columns;
  }

  /**
   * @return int
   */
  public function getType()
  {
    return $this->type;
  }

  /**
   * @param int $type
   *
   * @return static
   */
  public function setType($type)
  {
    $this->type = $type;

    return $this;
  }
}
