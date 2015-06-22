<?php

namespace Phrest\SDK\Generator\Response;

use Phrest\SDK\Generator\AbstractGenerator;
use Phrest\SDK\Generator\Helper\ClassGen;
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
   * @param        $columns
   */
  public function __construct($version, $entityName, $columns)
  {
    $this->name = $entityName;
    $this->columns = $columns;
    parent::__construct($version, $entityName);
  }

  /**
   * @return string
   */
  public function generate()
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
   * @return string
   */
  public function generateSingular()
  {
    $class = ClassGen::classGen(
      substr($this->name, 0, -1) . 'Response',
      $this->namespace,
      ['Phrest\API\Responses\Response'],
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

    return '<?php' . PHP_EOL . PHP_EOL . $class->generate();
  }

  /**
   * @return string
   */
  public function generatePlural()
  {
    $class = ClassGen::classGen(
      $this->name . 'Response',
      $this->namespace,
      ['Phrest\API\Responses\ResponseArray'],
      'ResponseArray'
    );

    return '<?php' . PHP_EOL . PHP_EOL . $class->generate();
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
