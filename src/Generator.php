<?php


namespace PhrestSDK;

use Phalcon\Annotations\Reader;
use Phalcon\Exception;
use PhrestAPI\Collections\Collection;
use PhrestAPI\Collections\CollectionRoute;
use PhrestAPI\Request\PhrestRequest;
use PhrestAPI\Responses\Response;
use PhrestSDK\Request\RequestOptions;
use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\DocBlock\Tag;
use Zend\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\FileGenerator;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\ParameterGenerator;
use Phalcon\Annotations\Adapter\Memory as AnnotationReader;
use Zend\Code\Generator\DocBlock\Tag\GenericTag as DocBlockTag;
use PhrestSDK\Request\GETRequest;
use PhrestSDK\Request\POSTRequest;
use PhrestSDK\Request\DELETERequest;
use PhrestSDK\Request\PATCHRequest;
use PhrestSDK\Request\Request;
use Zend\Code\Generator\PropertyGenerator;
use Zend\Code\Generator\PropertyValueGenerator;
use Zend\Code\Reflection\DocBlock\Tag\GenericTag;

class Generator
{
  const CLASS_TYPE_REQUEST = 'Request';

  const DOC_ACTION_DESCRIPTION = 'description';
  const DOC_ACTION_METHOD_PARAM = 'methodParam';
  const DOC_ACTION_POST_PARAM = 'postParam';
  const DOC_ACTION_URI = 'uri';
  const DOC_ACTION_RESPONSE = 'response';

  private $sdk;
  private $outputDir;
  private $indentation;

  private $runId;

  /**
   * Base namespace
   *
   * @var string
   */
  private $namespace;

  private $staticMethodRequests = [Request::METHOD_GET, Request::METHOD_DELETE];

  public function __construct(PhrestSDK $sdk, $namespace = 'SDK')
  {
    // Run ID used for string manipulation on output :( hacky but will do
    $this->runId = uniqid() . time();

    $this->sdk = $sdk;
    $this->namespace = $namespace;

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
    $this->printMessage("Creating directories");

    $directories = [
      $this->outputDir,
      sprintf('%s/%s', $this->outputDir, self::CLASS_TYPE_REQUEST),
    ];
    foreach($directories as $directory)
    {
      if(!file_exists($directory))
      {
        mkdir($directory, 0777, true);
      }
    }

    return $this;
  }

  /**
   * Static routes, such as GET or DELETE request
   *
   * @param CollectionRoute $route
   *
   * @return bool
   */
  private function isStaticRoute(CollectionRoute $route)
  {
    return in_array($route->type, $this->staticMethodRequests);
  }

  /**
   * Generate a static method call
   *
   * @param Collection      $collection
   * @param CollectionRoute $route
   *
   * @return MethodGenerator
   */
  private function getStaticMethodCall(
    Collection $collection,
    CollectionRoute $route
  )
  {
    // Create the method
    $method = new MethodGenerator();
    $method->setIndentation($this->indentation);
    $method->setName(strtolower($route->type));
    $method->setStatic(true);

    // Get method params
    $methodParams = $this->getActionMethodParamGenerators($collection, $route);
    if($methodParams)
    {
      $method->setParameters($methodParams);
    }

    // Request options param
    $optionsParam = new ParameterGenerator();
    $optionsParam->setName('options');
    $optionsParam->setDefaultValue(null);
    $optionsParam->setType('RequestOptions');
    $method->setParameter($optionsParam);

    // Docblock
    $docblock = new DocBlockGenerator();
    $tag = new DocBlockTag();
    $tag->setIndentation($this->indentation);
    $tag->setName('return');
    $tag->setContent($this->getActionResponse($collection, $route));
    //$tag = new GenericTag();
    //$tag->setN
    $docblock->setTag($tag);
    $method->setDocBlock($docblock);

    // Generate body
    $body = sprintf(
      'return parent::%s("%s%s", $options);',
      strtolower($route->type),
      $collection->prefix,
      $this->getMethodURI($collection, $route)
    );

    $method->setBody($body);

    return $method;
  }

  /**
   * @param Collection      $collection
   * @param CollectionRoute $route
   *
   * @return array|bool
   */
  public function getActionMethodParams(
    Collection $collection,
    CollectionRoute $route
  )
  {
    return $this->getActionAnnotations(
      $collection,
      $route,
      self::DOC_ACTION_METHOD_PARAM
    );
  }

  /**
   * Get action post parameters
   *
   * @param Collection      $collection
   * @param CollectionRoute $route
   *
   * @return array|bool
   */
  public function getActionPostParams(
    Collection $collection,
    CollectionRoute $route
  )
  {
    return $this->getActionAnnotations(
      $collection,
      $route,
      self::DOC_ACTION_POST_PARAM
    );
  }

  /**
   * Get the action method params
   *
   * @param Collection      $collection
   * @param CollectionRoute $route
   *
   * @return ParameterGenerator[]|bool
   */
  private function getActionMethodParamGenerators(
    Collection $collection,
    CollectionRoute $route
  )
  {
    $methodParamAnnotations = $this->getActionMethodParams($collection, $route);

    if(!$methodParamAnnotations)
    {
      return false;
    }

    // Generate params
    $params = [];
    foreach($methodParamAnnotations as $paramAnnotation)
    {
      // todo handle param types here
      $param = new ParameterGenerator();
      $param->setName($paramAnnotation);
      $param->setType('string');
      $param->setIndentation($this->indentation);

      $params[] = $param;
    }

    return $params;
  }

