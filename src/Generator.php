<?php


namespace PhrestSDK;

use Phalcon\Annotations\Reader;
use Phalcon\Exception;
use PhrestAPI\Collections\Collection;
use PhrestAPI\Collections\CollectionRoute;
use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\DocBlock\Tag;
use Zend\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\FileGenerator;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\ParameterGenerator;
use Phalcon\Annotations\Adapter\Memory as AnnotationReader;
use Zend\Code\Generator\DocBlock\Tag\GenericTag as DocBlockTag;

class Generator
{
  const CLASS_TYPE_REQUEST = 'Request';

  // Action description
  const DOC_ACTION_DESCRIPTION = 'description';
  const DOC_ACTION_METHOD_PARAM = 'methodParam';

  private $sdk;
  private $outputDir;
  private $indentation;

  public function __construct(PhrestSDK $sdk)
  {
    $this->sdk = $sdk;

    $this
      ->setDefaultOutputDir()
      ->setDefaultIndentation();
  }

  /**
   * Set the default indentation
   *
   * @return $this
   */
  public function setDefaultIndentation()
  {
    $this->indentation = '  ';

    return $this;
  }

  /**
   * Set the default output directory
   *
   * @return $this
   */
  public function setDefaultOutputDir()
  {
    $this->outputDir = $this->sdk->srcDir;

    return $this;
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
    $this->outputDir = $outputDir;

    return $this;
  }

  /**
   * Prints a message to the console
   *
   * @param $message
   */
  private function printMessage($message)
  {
    printf('%s%s', PHP_EOL, $message);
  }

  /**
   * Create the required directories
   *
   * @return $this
   */
  private function createDirectories()
  {
    $directories = [
      $this->outputDir,
      sprintf('%s/%s', $this->outputDir, self::CLASS_TYPE_REQUEST),
    ];
    foreach($directories as $directory)
    {
      mkdir($directory, 0777, true);
    }

    return $this;
  }

  /**
   * Generate the SDK
   */
  public function generate()
  {
    $this->printMessage("Generating SDK");

    $this->printMessage("Creating directories");
    $this->createDirectories();

    $collections = $this->getCollections();

    // Validate there is anything to do
    if(count($collections) === 0)
    {
      $this->printMessage('No Collections to process');
      exit;
    }

    // Generate SDK classes
    foreach($collections as $collection)
    {
      $controllerClass = $collection->controller;

      // If there are no routes to process
      if(count($collection->routes) === 0)
      {
        $this->printMessage(
          sprintf("No actions to process for %s", $controllerClass)
        );

        continue;
      }

      // Generate requests
      foreach($collection->routes as $route)
      {
        $this->generateRequestClass($collection, $route);
      }
    }
  }

  /**
   * Generate a class for the controller action
   *
   * @param Collection      $collection
   * @param CollectionRoute $route
   *
   * @return $this
   */
  private function generateRequestClass(
    Collection $collection,
    CollectionRoute $route
  )
  {
    $className = $this->getRequestClassName($collection, $route);
    $this->printMessage(sprintf('Generating Request: %s', $className));

    // Get the class docblock
    $docBlock = $this->getRequestClassDocBlock($collection, $route);

    // Generate the class
    $class = new ClassGenerator();
    $class
      //->setNamespaceName($this->getFinalNamespace())
      ->setName($className)
      ->addUse(get_class($this->sdk))
      //->setExtendedClass($this->getSDKClassShortName())
      ->setDocblock($docBlock);

    // Save class
    $this->saveClass($class);

    return $this;
  }

  /**
   * Save a class to file
   *
   * @param ClassGenerator $class
   * @param string         $type
   *
   * @return int
   */
  private function saveClass(
    ClassGenerator $class,
    $type = self::CLASS_TYPE_REQUEST
  )
  {
    $className = $class->getName();
    $fileName = sprintf('%s/%s/%s.php', $this->outputDir, $type, $className);
    $fileContent = sprintf(
      "<?php %s%s",
      PHP_EOL,
      $class->generate()
    );

    return file_put_contents(
      $fileName,
      $fileContent
    );
  }

