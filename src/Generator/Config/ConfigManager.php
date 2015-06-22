<?php

namespace Phrest\SDK\Generator\Config;

use Phalcon\Config;
use Phalcon\Config\Adapter\Yaml;

class ConfigManager
{
  /**
   * @var Config
   */
  protected $config;

  /**
   * @var string
   */
  protected $filePath;

  /**
   * ConfigGenerator constructor.
   *
   * @param object   $config
   * @param string $filePath
   */
  public function __construct($config, $filePath)
  {
    $this->config = $config;
    $this->filePath = $filePath;
  }

  /**
   * Save the config
   */
  public function save()
  {
    $this->config = $this->config->toArray();
    yaml_emit_file($this->filePath, $this->config);
  }

  /**
   * @return Config
   */
  public function getConfig()
  {
    return $this->config;
  }

  /**
   * @param Config $config
   *
   * @return ConfigManager
   */
  public function setConfig($config)
  {
    $this->config = $config;

    return $this;
  }
}
