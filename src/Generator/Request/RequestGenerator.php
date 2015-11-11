<?php

namespace Phrest\SDK\Generator\Request;

use Phalcon\Config;
use Phrest\SDK\Generator;
use Phrest\SDK\Generator\AbstractGenerator;
use Phrest\SDK\Generator\Helper\ClassGen;
use Zend\Code\Generator\ParameterGenerator;

class RequestGenerator extends AbstractGenerator
{
  /**
   * @var string
   */
  protected $name;

  /**
   * @var Config
   */
  protected $model;

  /**
   * @var string
   */
  protected $requestMethod;

  /**
   * @var string
   */
  protected $actionName;

  /**
   * @var Config
   */
  protected $action;

  /**
   * @var string
   */
  protected $namespace;

  /**
   * @param string $version
   * @param string $entityName
   * @param Config $model
   * @param string $requestMethod
   * @param string $actionName
   * @param Config $action
   */
  public function __construct(
    $version,
    $entityName,
    $model,
    $requestMethod,
    $actionName,
    $action
  )
  {
    $this->name = ucfirst($actionName) . 'Request';
    $this->model = $model;
    $this->requestMethod = $requestMethod;
    $this->actionName = $actionName;
    $this->action = $action;

    parent::__construct($version, $entityName);
  }

  /**
   * Process and create code/files
   */
  public function create()
  {
    $class = ClassGen::classGen(
      $this->name,
      $this->namespace,
      [
        'Phrest\API\Enums\RequestMethodEnum',
        'Phrest\SDK\Request\AbstractRequest',
        'Phrest\SDK\Request\RequestOptions',
        'Phrest\SDK\PhrestSDK'
      ],
      'AbstractRequest'
    );

    // Path
    $path = '/' . $this->version . '/'
      . strtolower($this->entityName)
      . $this->getPlaceholderUriFromUrl($this->action->url);

    $property = ClassGen::property('path', 'private', $path, 'string');
    $class->addPropertyFromGenerator($property);

    // Properties and constructor parameters
    /** @var ParameterGenerator[] $parameters */
    $parameters = [];

    // Get properties
    $getParams = $this->generateGetParamsFromUrl($this->action->url);
    if (!empty($getParams))
    {
      foreach ($getParams as $getParam)
      {
        $class->addPropertyFromGenerator(
          ClassGen::property($getParam, 'public', null)
        );

        $parameter = new ParameterGenerator($getParam);
        $parameter->setDefaultValue(null);

        $parameters[$getParam] = $parameter;
      }
    }

    // Post properties
    if (!empty($this->action->postParams))
    {
      foreach ($this->action->postParams as $name => $type)
      {
        if ($class->hasProperty($name))
        {
          continue;
        }

        $class->addPropertyFromGenerator(
          ClassGen::property($name, 'public', null, $type)
        );

        $parameter = new ParameterGenerator($name, $type);
        $parameter->setDefaultValue(null);

        $parameters[$name] = $parameter;
      }
    }

    // Constructor
    if (!empty($parameters))
    {
      $constructor = ClassGen::constructor($parameters);
      $class->addMethodFromGenerator($constructor);
    }

    // Create method
    $create = ClassGen::method('create', [], 'public', $this->getCreateBody());
    $class->addMethodFromGenerator($create);

    // Setters
    foreach ($parameters as $parameter)
    {
      $class->addMethodFromGenerator(
        ClassGen::setter($parameter->getName(), $parameter->getType())
      );
    }

    return $class;
  }

  /**
   * Replace E.G. /{id:[0-9]+}/{name:[a-z]+} with /%s/%s
   *
   * @param $url
   *
   * @return mixed
   */
  private function getPlaceholderUriFromUrl($url)
  {
    return preg_replace('#\{\w+:[^\}]+\}#i', '%s', $url);
  }

  /**
   * Parse E.G. /{id:[0-9]+}/{name:[a-z]+} into ['id', 'name']
   *
   * @param $url
   *
   * @return array
   */
  private function generateGetParamsFromUrl($url)
  {
    $matches = [];
    preg_match_all('#\{(\w+):[^\}]+\}#i', $url, $matches);

    return $matches[1];
  }

  /**
   * E.G.
   * <code>
   * $requestOptions = new RequestOptions();
   * $requestOptions->addPostParams(
   *  [
   *    'name' => $this->name,
   *    'email' => $this->email,
   *    'password' => $this->password,
   *  ]
   * );
   * if (!isset($this->id))
   * {
   *  throw new \Exception("Parameter 'id' is required. It is a GET
   *  parameter.");
   * }
   * return PhrestSDK::getResponse(
   *  self::METHOD_POST,
   *  $this->path,
   *  $requestOptions
   * );
   * </code>
   *
   * @return string
   */
  public function getCreateBody()
  {
    $createBody = '$requestOptions = new RequestOptions();' . PHP_EOL . PHP_EOL;

    if (!empty($this->action->postParams))
    {
      $createBody .= '$requestOptions->addPostParams(' . PHP_EOL .
        Generator::$indentation . "[" . PHP_EOL;

      foreach ($this->action->postParams as $name => $type)
      {
        $createBody .= sprintf(
          "%s'%s' => \$this->%s,%s",
          Generator::$indentation . Generator::$indentation,
          $name,
          $name,
          PHP_EOL
        );
      }

      $createBody .= Generator::$indentation . "]" . PHP_EOL
        . ');' . PHP_EOL . PHP_EOL;
    }

    $getParams = $this->generateGetParamsFromUrl($this->action->url);
    if ($getParams)
    {
      foreach ($getParams as $getParam)
      {
        $createBody .= 'if (!isset($this->' . $getParam . '))' . PHP_EOL
          . '{' . PHP_EOL
          . Generator::$indentation
          . sprintf(
            'throw new \Exception("Parameter \'%s\' is required. It is a GET parameter.");',
            $getParam
          ) . PHP_EOL
          . '}' . PHP_EOL . PHP_EOL;
      }

      array_walk($getParams,
        function (&$value, $key)
        {
          $value = '$this->' . $value;
        }
      );

      $path = sprintf("sprintf(\$this->path, %s)", implode(', ', $getParams));
    }
    else
    {
      $path = '$this->path';
    }

    $createBody .= 'return PhrestSDK::getResponse(' . PHP_EOL
      . Generator::$indentation
      . 'RequestMethodEnum::' . strtoupper($this->requestMethod) . ',' . PHP_EOL
      . Generator::$indentation
      . $path . ',' . PHP_EOL
      . Generator::$indentation
      . '$requestOptions' . PHP_EOL
      . ');' . PHP_EOL;

    return $createBody;
  }

  /**
   * @param string $namespace
   *
   * @return RequestGenerator
   */
  public function setNamespace($namespace)
  {
    $this->namespace = $namespace;

    return $this;
  }

  /**
   * @return string
   */
  public function getNamespace()
  {
    return $this->namespace;
  }

  /**
   * @return string
   */
  public function getName()
  {
    return $this->name;
  }
}
