<?php

namespace Phrest\SDK\Generator\Helper;

use Phrest\SDK\Generator\Controller\ControllerGenerator;
use Phrest\SDK\Generator\Exception\ExceptionGenerator;
use Phrest\SDK\Generator\Model\ModelGenerator;
use Phrest\SDK\Generator\Response\ResponseGenerator;

class Files
{
  /**
   * @var string
   */
  public static $outputDir;

  /**
   * @var string
   */
  public static $namespace;

  /**
   * @var bool
   */
  public static $force;

  /**
   * @param string $version
   * @param string $entityName
   */
  public static function initializeFolders($version, $entityName)
  {
    $home = self::$outputDir . '/' . $version . '/';

    $folders = [
      'Controllers',
      'Models',
      'Requests',
      'Responses',
      'Exceptions'
    ];

    if (!is_dir($home))
    {
      mkdir($home);
    }

    foreach ($folders as $folder)
    {
      if (!is_dir($home . $folder))
      {
        mkdir($home . $folder);
      }
      if (!is_dir($home . $folder . '/' . $entityName))
      {
        mkdir($home . $folder . '/' . $entityName);
      }
    }
  }

  /**
   * @param $version
   * @param $type
   * @param $entityName
   * @param $className
   *
   * @return string
   */
  public static function formPath($version, $type, $entityName, $className)
  {
    return self::$outputDir . '/'
    . $version . '/'
    . $type . '/'
    . $entityName . '/'
    . $className . '.php';
  }

  /**
   * @param ModelGenerator $generator
   */
  public static function saveModel($generator)
  {
    $generator->setNamespace(
      self::$namespace . '\\'
      . $generator->getVersion() . '\\'
      . 'Models\\'
      . $generator->getEntityName()
    );

    file_put_contents(
      self::formPath(
        $generator->getVersion(),
        'Models',
        $generator->getEntityName(),
        $generator->getName()
      ),
      $generator->generate()
    );
  }

  /**
   * @param ResponseGenerator $generator
   */
  public static function saveResponse($generator)
  {
    $generator->setNamespace(
      self::$namespace . '\\'
      . $generator->getVersion() . '\\'
      . 'Responses\\'
      . $generator->getEntityName()
    );

    file_put_contents(
      self::formPath(
        $generator->getVersion(),
        'Responses',
        $generator->getEntityName(),
        substr($generator->getName(), 0, -1) . 'Response'
      ),
      $generator->setType(ResponseGenerator::SINGULAR)->generate()
    );

    file_put_contents(
      self::formPath(
        $generator->getVersion(),
        'Responses',
        $generator->getEntityName(),
        $generator->getName() . 'Response'
      ),
      $generator->setType(ResponseGenerator::PLURAL)->generate()
    );
  }

  /**
   * @param ExceptionGenerator $generator
   */
  public static function saveException($generator)
  {
    $generator->setNamespace(
      self::$namespace . '\\'
      . $generator->getVersion() . '\\'
      . 'Exceptions\\'
      . $generator->getEntityName()
    );

    file_put_contents(
      self::formPath(
        $generator->getVersion(),
        'Exceptions',
        $generator->getEntityName(),
        $generator->getException()
      ),
      $generator->generate()
    );
  }

  /**
   * @param ControllerGenerator $generator
   */
  public static function saveController($generator)
  {
    $generator->setNamespace(
      self::$namespace . '\\'
      . $generator->getVersion() . '\\'
      . 'Controllers\\'
      . $generator->getEntityName()
    );

    file_put_contents(
      self::formPath(
        $generator->getVersion(),
        'Controllers',
        $generator->getEntityName(),
        $generator->getEntityName() . 'Controller'
      ),
      $generator->generate()
    );
  }
}
