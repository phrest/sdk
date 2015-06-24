<?php

namespace Phrest\SDK\Generator\Helper;

use Phalcon\Config;
use Phrest\SDK\Generator;
use Phrest\SDK\Generator\Controller\ControllerGenerator;
use Phrest\SDK\Generator\Exception\ExceptionGenerator;
use Phrest\SDK\Generator\Model\ModelGenerator;
use Phrest\SDK\Generator\Request\RequestGenerator;
use Phrest\SDK\Generator\Response\ResponseGenerator;

class Files
{
  /**
   * @var string
   */
  public static $outputDir;

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
   * @param array $config
   */
  public static function saveCollectionConfig($config)
  {
    $folder = self::$outputDir . '/Config';

    yaml_emit_file(
      $folder . '/collections.yaml', $config
    );
  }

  /**
   * @param ModelGenerator $generator
   */
  public static function saveModel($generator)
  {
    $generator->setNamespace(
      Generator::$namespace . '\\'
      . $generator->getVersion() . '\\'
      . 'Models\\'
      . $generator->getEntityName()
    );

    $filePath = self::formPath(
      $generator->getVersion(),
      'Models',
      $generator->getEntityName(),
      $generator->getName()
    );

    $class = $generator->create();

    if (is_file($filePath))
    {
      $diff = new ClassDiff(
        $filePath,
        $class->getNamespaceName() . '\\' . $class->getName(),
        $class
      );

      $class = $diff->merge();
    }

    file_put_contents(
      $filePath,
      self::generatorToString($class)
    );
  }

  /**
   * @param RequestGenerator $generator
   */
  public static function saveRequest($generator)
  {
    $generator->setNamespace(
      Generator::$namespace . '\\'
      . $generator->getVersion() . '\\'
      . 'Requests\\'
      . $generator->getEntityName()
    );

    $filePath = self::formPath(
      $generator->getVersion(),
      'Requests',
      $generator->getEntityName(),
      $generator->getName()
    );

    $class = $generator->create();

    if (is_file($filePath))
    {
      $diff = new ClassDiff(
        $filePath,
        $class->getNamespaceName() . '\\' . $class->getName(),
        $class
      );

      $class = $diff->merge();
    }

    file_put_contents(
      $filePath,
      self::generatorToString($class)
    );
  }

  /**
   * @param ResponseGenerator $generator
   */
  public static function saveResponse($generator)
  {
    $generator->setNamespace(
      Generator::$namespace . '\\'
      . $generator->getVersion() . '\\'
      . 'Responses\\'
      . $generator->getEntityName()
    );

    $filePath = self::formPath(
      $generator->getVersion(),
      'Responses',
      $generator->getEntityName(),
      substr($generator->getName(), 0, -1) . 'Response'
    );

    $class = $generator->setType(ResponseGenerator::SINGULAR)->create();

    if (is_file($filePath))
    {
      $diff = new ClassDiff(
        $filePath,
        $class->getNamespaceName() . '\\' . $class->getName(),
        $class
      );

      $class = $diff->merge();
    }

    file_put_contents(
      $filePath,
      self::generatorToString($class)
    );

    $filePath = self::formPath(
      $generator->getVersion(),
      'Responses',
      $generator->getEntityName(),
      $generator->getName() . 'Response'
    );

    $class = $generator->setType(ResponseGenerator::PLURAL)->create();

    if (is_file($filePath))
    {
      $diff = new ClassDiff(
        $filePath,
        $class->getNamespaceName() . '\\' . $class->getName(),
        $class
      );

      $class = $diff->merge();
    }

    file_put_contents(
      $filePath,
      self::generatorToString($class)
    );
  }

  /**
   * @param ExceptionGenerator $generator
   */
  public static function saveException($generator)
  {
    $generator->setNamespace(
      Generator::$namespace . '\\'
      . $generator->getVersion() . '\\'
      . 'Exceptions\\'
      . $generator->getEntityName()
    );

    $filePath = self::formPath(
      $generator->getVersion(),
      'Exceptions',
      $generator->getEntityName(),
      $generator->getException()
    );

    $class = $generator->create();

    if (is_file($filePath))
    {
      $diff = new ClassDiff(
        $filePath,
        $class->getNamespaceName() . '\\' . $class->getName(),
        $class
      );

      $class = $diff->merge();
    }

    file_put_contents(
      $filePath,
      self::generatorToString($class)
    );
  }

  /**
   * @param ControllerGenerator $generator
   */
  public static function saveController($generator)
  {
    $generator->setNamespace(
      Generator::$namespace . '\\'
      . $generator->getVersion() . '\\'
      . 'Controllers\\'
      . $generator->getEntityName()
    );

    $filePath = self::formPath(
      $generator->getVersion(),
      'Controllers',
      $generator->getEntityName(),
      $generator->getEntityName() . 'Controller'
    );

    $class = $generator->create();

    if (is_file($filePath))
    {
      $diff = new ClassDiff(
        $filePath,
        $class->getNamespaceName() . '\\' . $class->getName(),
        $class
      );

      $class = $diff->merge();
    }

    file_put_contents(
      $filePath,
      self::generatorToString($class)
    );
  }

  /**
   * @param \Zend\Code\Generator\GeneratorInterface $class
   *
   * @return string
   */
  public static function generatorToString($class)
  {
    return '<?php' . PHP_EOL . PHP_EOL . $class->generate();
  }
}
