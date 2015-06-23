<?php

namespace Phrest\SDK\Generator;

use Phrest\SDK\Generator;

abstract class AbstractGenerator implements GeneratorInterface
{
  /**
   * @var string
   */
  protected $version;

  /**
   * @var string
   */
  protected $entityName;

  /**
   * AbstractGenerator constructor.
   *
   * @param string $version
   * @param string $entityName
   */
  public function __construct($version, $entityName)
  {
    $this->version = $version;
    $this->entityName = $entityName;
  }

  /**
   * @param string $version
   *
   * @return AbstractGenerator
   */
  public function setVersion($version)
  {
    $this->version = $version;

    return $this;
}

  /**
   * @return string
   */
  public function getVersion()
  {
    return $this->version;
  }

  /**
   * @param string $entityName
   *
   * @return AbstractGenerator
   */
  public function setEntityName($entityName)
  {
    $this->entityName = $entityName;

    return $this;
}

  /**
   * @return string
   */
  public function getEntityName()
  {
    return $this->entityName;
  }
}