  /**
   * Get the DockBlock for a request class
   *
   * @param Collection      $collection
   * @param CollectionRoute $route
   *
   * @return DocBlockGenerator
   */
  private function getRequestClassDocBlock(
    Collection $collection,
    CollectionRoute $route
  )
  {
    $docBlock = new DocBlockGenerator();

    // Description
    $description = $this->getActionAnnotation(
      $collection,
      $route,
      self::DOC_ACTION_DESCRIPTION
    );
    if(!$description)
    {
      $description = sprintf(
        "Please use annotation @%s('Details...')",
        self::DOC_ACTION_DESCRIPTION
      );
    }
    $docBlock->setShortDescription($description);

    // Method (Class) params
    $methodParams = $this->getActionMethodParams($collection, $route);
    if($methodParams)
    {
      $docBlock->setTags($methodParams);
    }

    return $docBlock;
  }

  /**
   * Get Action Method Params
   *
   * @param Collection      $collection
   * @param CollectionRoute $route
   *
   * @return DocBlockTag[]|bool
   */
  private function getActionMethodParams(
    Collection $collection,
    CollectionRoute $route
  )
  {
    $methodParams = $this->getActionAnnotations(
      $collection,
      $route,
      self::DOC_ACTION_METHOD_PARAM
    );

    if(!$methodParams)
    {
      return false;
    }

    $tags = [];
    foreach($methodParams as $paramData)
    {
      // todo recognise types here
      $paramName = $paramData;
      $paramType = "string";

      $tag = new DocBlockTag();
      $tag->setName('param');
      $tag->setContent(sprintf('$%s %s', $paramName, $paramType));

      $tags[] = $tag;
    }

    return $tags;
  }

  /**
   * Get an array of action annotations by type
   *
   * @param Collection      $collection
   * @param CollectionRoute $route
   * @param                 $annotationType
   *
   * @return array|bool
   */
  private function getActionAnnotations(
    Collection $collection,
    CollectionRoute $route,
    $annotationType
  )
  {
    $classReader = $this->getClassAnnotationReader($collection->controller);
    $methodParams = $classReader->getMethodsAnnotations();

    if(!isset($methodParams[$route->controllerAction]))
    {
      return false;
    }

    try
    {
      return $methodParams[$route->controllerAction]
        ->get($annotationType)
        ->getArguments();
    }
    catch(\Exception $e)
    {
      return false;
    }
  }

  /**
   * Get an action annotation
   *
   * @param Collection      $collection
   * @param CollectionRoute $route
   * @param                 $annotationType
   *
   * @return string|bool
   */
  private function getActionAnnotation(
    Collection $collection,
    CollectionRoute $route,
    $annotationType
  )
  {
    $annotations = $this->getActionAnnotations(
      $collection,
      $route,
      $annotationType
    );

    if(!$annotations)
    {
      return false;
    }

    return $annotations[0];
  }

  /**
   * Get Annotation Reader for class
   *
   * @param $class
   *
   * @return \Phalcon\Annotations\Reflection
   */
  private function getClassAnnotationReader($class)
  {
    return (new AnnotationReader())->get($class);
  }

  /**
   * Get the request class name for a controller action
   *
   * @param Collection      $collection
   * @param CollectionRoute $route
   */
  private function getRequestClassName(
    Collection $collection,
    CollectionRoute $route
  )
  {

    // Get the controller class name
    $controller = new $collection->controller;
    $controllerReflection = new \ReflectionClass($controller);
    $controllerClassName = $controllerReflection->getShortName();
    $controllerClassName = str_replace('Controller', '', $controllerClassName);

    return sprintf(
      '%s%sRequest',
      $controllerClassName,
      ucfirst($route->controllerAction)
    );
  }

  /**
   * Get the API Collections
   *
   * @return \PhrestAPI\Collections\Collection[]
   * @throws \Exception
   */
  private function getCollections()
  {
    return $this->sdk->app->getCollections();
  }
}




