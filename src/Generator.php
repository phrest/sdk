<?php


namespace PhrestSDK;

use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\ParameterGenerator;

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
    return $namespace = $reflect->getNamespaceName() . '\\' . $reflect->getShortName();
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
        ->setName($collection->name)
        ->setDocblock($docblock);

      // Get class & method annotations
      $reader = new \Phalcon\Annotations\Adapter\Memory();
      $reflector = $reader->get($collection->controller);
      $methodAnnotations = $reflector->getMethodsAnnotations();

      // Create methods for each action
      foreach($collection->routes as $route)
      {
        $method = new MethodGenerator();
        $method->setName($route->controllerAction);

        foreach($route->methodParams as $paramName => $paramType)
        {
          $methodParam = new ParameterGenerator($paramName, $paramType);
          $method->setParameter($methodParam);
        }

        $body = sprintf(
          'return parent::%s("%s")',
          $route->type,
          $route->routePattern
        );
        $method->setBody($body);

        $class->addMethodFromGenerator($method);
      }

      echo $class->generate();
    }
  }
}