  /**
   * Get the action post params
   *
   * @param Collection      $collection
   * @param CollectionRoute $route
   *
   * @return array
   */
  private function getActionPostParamProperties(
    Collection $collection,
    CollectionRoute $route
  )
  {
    $methodParamAnnotations = $this->getActionPostParams($collection, $route);

    if(!$methodParamAnnotations)
    {
      return false;
    }

    // Generate properties
    $properties = [];
    foreach($methodParamAnnotations as $paramAnnotation)
    {
      // todo handle param types here
      $property = new PropertyGenerator();
      $property->setName($paramAnnotation);
      $property->setDefaultValue($this->runId);
      $property->setIndentation($this->indentation);

      $properties[] = $property;
    }

    return $properties;
  }

  /**
   * Get the method URI
   *
   * @param Collection      $collection
   * @param CollectionRoute $route
   *
   * @throws \Exception
   * @return string
   */
  private function getMethodURI(Collection $collection, CollectionRoute $route)
  {
    $methodURI = $this->getActionAnnotation(
      $collection,
      $route,
      self::DOC_ACTION_URI
    );

    if(!$methodURI)
    {
      // Default value
      return $route->routePattern;
    }

    return $methodURI;
  }

  /**
   * @param Collection      $collection
   * @param CollectionRoute $route
   *
   * @return bool|string
   */
  private function getActionResponse(Collection $collection, CollectionRoute $route)
  {
    $response = $this->getActionAnnotation(
      $collection,
      $route,
      self::DOC_ACTION_RESPONSE
    );

    if(!$response)
    {
      // Default value
      return '\\' . Response::class;
    }

    return $response;
  }

  /**
   * Get the extended class type for an action
   *
   * @param CollectionRoute $route
   *
   * @throws \Exception
   * @return string
   */
  private function getActionExtendedClassName(CollectionRoute $route)
  {
    switch($route->type)
    {
      case Request::METHOD_GET:
        return '\\' . GETRequest::class;
      case Request::METHOD_POST:
        return '\\' . POSTRequest::class;
      case Request::METHOD_PATCH:
        return '\\' . PATCHRequest::class;
      case Request::METHOD_DELETE:
        return '\\' . DELETERequest::class;
    }

    throw new \Exception(
      sprintf('No HTTP Method found for %s', $route->controllerAction)
    );
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

    return file_put_contents(
      $fileName,
      $this->getClassString($class)
    );
  }

  /**
   * Filter the class body before saving out to file
   *
   * @param \Zend\Code\Generator\ClassGenerator $class
   *
   * @return string
   */
  private function getClassString(ClassGenerator $class)
  {
    // Create PHP class
    $content = sprintf(
      "<?php %s%s",
      PHP_EOL,
      $class->generate()
    );

    // Unset vars that should not have a value, not handled by zend code :(
    $content = str_replace(sprintf(" = '%s'", $this->runId), '', $content);

    return $content;
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
    if(!$this->isStaticRoute($route))
    {
      //$methodParams = $this->getActionMethodParams($collection, $route);
      //if($methodParams)
      {
        //$docBlock->setTags($methodParams);
      }
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
  /*private function getActionMethodParams(
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
  }*/

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

  /**
   * Generate the SDK
   */
  public function generate()
  {
    $this->printMessage("Generating SDK");

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
   * Request Class Namespace
   *
   * @param Collection $collection
   *
   * @return string
   */
  private function getRequestClassNamespace(Collection $collection)
  {
    return sprintf('%s\Request', $this->namespace);
  }

  /**
   * Build the __construct for the request class
   *
   * @param Collection      $collection
   * @param CollectionRoute $route
   *
   * @return MethodGenerator
   * @throws \Exception
   */
  private function getRequestClassConstruct(
    Collection $collection,
    CollectionRoute $route
  )
  {
    if($this->isStaticRoute($route))
    {
      throw new \Exception('Constructor for a static call?');
    }

    // Build method
    $method = new MethodGenerator();
    $method->setIndentation($this->indentation);
    $method->setName('__construct');

    // Add params
    $methodParams = $this->getActionMethodParamGenerators($collection, $route);
    if($methodParams)
    {
      $method->setParameters($methodParams);
    }

    // Set body
    $body = sprintf(
      '$this->path = "%s";',
      $this->getMethodURI($collection, $route)
    );
    $method->setBody($body);

    return $method;
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
      ->setNamespaceName($this->getRequestClassNamespace($collection))
      ->setName($className)
      ->addUse(get_class($this->sdk))
      ->setIndentation($this->indentation)
      ->setExtendedClass($this->getActionExtendedClassName($route))
      ->setDocblock($docBlock);

    // Generate single static method
    if($this->isStaticRoute($route))
    {
      // Add use statement for method
      $class->addUse('\PhrestSDK\Request\RequestOptions');

      // Add static method
      $method = $this->getStaticMethodCall($collection, $route);
      if($method)
      {
        $class->addMethodFromGenerator($method);
      }
    }
    else
    {
      // Add public properties for parameters
      $properties = $this->getActionPostParamProperties($collection, $route);
      if($properties)
      {
        $class->addProperties($properties);
      }

      // Add constructor method
      $class->addMethodFromGenerator(
        $this->getRequestClassConstruct($collection, $route)
      );
    }

    // Save class
    $this->saveClass($class);

    return $this;
  }
}




