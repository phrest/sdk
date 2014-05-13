<?php


namespace PhrestSDK;

use Phalcon\Exception;
use PhrestAPI\Responses\Response;
use PhrestAPI\PhrestAPI;

/**
 * SDK for Phalcon REST API
 * This class can be used as a standalone client for HTTP based requests or
 * you can use it for internal API calls by calling API::setApp()
 */
class PhrestSDK
{
  const METHOD_GET = 'GET';
  const METHOD_POST = 'POST';
  const METHOD_PUT = 'PUT';
  const METHOD_DELETE = 'DELETE';

  /** @var PhrestAPI */
  public $app;

  /** @var string */
  public $url;

  /** @var string */
  public $srcDir;

  private static $instance;

  public function __construct($srcDir)
  {
    $this->srcDir = $srcDir;

    self::$instance = $this;
  }

  /**
   * @return PhrestSDK
   * @throws \Exception
   */
  private static function getInstance()
  {
    if(!isset(self::$instance))
    {
      throw new \Exception('No instance of the SDK available');
    }

    return self::$instance;
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
    // todo see if there is a better way that overriding $_REQUEST
    // Take a backup of the request array
    $request = $_REQUEST;

    // Override the request params
    if(isset($params) && count($params) > 0)
    {
      foreach($params as $key => $val)
      {
        $_REQUEST[$key] = $val;
      }
    }
    $_REQUEST['type'] = 'raw';

    $response = $this->app->handle($path);

    $_REQUEST = $request;
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
    $instance = self::getInstance();

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
    return self::getResponse(self::METHOD_GET, $path);
  }

  /**
   * Makes a POST call based on path/url
   * @param $path
   * @param array $params
   * @return Response
   */
  public static function post($path, $params = [])
  {
    return self::getResponse(self::METHOD_GET, $path, $params);
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
    return self::getResponse(self::METHOD_GET, $path);
  }

  /**
   * Makes a DELETE call based on path/url
   *
   * @param $path
   * @return Response
   */
  public static function delete($path)
  {
    return self::getResponse(self::METHOD_DELETE, $path);
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
  private function getHTTPResponse($method = self::METHOD_GET, $path, $params = [])
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
