<?php

namespace Phrest\SDK\Generator\Config\Collection;

use Phalcon\Config;
use Phrest\SDK\Generator\Config\ConfigManager;
use Phrest\SDK\Generator\GeneratorInterface;
use Phalcon\Config\Adapter\Yaml;

class CollectionConfigGenerator implements GeneratorInterface
{
  /**
   * @var ConfigManager
   */
  protected $configManager;

  /**
   * @var Config
   */
  protected $collectionConfig;

  /**
   * CollectionConfigManager constructor.
   *
   * @param ConfigManager $configManager
   * @param Yaml          $collectionConfig
   */
  public function __construct(ConfigManager $configManager, Config $buildConfig)
  {
    $this->configManager = $configManager;

    foreach ($buildConfig as $entityName => $entityConfig)
    {
      if (isset($entityConfig->collection))
      {
        $this->collectionConfig[$entityName] = $entityConfig->collection;
      }
    }
  }

  /**
   * Add all collection configs to the main config file and save it
   */
  public function generate()
  {
    $config = $this->configManager->getConfig();

    if (isset($config->collections))
    {
      $merge['collections'] = $config->collections->toArray();
    }
    else {
      $merge['collections'] = [];
    }

    foreach ($this->collectionConfig as $name => $collection)
    {
      $merge['collections'][$name] = $collection;
    }

    $config->merge(new Config($merge));

    $this->configManager->setConfig($config);

    $this->configManager->save();
  }
}
