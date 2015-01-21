<?php


namespace PhrestSDK;

use Phalcon\DI;
use Phalcon\Events\Manager;
use Phalcon\Exception;
use Phalcon\Mvc\Dispatcher;
use Phalcon\Registry;
use PhrestAPI\DI\PhrestDI;
use PhrestAPI\Request\PhrestRequest;
use PhrestAPI\Responses\Response;
use PhrestAPI\PhrestAPI;
use Phalcon\DI as PhalconDI;
use PhrestSDK\Request\RequestOptions;
use My\Common\DI\SiteDI;
use Zend\Stdlib\Request;

/**
 * SDK for Phalcon REST API
 * This class can be used as a standalone client for HTTP based requests or
 * you can use it for internal API calls by calling API::setApp()
 */
class PhrestSDK
{
  // todo this is a temporary solution to storing the URI while processing
  // an internal request, if the request 404s we need a route
  public static $uri;
  public static $method;

  /** @var PhrestAPI */
  public $app;

  /** @var string */
  public $url;

  /** @var string */
  public $srcDir;

  public function __construct($srcDir)
  {
    $this->srcDir = $srcDir;
  }

  /**
   * @return PhrestSDK
   * @throws \Exception
   */
  public static function getInstance()
  {
    static $instance;

    if(isset($instance))
    {
      return $instance;
    }

    $di = PhalconDI::getDefault();

    if(!$di)
    {
      throw new \Exception('No DI found');
    }

    // Get already created instance
    try
    {
      $instance = $di->get('sdk');

      return $instance;
    }
    catch(\Exception $e)
    {
      throw new \Exception("No instance of 'sdk' found in DI");
    }
  }

  /**
   * Set the API instance
   *
   * @param PhrestAPI $app
   *
   * @return $this
   */
  public function setApp(PhrestAPI $app)
  {
    $this->app = $app;
    $this->app->isInternalRequest = true;

    return $this;
  }

  /**
   * Set the URL of the API
   *
   * @param $url
   *
   * @return $this
   */
  public function setURL($url)
  {
    $this->url = $url;

    return $this;
  }

  /**
   * Gets a raw response from the internal API
   * This will trick Phalcon into thinking its a
   * real PUT, POST, PATCH, GET or DELETE request
   * It will override the Default DI (Which will be the current site)
   * and will restore everything after the request
   *
   * It seems hacky, but I am not sure if there is any better way, please
   * submit a pull request if you can improve! :)
   *
   * todo test if the below does in fact require a new instance of DI
   * if it is being called from within the API
   *
   * @param                        $method
   * @param                        $path
   * @param RequestOptions         $options
   *
   * @return Response
   */
  private function getRawResponse(
    $method,
    $path,
    RequestOptions $options = null
  )
  {
    // Backup super globals
    $request = $_REQUEST;
    $post = $_POST;
    $get = $_GET;

    // Override the request params
    $_GET = $options ? $options->getGetParams() : [];
    $_POST = $options ? $options->getPostParams() : [];

    // Set HTTP method in GET
    $_GET['method'] = $method;
    $_GET['_url'] = $path;
    $_REQUEST = ['type' => 'raw']; // todo is this requred?

    // Get current DI
    $defaultDI = DI::getDefault();

    if($defaultDI instanceof PhrestDI)
    {
      $apiDI = $defaultDI;
    }
    else
    {
      // Set API DI to the default, this is required for models etc.
      // As Phalcon will get the default DI to perform actions
      $apiDI = self::getInstance()->app->getDI();
      DI::setDefault($apiDI);
    }

    // Cache the URI & method
    self::$uri = $path;
    self::$method = $method;

    // Get response from API
    // todo post not picked up
    try
    {
      $response = $this->app->handle($path);
    }
    catch(\Exception $e)
    {
      DI::setDefault($defaultDI);
      throw $e;
    }

    // Remove cached uri & method
    self::$uri = null;
    self::$method = null;

    // Restore default DI
    if(!$defaultDI instanceof PhrestDI)
    {
      DI::setDefault($defaultDI);
    }

    // Restore super globals
    $_REQUEST = $request;
    $_POST = $post;
    $_GET = $get;

    return $response;
  }

  /**
   * @param                $method
   * @param                $path
   * @param RequestOptions $options
   *
   * @return Response|string
   * @throws \Phalcon\Exception
   */
  public static function getResponse(
    $method,
    $path,
    RequestOptions $options = null
  )
  {
    $instance = static::getInstance();

    // Get from the internal call if available
    if(isset($instance->app))
    {
      return $instance->getRawResponse($method, $path, $options);
    }

    // Get via HTTP (cURL) if available
    if(isset($instance->url))
    {
      return $instance->getHTTPResponse($method, $path, $options);
    }

    // todo better exception message with link
    throw new Exception(
      'No app configured for internal calls,
          and no URL supplied for HTTP based calls'
    );
  }

  /**
   * Makes a GET call based on path/url
   *
   * @param $path
   *
   * @return Response
   */
  public static function get($path, RequestOptions $options = null)
  {
    return self::getResponse(PhrestRequest::METHOD_GET, $path, $options);
  }

  /**
   * Makes a POST call based on path/url
   *
   * @param       $path
   * @param array $params
   *
   * @return Response
   */
  public static function post($path, $params = [])
  {
    return self::getResponse(PhrestRequest::METHOD_POST, $path, $params);
  }

  /**
   * Makes a PUT call based on path/url
   *
   * @param       $path
   * @param array $params
   *
   * @throws \Phalcon\Exception
   * @return Response
   */
  public static function put($path, $params = [])
  {
    return self::getResponse(PhrestRequest::METHOD_PUT, $path);
  }

  /**
   * Makes a PATCH call based on path/url
   *
   * @param       $path
   * @param array $params
   *
   * @throws \Phalcon\Exception
   * @return Response
   */
  public static function patch($path, $params = [])
  {
    return self::getResponse(PhrestRequest::METHOD_PATCH, $path, $params);
  }

  /**
   * Makes a DELETE call based on path/url
   *
   * @param $path
   *
   * @return Response
   */
  public static function delete($path)
  {
    return self::getResponse(PhrestRequest::METHOD_DELETE, $path);
  }

  /**
   * Makes a cURL HTTP request to the API and returns the response
   * todo this needs to also handle PUT, POST, DELETE
   *
   * @param string                 $method
   * @param                        $path
   * @param RequestOptions         $options
   *
   * @throws \Exception
   *
   * @return string
   */
  private function getHTTPResponse(
    $method = PhrestRequest::METHOD_GET,
    $path,
    RequestOptions $options = null
  )
  {
    // Prepare curl
    $curl = curl_init($this->url . $path);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $curlResponse = curl_exec($curl);

    // Handle failed request
    if($curlResponse === false)
    {
      $info = curl_getinfo($curl);
      curl_close($curl);

      throw new \Exception('Transmission Error: ' . print_r($info, true));
    }

    // Return response
    curl_close($curl);
    return json_decode($curlResponse);
  }
}
