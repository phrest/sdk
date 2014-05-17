<?php


namespace PhrestSDK;

use Phalcon\Annotations\Reader;
use Phalcon\Exception;
use PhrestAPI\Collections\Collection;
use PhrestAPI\Collections\CollectionRoute;
use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\DocBlock\Tag;
use Zend\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\FileGenerator;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\ParameterGenerator;
use Phalcon\Annotations\Adapter\Memory as AnnotationReader;

class Generator
{
  private $sdk;
  private $outputDir;
  private $indentation;

  public function __construct(PhrestSDK $sdk)
  {
    $this->sdk = $sdk;

    $this
      ->setDefaultOutputDir()
      ->setDefaultIndentation();
  }

  /**
   * Set the default indentation
   *
   * @return $this
   */
  public function setDefaultIndentation()
  {
    $this->indentation = '  ';

    return $this;
  }

  /**
   * Set the default output directory
   *
   * @return $this
   */
  public function setDefaultOutputDir()
  {
    $this->outputDir = $this->sdk->srcDir;

    return $this;
  }

  /**
   * Set the output directory for the SDK
   *
   * @param $outputDir
   *
   * @return $this
   */
  public function setOutputDir($outputDir)
  {
    $this->outputDir = $outputDir;

    return $this;
  }

  /**
   * Prints a message to the console
   *
   * @param $message
   */
  private function printMessage($message)
  {
    printf('%s%s', PHP_EOL, $message);
  }

  /**
   * Generate the SDK
   */
  public function generate()
  {
    $this->printMessage("Generating SDK...");

    $collections = $this->getCollections();

    // Validate there is anything to do
    if(count($collections) === 0)
    {
      $this->printMessage('No Collections to process');
      exit;
    }

    // Generate SDK classes
    foreach($collections as $collection)
    {
      $controllerClass = $collection->controller;

      // If there are no routes to process
      if(count($collection->routes) === 0)
      {
        $this->printMessage(
          sprintf("No actions to process for %s", $controllerClass)
        );

        continue;
      }

      // Generate requests
      foreach($collection->routes as $route)
      {
        $this->generateRequestClass($collection, $route);
      }
    }
  }

  /**
   * Generate a class for the controller action
   *
   * @param Collection      $collection
   * @param CollectionRoute $route
   *
   * @return $this
   */
  private function generateRequestClass(
    Collection $collection,
    CollectionRoute $route
  )
  {
    // Build the class name
    $className = $this->getRequestClassName($collection, $route);
    $this->printMessage(sprintf('Generating Request: %s', $className));

    return $this;
  }

  /**
   * Get the request class name for a controller action
   * @param Collection      $collection
   * @param CollectionRoute $route
   */
  private function getRequestClassName(
    Collection $collection,
    CollectionRoute $route
  )
  {

    // Get the controller class name
    $controller = new $collection->controller;
    $controllerReflection = new \ReflectionClass($controller);
    $controllerClassName =  $controllerReflection->getShortName();
    $controllerClassName = str_replace('Controller', '', $controllerClassName);

    return sprintf(
      '%s%sRequest',
      $controllerClassName,
      ucfirst($route->controllerAction)
    );
  }

  /**
   * Get the API Collections
   *
   * @return \PhrestAPI\Collections\Collection[]
   * @throws \Exception
   */
  private function getCollections()
  {
    return $this->sdk->app->getCollections();
  }
}




