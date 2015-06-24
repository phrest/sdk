<?php

namespace Phrest\SDK;

use Phalcon\Config;
use Phrest\SDK\Generator\Config\ConfigGenerator;
use Phrest\SDK\Generator\Controller\ControllerGenerator;
use Phrest\SDK\Generator\Exception\ExceptionGenerator;
use Phrest\SDK\Generator\Helper\Files;
use Phrest\SDK\Generator\Model\ModelGenerator;
use Phrest\SDK\Generator\Request\RequestGenerator;
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
   * @var string
   */
  public static $namespace;

  /**
   * @var string
   */
  public static $indentation = '  ';

  /**
   * @var bool
   */
  public static $force;

  /**
   * @param PhrestSDK $sdk
   * @param Config    $config
   * @param string    $namespace
   */
  public function __construct(PhrestSDK $sdk, $config, $namespace = 'SDK')
  {
    $this->sdk = $sdk;
    $this->config = $config;

    Files::$outputDir = $this->sdk->srcDir;
    Generator::$namespace = rtrim($namespace, '\\');
  }

  /**
   * Generate the SDK
   */
  public function generate()
  {
    $this->printMessage('');

    $this->printMessage(
      'Creating Collection config'
    );

    Files::saveCollectionConfig((new ConfigGenerator($this->config))->create());

    $this->printMessage(
      'Creating Models, Controllers, Requests, Responses, and Exceptions'
    );

    /**
     * @var string $name
     * @var Config $entity
     */
    foreach ($this->config as $version => $api)
    {
      $this->printMessage($version . '...');
      foreach ($api as $entityName => $entity)
      {
        $entity = $this->vaidateEntityConfig($entityName, $entity);

        Files::initializeFolders($version, $entityName);

        if (isset($entity->model))
        {
          $columns = $entity->model->columns;

          // Models
          Files::saveModel(
            new ModelGenerator($version, $entityName, $columns)
          );

          // Requests
          foreach ($entity->requests as $requestMethod => $actions)
          {
            foreach ($actions as $actionName => $action)
            {
              if (empty($action))
              {
                continue;
              }
              Files::saveRequest(
                new RequestGenerator(
                  $version,
                  $entityName,
                  $entity->model,
                  $requestMethod,
                  $actionName,
                  $action
                )
              );
            }
          }

          // Responses
          Files::saveResponse(
            new ResponseGenerator($version, $entityName, $columns)
          );
        }

        // Exceptions
        $exceptions = $this->getExceptionsFromEntityConfig($entity);
        foreach ($exceptions as $exception)
        {
          Files::saveException(
            new ExceptionGenerator($version, $entityName, $exception)
          );
        }

        // Controllers
        Files::saveController(
          new ControllerGenerator($version, $entityName, $entity)
        );
      }
    }

    $this->printMessage("All done, Remember to add the files to VCS!");
  }

  /**
   * @param string $entityName
   * @param Config $entity
   */
  public function vaidateEntityConfig($entityName, $entity)
  {
    if (substr($entityName, -1) != 's')
    {
      $this->printMessage(
        "Entity: {$entityName} doesn't end with 's'. Strange names might occur"
      );
    }

    if (empty($entity->requests))
    {
      $this->printMessage(
        "Entity: {$entityName} doesn't have any requests set up for it."
        . " Controllers, exceptions, requests will be skipped."
      );
    }
    else
    {
      foreach ($entity->requests as $requestMethod => $actions)
      {
        foreach ($actions as $actionName => $action)
        {
          if (empty($action->url))
          {
            $this->printMessage(
              "Entity: {$entityName}::{$actionName} doesn't have a url set."
              . " This action will be skipped."
            );
            $entity->requests->$requestMethod->$actionName = false;
          }

          if (!empty($action->throws))
          {
            if (!empty($action->throws->extends))
            {
              if (!class_exists("Phrest\\API\\Exceptions\\" . $action->throws->extends))
              {
                $this->printMessage(
                  "Entity: {$entityName}::{$actionName} Unknown exception: "
                  . "Phrest\\API\\Exceptions\\" . $action->throws->extends
                  . ". This extension will be removed."
                );
                $action->throws->extends = false;
              }
            }
          }
        }
      }
    }

    if (empty($entity->model))
    {
      $this->printMessage(
        "Entity: {$entityName} doesn't have a model definition. Models and "
        . "responses will be skipped."
      );
    }

    return $entity;
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
