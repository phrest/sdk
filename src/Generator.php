<?php


namespace PhrestSDK;

use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\DocBlockGenerator;

class Generator
{
  private $sdk;
  private $outputDir;

  public function __construct(PhrestSDK $sdk)
  {
    $this->sdk = $sdk;
    $this->outputDir = $this->sdk->srcDir . '/gen/' . date('Y-m-d-h-i-s');
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
    mkdir($this->outputDir, 0777, true);

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
      echo $collection->controller;

      $class = new ClassGenerator();
      $docblock = DocBlockGenerator::fromArray(
        array(
          'shortDescription' => 'Phrest SDK generated class',
          'longDescription' => 'This is a class generated with Phrest',
          'tags' => array(
            array(
              'name' => 'version',
              'description' => '$Rev:$',
            ),
            array(
              'name' => 'license',
              'description' => 'New BSD',
            ),
          ),
        )
      );
      $class
        ->setName('Foo')
        ->setDocblock($docblock);
      echo $class->generate();

      foreach($collection->routes as $route)
      {
        echo $route->action;
      }
    }
  }
}




