<?php

namespace Phrest\SDK\Generator\SDK;

use Phrest\SDK\Generator;
use Phrest\SDK\Generator\AbstractGenerator;
use Phrest\SDK\Generator\Helper\ClassGen;
use Phrest\SDK\Generator\Request\RequestGenerator;
use Zend\Code\Generator\DocBlock\Tag\GenericTag;
use Zend\Code\Generator\ParameterGenerator;

class SDKGenerator extends AbstractGenerator
{

  /** @var string */
  protected $name;

  /** @var RequestGenerator[] */
  protected $requests;

  /**
   * @var string
   */
  protected $namespace;

  /**
   * SDKGenerator constructor.
   *
   * @param string             $version
   * @param string             $name
   * @param RequestGenerator[] $requests
   */
  public function __construct($version, $name, array $requests)
  {
    $this->name = $name . 'SDK' . $version;
    $this->requests = $requests;

    parent::__construct($version, 'SDK');
  }

  /**
   * Process and create code/files
   */
  public function create()
  {
    $uses = [
      'Phrest\API\DI\PhrestDIInterface',
      'Phrest\SDK\PhrestSDK',
      'Phrest\SDK\Request\AbstractRequest',
      $this->namespace . '\\' . Generator::$name . 'API'
    ];

    foreach($this->requests as $request)
    {
      $uses[] = $request->getNamespace() . '\\' . $request->getName();
    }

    $class = ClassGen::classGen(
      $this->name,
      $this->namespace . '\\' . $this->version,
      $uses,
      'PhrestSDK'
    );

    $di = new ParameterGenerator('di', 'PhrestDIInterface');
    $constructor = ClassGen::constructor([$di]);
    $constructor->setBody($this->getConstructorBody());
    $class->addMethodFromGenerator($constructor);

    $abstractRequest = new ParameterGenerator('request', 'AbstractRequest');
    $injectDependencies = ClassGen::method(
      'injectDependencies',
      [$abstractRequest],
      'private',
      'return $request;'
    );
    $docBlock = $injectDependencies->getDocBlock();
    $docBlock->setTag(new GenericTag('return', 'AbstractRequest'));
    $class->addMethodFromGenerator($injectDependencies);

    foreach($this->requests as $request)
    {
      $getRequest = ClassGen::method(
        'get' . ucfirst($request->getName()),
        [],
        'public',
        'return $this->injectDependencies(new ' . $request->getName() . '());'
      );
      $getRequest->getDocBlock()->setTag(new GenericTag('return', $request->getName()));
      $class->addMethodFromGenerator($getRequest);
    }

    return $class;
  }

  /**
   * @return string
   */
  public function getConstructorBody()
  {
    return sprintf(
      '$this->setApp(new %sAPI($di, null, true));%s%sparent::__construct(realpath(__DIR__));',
      Generator::$name,
      PHP_EOL,
      PHP_EOL
    );
  }

  /**
   * @param string $namespace
   *
   * @return RequestGenerator
   */
  public function setNamespace($namespace)
  {
    $this->namespace = $namespace;

    return $this;
  }

  /**
   * @return string
   */
  public function getName()
  {
    return $this->name;
  }

}
