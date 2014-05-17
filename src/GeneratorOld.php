<?php


namespace PhrestSDK;

use Phalcon\Annotations\Reader;
use Phalcon\Exception;
use PhrestAPI\Collections\Collection;
use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\DocBlock\Tag;
use Zend\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\FileGenerator;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\ParameterGenerator;
use Phalcon\Annotations\Adapter\Memory as AnnotationReader;

class Generator
{
  // Desired SDK Class Name
  const SDK_CLASS_NAME = 'className';

  // Class description
  const SDK_CLASS_DESCRIPTION = 'description';

  //Method Parameter
  const SDK_METHOD_PARAM = 'methodParam';

  // Action Post Parameter
  const SDK_POST_PARAM = 'postParam';

  // Method URI
  const SDK_METHOD_URI = 'methodURI';

  // Method description
  const SDK_METHOD_DESCRIPTION = 'description';

  private $sdk;
  private $outputDir;
  private $indentation;

  public function __construct(PhrestSDK $sdk)
  {
    $this->sdk = $sdk;

    // Set the indentation of code
    $this->indentation = '  ';

    // Set the output directory
    $this->outputDir = $this->sdk->srcDir . '/' . $this->getNamespace();
  }

  private function getSDKClassShortName()
  {
    $reflect = new \ReflectionClass($this->sdk);
    return $reflect->getShortName();
  }

  private function getNamespace()
  {
    // Default namespace is the same as the SDK class
    $reflect = new \ReflectionClass($this->sdk);
    return $reflect->getShortName();
  }

  private function getFinalNamespace()
  {
    // Default namespace is the same as the SDK class
    $reflect = new \ReflectionClass($this->sdk);
    return
      $namespace = $reflect->getNamespaceName() . '\\' . $reflect->getShortName(
        );
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
    // todo fail if already created
    @mkdir($this->outputDir, 0777, true);

    return $this;
  }

  /**
   * Gets the SDK Class Name
   * @param $class
   * @return array|string
   * @throws \Exception
   */
  private function getSDKClassName($class)
  {
    $className = $this->getClassAnnotation($class, self::SDK_CLASS_NAME);

    if(!$className)
    {
      $className = 'UseAnnotation_' . self::SDK_CLASS_NAME . '_' . uniqid();
    }

    return $className;
  }

  private function getClassDescription($class)
  {
    $className = $this->getClassAnnotation($class, self::SDK_CLASS_DESCRIPTION);

    if(!$className)
    {
      $className
        = 'UseAnnotation_' . self::SDK_CLASS_DESCRIPTION . '_' . uniqid();
    }

    return $className;
  }

  /**
   * Get Annotation Reader for class
   * @param $class
   * @return \Phalcon\Annotations\Reflection
   */
  private function getClassAnnotationReader($class)
  {
    return (new AnnotationReader())->get($class);
  }

  /**
   * Gets a Class Annotation by key
   * @param $class
   * @param $annotationKey
   * @return bool|mixed
   */
  private function getClassAnnotation($class, $annotationKey)
  {
    $annotations = $this
      ->getClassAnnotationReader($class)
      ->getClassAnnotations();

    if(!$annotations)
    {
      return false;
    }

    try
    {
      return $annotations->get($annotationKey)->getArgument(0);
    }
    catch(\Exception $e)
    {
      return false;
    }
  }

  /**
   * Get a list of method parameters
   *
   * @param $class
   * @param $method
   * @return array
   */
  private function getMethodParams($class, $method)
  {
    $reader = $this->getClassAnnotationReader($class);
    $methodParams = $reader->getMethodsAnnotations();

    if(!isset($methodParams[$method]))
    {
      return [];
    }

    $actionParams = $methodParams[$method]->getAll(self::SDK_METHOD_PARAM);

    $params = [];
    foreach($actionParams as $param)
    {
      $params[$param->getArgument(0)] = 'string';
    }

    return $params;
  }

  /**
   * Gets a list of method post params
   *
   * @param $class
   * @param $method
   * @return array
   */
  private function getPostParams($class, $method)
  {
    $reader = $this->getClassAnnotationReader($class);
    $methodParams = $reader->getMethodsAnnotations();

    if(!isset($methodParams[$method]))
    {
      return [];
    }

    $actionParams = $methodParams[$method]->getAll(self::SDK_POST_PARAM);

    $params = [];
    foreach($actionParams as $param)
    {
      $params[$param->getArgument(0)] = 'string';
    }

    return $params;
  }

