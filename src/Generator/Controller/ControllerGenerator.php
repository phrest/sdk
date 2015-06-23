<?php

namespace Phrest\SDK\Generator\Controller;

use Phalcon\Config;
use Phrest\SDK\Generator;
use Phrest\SDK\Generator\AbstractGenerator;
use Phrest\SDK\Generator\Helper\ClassGen;
use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\DocBlock\Tag\GenericTag;
use Zend\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\ParameterGenerator;

class ControllerGenerator extends AbstractGenerator
{

  /**
   * @var Config
   */
  protected $entityDefinition;

  /**
   * @var string
   */
  protected $namespace;

  /**
   * @var array
   */
  protected $uses = [];

  /**
   * @param string $version
   * @param string $entityName
   * @param Config $entityDefinition
   */
  public function __construct($version, $entityName, $entityDefinition)
  {
    $this->entityDefinition = $entityDefinition;

    parent::__construct($version, $entityName);
  }

  /**
   * Process and create code/files
   *
   * @return ClassGenerator
   */
  public function create()
  {
    $class = ClassGen::classGen(
      $this->entityName . 'Controller',
      $this->namespace,
      ['Phrest\API\Controllers\RESTController'],
      'RESTController'
    );

    foreach ($this->entityDefinition->requests as $requestMethod => $actions)
    {
      foreach ($actions as $actionName => $action)
      {
        if (empty($action))
        {
          continue;
        }

        $params = $this->generateParamsFromUrl($action->url);
        $docblock = $this->createDocblockForMethod(
          $requestMethod,
          $action,
          $params
        );

        $method = ClassGen::method($actionName, $params, 'public');
        $method->setDocBlock($docblock);

        $body = $this->createBodyForMethod($action);
        $method->setBody($body);

        $class->addMethodFromGenerator($method);
      }
    }

    foreach ($this->uses as $use)
    {
      $class->addUse($use);
    }

    return $class;
  }

  /**
   * @param string               $requestMethod
   * @param Config               $action
   * @param ParameterGenerator[] $params
   *
   * @return DocBlockGenerator
   */
  private function createDocblockForMethod($requestMethod, $action, $params)
  {
    if (empty($action->doc))
    {
      $action->doc = 'TODO';
    }

    /*
     * $action->doc
     * METHOD: /{url:[a-z]+}
     */
    $docblock = new DocBlockGenerator(
      $action->doc,
      strtoupper($requestMethod) . ': ' . $action->url
    );

    foreach ($params as $param)
    {
      /*
       * @param $paramName
       */
      $docblock->setTag(new GenericTag('param', "\${$param->getName()}"));
    }

    if (!empty($action->throws))
    {
      /*
       * @throws \Name\Space\Version\Exceptions\EntityName\SomethingException
       */
      $docblock->setTag(
        new GenericTag(
          'throws',
          sprintf(
            "\\%s\\%s\\Exceptions\\%s\\%s",
            Generator::$namespace,
            $this->version,
            $this->entityName,
            $action->throws->exception
          )
        )
      );
    }

    if (!empty($action->returns))
    {
      /*
       * @returns \Name\Space\Version\EntityName\EntityName(s)?Response
       */
      $docblock->setTag(
        new GenericTag(
          'returns',
          sprintf(
            "\\%s\\%s\\Responses\\%s\\%s",
            Generator::$namespace,
            $this->version,
            $this->entityName,
            $action->returns
          )
        )
      );
    }

    return $docblock;
  }

  /**
   * @param Config $action
   *
   * @return string
   */
  private function createBodyForMethod($action)
  {
    $body = '//todo' . PHP_EOL;

    if (!empty($action->throws))
    {
      $exceptionClass = sprintf(
        "\\%s\\%s\\Exceptions\\%s\\%s",
        Generator::$namespace,
        $this->version,
        $this->entityName,
        $action->throws->exception
      );

      $this->addUse($exceptionClass);

      $body .= sprintf(
        "if (false)" . PHP_EOL
        . "{" . PHP_EOL
        . "%sthrow new %s('%s');" . PHP_EOL
        . "}" . PHP_EOL,
        Generator::$indentation,
        $action->throws->exception,
        $action->throws->message
      );
    }

    if (!empty($action->returns))
    {
      $responseClass = sprintf(
        "\\%s\\%s\\Responses\\%s\\%s",
        Generator::$namespace,
        $this->version,
        $this->entityName,
        $action->returns
      );

      $this->addUse($responseClass);

      $params = [];
      if (class_exists($responseClass))
      {
        if (method_exists($responseClass, '__construct'))
        {
          $constructor = new \ReflectionMethod($responseClass, '__construct');
          foreach ($constructor->getParameters() as $param)
          {
            $params[] = '$' . $param->getName();
          }
        }
      }

      $body .= sprintf(
        "return new %s(%s);",
        $action->returns,
        implode(', ', $params)
      );
    }

    return $body;
  }

  /**
   * Parse E.G. /{id:[0-9]+}/{name:[a-z]+} into ['id', 'name']
   *
   * @param $url
   *
   * @return ParameterGenerator[]
   */
  private function generateParamsFromUrl($url)
  {
    $matches = [];
    preg_match_all('#\{(\w+):[^\}]+\}#i', $url, $matches);

    $params = [];
    foreach ($matches[1] as $param)
    {
      $params[] = new ParameterGenerator($param);
    }

    return $params;
  }

  /**
   * @param $use
   */
  private function addUse($use)
  {
    if (!in_array($use, $this->uses))
    {
      $this->uses[] = ltrim($use, '\\');
    }
  }

  /**
   * @param string $namespace
   *
   * @return ControllerGenerator
   */
  public function setNamespace($namespace)
  {
    $this->namespace = $namespace;

    return $this;
  }
}
