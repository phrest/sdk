<?php


namespace PhrestSDK;

use Phalcon\Annotations\Reader;
use Phalcon\Exception;
use PhrestAPI\Collections\Collection;
use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\ParameterGenerator;
use Phalcon\Annotations\Adapter\Memory as AnnotationReader;

class Generator
{
  private $sdk;
  private $outputDir;

  public function __construct(PhrestSDK $sdk)
  {
    $this->sdk = $sdk;
    $this->outputDir = $this->sdk->srcDir . '/' . $this->getNamespace();
  }

  private function getNamespace()
  {
    // Default namespace is the same as the SDK class
    $reflect = new \ReflectionClass($this->sdk);
    return $reflect->getShortName();
  }

  private function getFinalNamespace()
  {
    // Default namespace is the same as the SDK class
    $reflect = new \ReflectionClass($this->sdk);
    return
      $namespace = $reflect->getNamespaceName() . '\\' . $reflect->getShortName(
        );
  }

  public function generate()
  {
    echo PHP_EOL . "Generating SDK to " . $this->outputDir . PHP_EOL;

    // Make directory
    $this->createOutputDir();

    // Generate classes
    $this->generateClasses();
  }

  /**
   * Create the output directory
   * @return $this
   */
  private function createOutputDir()
  {
    // todo fail if already created
    @mkdir($this->outputDir, 0777, true);

    return $this;
  }

  /**
   * Gets the SDK Class Name
   * @param $class
   * @return array|string
   * @throws \Exception
   */
  private function getSDKClassName($class)
  {
    $className = $this->getClassAnnotation($class, 'sdkClassName');

    if(!$className)
    {
      $className = 'UseAnnotation_sdkClassName_' . uniqid();
    }

    return $className;
  }

  /**
   * Get Annotation Reader for class
   * @param $class
   * @return \Phalcon\Annotations\Reflection
   */
  private function getClassAnnotationReader($class)
  {
    return (new AnnotationReader())->get($class);
  }

  /**
   * Gets a Class Annotation by key
   * @param $class
   * @param $annotationKey
   * @return bool|mixed
   */
  private function getClassAnnotation($class, $annotationKey)
  {
    $annotations = $this
      ->getClassAnnotationReader($class)
      ->getClassAnnotations();

    try
    {
      return $annotations->get($annotationKey)->getArgument(0);
    }
    catch(\Exception $e)
    {
      return false;
    }
  }

  private function getActionParams($class, $method)
  {
    $reader = $this->getClassAnnotationReader($class);
    $methodParams = $reader->getMethodsAnnotations();

    if(!isset($methodParams[$method]))
    {
      return [];
    }

    $actionParams = $methodParams[$method]->getAll('actionParam');

    $params = [];
    foreach($actionParams as $param)
    {
      $params[$param->getArgument(0)] = 'string';
    }

    return $params;
  }

  private function getActionPostParams($class, $method)
  {
    $reader = $this->getClassAnnotationReader($class);
    $methodParams = $reader->getMethodsAnnotations();

    if(!isset($methodParams[$method]))
    {
      return [];
    }

    $actionParams = $methodParams[$method]->getAll('postParam');

    $params = [];
    foreach($actionParams as $param)
    {
      $params[$param->getArgument(0)] = 'string';
    }

    return $params;
  }

  /**
   * Get the action URI
   *
   * @param $class
   * @param $method
   * @return mixed|string
   */
  private function getActionURI($class, $method)
  {
    $reader = $this->getClassAnnotationReader($class);
    $methodParams = $reader->getMethodsAnnotations();

    if(!isset($methodParams[$method]))
    {
      return '';
    }

    try
    {
      return $methodParams[$method]->get('uri')->getArgument(0);
    }
    catch(\Exception $e)
    {
      return '';
    }
  }

  /**
   * @throws \Exception
   */
  private function generateClasses()
  {
    $collections = $this->sdk->app->getCollections();

    foreach($collections as $collection)
    {
      // Create class for collection
      $docblock = new DocBlockGenerator();
      $docblock->setShortDescription('Phrest auto generated SDK class');
      $class = new ClassGenerator();
      $class
        ->setNamespaceName($this->getFinalNamespace())
        ->setName($this->getSDKClassName($collection->controller))
        ->setDocblock($docblock);

      // Create methods for each action
      foreach($collection->routes as $route)
      {
        $method = new MethodGenerator();
        $method->setName($route->controllerAction);
        $method->setStatic(true);

        // Add action params
        $actionParams = $this->getActionParams(
          $collection->controller,
          $route->controllerAction
        );
        foreach($actionParams as $paramName => $paramType)
        {
          $methodParam = new ParameterGenerator($paramName, $paramType);
          $method->setParameter($methodParam);
        }

        // Get uri
        $uri = $this->getActionURI(
          $collection->controller,
          $route->controllerAction
        );

        // Get post param
        $postParams = $this->getActionPostParams(
          $collection->controller,
          $route->controllerAction
        );

        // Add post params
        if(count($postParams) > 0)
        {
          $methodParam = new ParameterGenerator('params', null, []);
          $method->setParameter($methodParam);
        }

        // Set the method body
        $body = sprintf(
          'return parent::%s("%s%s"%s)',
          $route->type,
          $collection->prefix,
          $uri,
          count($postParams) > 0 ? ', $params' : null
        );
        $method->setBody($body);

        $class->addMethodFromGenerator($method);
      }

      echo $class->generate();
    }
  }
}




