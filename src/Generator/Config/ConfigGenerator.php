<?php

namespace Phrest\SDK\Generator\Config;

use Phalcon\Config;
use Phrest\SDK\Generator\GeneratorInterface;

class ConfigGenerator implements GeneratorInterface
{
  /**
   * @var Config
   */
  protected $buildConfig;

  /**
   * CollectionConfigManager constructor.
   *
   * @param Config          $collectionConfig
   */
  public function __construct(Config $buildConfig)
  {
    $this->buildConfig = $buildConfig;
  }

  /**
   * @return array
   */
  public function create()
  {
    $collectionConfigs = [];

    foreach ($this->buildConfig as $version => $collections)
    {
      $collectionConfig = [];
      foreach ($collections as $entityName => $entity)
      {
        foreach ($entity->requests as $requestMethod => $requests)
        {
          foreach ($requests as $actionName => $action)
          {
            $collectionConfig[$entityName][$requestMethod][$actionName] = $action->url;
          }
        }
      }
      $collectionConfigs[$version] = $collectionConfig;
    }
    return $collectionConfigs;
  }
}
