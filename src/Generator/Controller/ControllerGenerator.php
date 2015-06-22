<?php

namespace Phrest\SDK\Generator\Controller;

use Phalcon\Config;
use Phrest\SDK\Generator\AbstractGenerator;
use Phrest\SDK\Generator\Helper\ClassGen;
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

  public function __construct($version, $entityName, $entityDefinition)
  {
    $this->entityDefinition = $entityDefinition;

    parent::__construct($version, $entityName);
  }

  /**
   * Process and generate code/files
   * @return string
   */
  public function generate()
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
        if (empty($action->doc))
        {
          $action->doc = 'TODO';
        }
        $docblock = new DocBlockGenerator(
          $action->doc,
          strtoupper($requestMethod) . ': ' . $action->url
        );
        $docblock->setTag(new GenericTag('description', "('{$action->doc}')"));



        if (!empty($action->throws))
        {

        }

        $params = $this->generateParamsFromUrl($action->url);

        foreach ($params as $param)
        {
          $docblock->setTag(new GenericTag('param', "\${$param->getName()}"));
          $docblock->setTag(new GenericTag('methodParam', "('{$param->getName()}')"));
        }

        $docblock->setTag(new GenericTag('uri', "('{$param->getName()}')"));

        $method = ClassGen::method($actionName, $params, 'public');
        $method->setDocBlock($docblock);

        $class->addMethodFromGenerator($method);
      }

    }

    return '<?php' . PHP_EOL . PHP_EOL . $class->generate();
  }

  /**
   * Parse E.G. /{id:[0-9]+}/{name:[a-z]+} into ['id', 'name']
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
