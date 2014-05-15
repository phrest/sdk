<?php


namespace PhrestSDK;

use Phalcon\DI;
use Phalcon\Events\Manager;
use Phalcon\Exception;
use Phalcon\Mvc\Dispatcher;
use Phalcon\Registry;
use PhrestAPI\Request\PhrestRequest;
use PhrestAPI\Responses\Response;
use PhrestAPI\PhrestAPI;
use Phalcon\DI as PhalconDI;
use Site\Common\DI\SiteDI;
use Zend\Stdlib\Request;

/**
 * SDK for Phalcon REST API
 * This class can be used as a standalone client for HTTP based requests or
 * you can use it for internal API calls by calling API::setApp()
 */
class PhrestSDK
{
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
    $di = PhalconDI::getDefault();

    if(
    $sdk = $di->get('sdk')
    )
    {
      return $sdk;
    }

    throw new \Exception('No instance of the SDK available');
  }

  /**
   * Set the API instance
   *
   * @param PhrestAPI $app
   * @return $this
   */
  public function setApp(PhrestAPI $app)
  {
    $this->app = $app;
    $this->app->isInternal = true;

    return $this;
  }

  /**
   * Set the URL of the API
   *
   * @param $url
   * @return $this
   */
  public function setURL($url)
  {
    $this->url = $url;

    return $this;
  }

  private function getRawResponse($method, $path, $params = [])
  {
    // Backup super globals
    $request = $_REQUEST;
    $post = $_POST;
    $get = $_GET;

    // Override the request params
    if(isset($params) && count($params) > 0)
    {
      foreach($params as $key => $val)
      {
        $_POST[$key] = $val;
      }
    }

    // Set HTTP method in GET
    $_GET['method'] = $method;

    // Get response from API
    $response = $this->app->handle($path);

    // Restore super globals
    $_REQUEST = $request;
    $_POST = $post;
    $_GET = $get;

    return $response;
  }

  /**
   * Handle getting a response
   *
   * @param $method
   * @param $path
   * @param array $params
   * @return mixed|string
   * @throws \Exception
   * @throws \Phalcon\Exception
   */
  private static function getResponse($method, $path, $params = [])
  {
    $instance = static::getInstance();

    // Get from the internal call if available
    if(isset($instance->app))
    {
      return $instance->getRawResponse($method, $path, $params);
    }

    // Get via HTTP (cURL) if available
    if(isset($instance->url))
    {
      return $instance->getHTTPResponse($method, $path, $params);
    }

    // todo better exception message with link
    throw new Exception(
      'No app configured for internal calls,
          and no URL supplied for HTTP based calls'
    );
  }

  /**
   * Makes a GET call based on path/url
   * @param $path
   * @return Response
   */
  public static function get($path)
  {
    return self::getResponse(PhrestRequest::METHOD_GET, $path);
  }

  /**
   * Makes a POST call based on path/url
   * @param $path
   * @param array $params
   * @return Response
   */
  public static function post($path, $params = [])
  {
    return self::getResponse(PhrestRequest::METHOD_POST, $path, $params);
  }

  /**
   * Makes a PUT call based on path/url
   * @param $path
   * @param array $params
   * @throws \Phalcon\Exception
   * @return Response
   */
  public static function put($path, $params = [])
  {
    return self::getResponse(PhrestRequest::METHOD_PUT, $path);
  }

  /**
   * Makes a PATCH call based on path/url
   * @param $path
   * @param array $params
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
   * @return Response
   */
  public static function delete($path)
  {
    return self::getResponse(PhrestRequest::METHOD_DELETE, $path);
  }

  /**
   * Makes a cURL HTTP request to the API and returns the response
   * todo this needs to also handle PUT, POST, DELETE
   * @param string $method
   * @param $path
   * @param array $params
   * @throws \Exception
   * @return string
   */
  private function getHTTPResponse(
    $method = PhrestRequest::METHOD_GET,
    $path,
    $params = []
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
