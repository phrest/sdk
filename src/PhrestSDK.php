<?php

namespace Phrest\SDK;

use GuzzleHttp\Client;
use GuzzleHttp\Post\PostBody;
use Phalcon\DI;
use Phalcon\Events\Manager;
use Phalcon\Exception;
use Phalcon\Mvc\Dispatcher;
use Phalcon\Registry;
use Phrest\API\DI\PhrestDI;
use Phrest\API\Enums\RequestMethodEnum;
use Phrest\API\Request\PhrestRequest;
use Phrest\API\Response\Response;
use Phrest\API\PhrestAPI;
use Phalcon\DI as PhalconDI;
use Phrest\SDK\Request\RequestOptions;
use GuzzleHttp\Message\Request;

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

    if (isset($instance))
    {
      return $instance;
    }

    $di = PhalconDI::getDefault();

    if (!$di)
    {
      throw new \Exception('No DI found');
    }

    // Get already created instance
    try
    {
      $instance = $di->get('sdk');

      return $instance;
    }
    catch (\Exception $e)
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
   * It seems hacky, but I am not sure if there is any better way, please
   * submit a pull request if you can improve! :)
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
    $server = $_SERVER;

    // Override the request params
    $_GET = $options ? $options->getGetParams() : [];
    $_POST = $options ? $options->getPostParams() : [];

    // Set HTTP method in GET
    $_GET['method'] = $method;
    $_GET['_url'] = $path;
    $_REQUEST = ['type' => 'raw']; // todo is this requred?

    // This is required for phalcon 3
    $_SERVER['REQUEST_METHOD'] = $method;

    // Get current DI
    $defaultDI = DI::getDefault();

    if ($defaultDI instanceof PhrestDI)
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
      $isInternal = $this->app->isInternalRequest;

      $this->app->isInternalRequest = true;

      $response = $this->app->handle($path);

      $this->app->isInternalRequest = $isInternal;
    }
    catch (\Exception $e)
    {
      DI::setDefault($defaultDI);
      throw $e;
    }

    // Remove cached uri & method
    self::$uri = null;
    self::$method = null;

    // Restore default DI
    if (!$defaultDI instanceof PhrestDI)
    {
      DI::setDefault($defaultDI);
    }

    // Restore super globals
    $_REQUEST = $request;
    $_POST = $post;
    $_GET = $get;
    $_SERVER = $server;

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

    if ($options)
    {
      // Get via HTTP
      if (isset($instance->url) && $options->isHttp())
      {
        return $instance->getHTTPResponse($method, $path, $options);
      }
    }

    // Get from the internal call if available
    if (isset($instance->app))
    {
      return $instance->getRawResponse($method, $path, $options);
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
    return self::getResponse(RequestMethodEnum::GET, $path, $options);
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
    return self::getResponse(RequestMethodEnum::POST, $path, $params);
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
    return self::getResponse(RequestMethodEnum::PUT, $path);
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
    return self::getResponse(RequestMethodEnum::PATCH, $path, $params);
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
    return self::getResponse(RequestMethodEnum::DELETE, $path);
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
   * @return string
   */
  private function getHTTPResponse(
    $method,
    $path,
    RequestOptions $options = null
  )
  {
    $client = new Client();

    // Build body
    $body = new PostBody();

    if ($options)
    {
      foreach ($options->getPostParams() as $name => $value)
      {
        $body->setField($name, $value);
      }
    }

    // Prepare the request
    $request = new Request(
      $method,
      $this->url . $path,
      [],
      $body,
      []
    );

    // Get response
    $response = $client->send($request);
    $body = json_decode($response->getBody());

    if (isset($body->data))
    {
      return $body->data;
    }
    else
    {
      throw new \Exception('Error calling ' . $method . ' to: ' . $path);
    }
  }
}