  /**
   * Get the action URI
   *
   * @param $class
   * @param $method
   * @return mixed|string
   */
  private function getMethodURI($class, $method)
  {
    $reader = $this->getClassAnnotationReader($class);
    $methodParams = $reader->getMethodsAnnotations();

    if(!isset($methodParams[$method]))
    {
      return '';
    }

    try
    {
      return $methodParams[$method]->get(self::SDK_METHOD_URI)->getArgument(0);
    }
    catch(\Exception $e)
    {
      return '';
    }
  }

  /**
   * Get a method description
   * @param $class
   * @param $method
   * @return mixed|string
   */
  private function getMethodDescription($class, $method)
  {
    $reader = $this->getClassAnnotationReader($class);
    $methodParams = $reader->getMethodsAnnotations();

    if(!isset($methodParams[$method]))
    {
      return '';
    }

    try
    {
      return $methodParams[$method]->get(self::SDK_METHOD_DESCRIPTION)
        ->getArgument(0);
    }
    catch(\Exception $e)
    {
      return '';
    }
  }

  /**
   * @throws \Exception
   */
  private function generateClasses()
  {
    $collections = $this->sdk->app->getCollections();

    foreach($collections as $collection)
    {
      $className = $this->getSDKClassName($collection->controller);

      // Create class for collection
      $classDocblock = new DocBlockGenerator();
      $classDocblock->setShortDescription(
        $this->getClassDescription($collection->controller)
      );
      $class = new ClassGenerator();
      $class
        ->setNamespaceName($this->getFinalNamespace())
        ->setName($className)
        ->addUse(get_class($this->sdk))
        ->setExtendedClass($this->getSDKClassShortName())
        ->setDocblock($classDocblock);

      // Create methods for each action
      foreach($collection->routes as $route)
      {
        // Get action params
        $methodParams = $this->getMethodParams(
          $collection->controller,
          $route->controllerAction
        );

        // Create the method
        $method = new MethodGenerator();
        $method->setIndentation($this->indentation);
        $method->setName($route->controllerAction);
        $method->setStatic(true);

        // Add action params
        foreach($methodParams as $paramName => $paramType)
        {
          $methodParam = new ParameterGenerator($paramName, $paramType);
          $method->setParameter($methodParam);
        }

        // Get uri
        $uri = $this->getMethodURI(
          $collection->controller,
          $route->controllerAction
        );

        // Add post params
        $postParams = $this->getPostParams(
          $collection->controller,
          $route->controllerAction
        );
        if(count($postParams) > 0)
        {
          $methodParam = new ParameterGenerator('params', null, []);
          $method->setParameter($methodParam);
        }

        // Set the method body
        $body = sprintf(
          'return parent::%s("%s%s"%s);',
          $route->type,
          $collection->prefix,
          $uri,
          count($postParams) > 0 ? ', $params' : null
        );
        $method->setBody($body);

        // Set the method docblock
        $method->setDocBlock(
          $this->getMethodDocBlock(
            $collection->controller,
            $route->controllerAction
          )
        );

        // Add method to class
        $class->addMethodFromGenerator($method);
      }

      // Save class to file
      $this->saveClass($class, $className);

    }
  }

  /**
   * Save a class to file
   *
   * @param $class
   * @param $className
   */
  private function saveClass(ClassGenerator $class, $className)
  {
    file_put_contents(
      $this->outputDir . '/' . $className . '.php',
      sprintf(
        "<?php %s%s",
        PHP_EOL,
        $class->generate()
      )
    );
  }

  /**
   * Get the docblock object for a method
   *
   * @param $class
   * @param $method
   * @return DocBlockGenerator
   */
  private function getMethodDocBlock($class, $method)
  {
    $methodDocBlock = new DocBlockGenerator();

    // Set method params
    $methodParams = $this->getMethodParams(
      $class,
      $method
    );
    foreach($methodParams as $paramName => $paramType)
    {
      $param = new Tag\GenericTag();
      $param->setName('param');
      $param->setContent('$' . $paramName . ' ' . $paramType);
      $methodDocBlock->setTag($param);
    }

    // Set method post params
    $postParams = $this->getPostParams(
      $class,
      $method
    );
    foreach($postParams as $paramName => $paramType)
    {
      $param = new Tag\GenericTag();
      $param->setName('postParam');
      $param->setContent(sprintf('"%s" %s', $paramName, $paramType));
      $methodDocBlock->setTag($param);
    }

    // Set method short description
    $methodDescription = $this->getMethodDescription(
      $class,
      $method
    );
    if(!$methodDescription)
    {
      $methodDescription
        = 'Please provide a method description using the @'
        . self::SDK_METHOD_DESCRIPTION . '("...") annotation';
    }
    $methodDocBlock->setShortDescription($methodDescription);

    return $methodDocBlock;
  }
}




