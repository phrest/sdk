<?php

namespace Phrest\SDK;

use Phalcon\Config;
use Phrest\SDK\Generator\Controller\ControllerGenerator;
use Phrest\SDK\Generator\Exception\ExceptionGenerator;
use Phrest\SDK\Generator\Helper\Files;
use Phrest\SDK\Generator\Model\ModelGenerator;
use Phrest\SDK\Generator\Response\ResponseGenerator;

class Generator
{

  /**
   * @var PhrestSDK
   */
  private $sdk;

  /**
   * @var Config
   */
  private $config;

  /**
   * @param PhrestSDK $sdk
   * @param string    $namespace
   */
  public function __construct(PhrestSDK $sdk, $namespace = 'SDK')
  {
    $this->sdk = $sdk;
    $this->config = $this->sdk->app->di->get('config');

    Files::$outputDir = $this->sdk->srcDir;
    Files::$namespace = $namespace;
  }

  /**
   * Generate the SDK
   */
  public function generate()
  {
    $this->printMessage("");
    $this->printMessage('Creating Models, Controllers, Requests, Responses, and Exceptions');

    /**
     * @var string $name
     * @var Config $entity
     */
    foreach ($this->config->apis as $version => $api)
    {
      $this->printMessage($version . '...');
      foreach ($api as $entityName => $entity)
      {
        Files::initializeFolders($version, $entityName);

        // Controllers
        echo (new ControllerGenerator($version, $entityName, $entity))->generate();

        Files::saveController(new ControllerGenerator($version, $entityName, $entity));

        if (isset($entity->model))
        {
          // Models
          Files::saveModel(
            new ModelGenerator($version, $entityName, $entity->model->columns)
          );

          // Responses
          Files::saveResponse(
            new ResponseGenerator(
              $version,
              $entityName,
              $entity->model->columns
            )
          );
        }

        // Exceptions
        $exceptions = $this->getExceptionsFromEntityConfig($entity);
        foreach ($exceptions as $exception)
        {
          Files::saveException(
            new ExceptionGenerator(
              $version,
              $entityName,
              $exception->exception,
              $exception->message,
              $exception->extends
            )
          );
        }
      }
    }

    $this->printMessage("All done, Remember to add the files to git!");
  }

  /**
   * @param Config $entity
   *
   * @return \stdClass[]
   */
  public function getExceptionsFromEntityConfig($entity)
  {
    $exceptions = [];

    foreach ($entity->requests as $request)
    {
      foreach ($request as $action)
      {
        if (isset($action->throws))
        {
          $exceptions[] = $action->throws;
        }
      }
    }

    return $exceptions;
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
    $this->outputDir = rtrim($outputDir, '/');

    return $this;
  }

  /**
   * Prints a message to the console
   *
   * @param $message
   */
  private function printMessage($message)
  {
    printf('%s%s', $message, PHP_EOL);

    return $this;
  }
}
