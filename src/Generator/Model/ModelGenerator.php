<?php

namespace Phrest\SDK\Generator\Model;

use Phrest\SDK\Generator;
use Phrest\SDK\Generator\AbstractGenerator;
use Phrest\SDK\Generator\Helper\ClassGen;
use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\DocBlock\Tag;
use Zend\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\ParameterGenerator;
use Zend\Code\Generator\PropertyGenerator;
use Zend\Code\Reflection\DocBlock\Tag\PropertyTag;

class ModelGenerator extends AbstractGenerator
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
   * @param string $version
   * @param string $entityName
   * @param        $columns
   */
  public function __construct($version, $entityName, $columns)
  {
    $this->name = $entityName;
    $this->columns = $columns;
    parent::__construct($version, $entityName);
  }

  public function generate()
  {
    $class = ClassGen::classGen(
      $this->name,
      $this->namespace,
      ['Phalcon\Mvc\Model'],
      'Model'
    );

    foreach ($this->columns as $name => $type)
    {
      $property = ClassGen::property($name, 'public', null, $type);
      $class->addPropertyFromGenerator($property);
    }

    $class->addMethodFromGenerator(
      ClassGen::method('initialize')
    );

    return '<?php' . PHP_EOL . PHP_EOL . $class->generate();
  }

  /**
   * @param string $namespace
   *
   * @return ModelGenerator
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
}
