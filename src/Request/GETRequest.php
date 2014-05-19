<?php


namespace PhrestSDK\Request;

/**
 * GETRequest
 */
class GETRequest extends Request
{
  /**
   * Perform a GET request to the API
   *
   * @param                $path
   * @param RequestOptions $options
   *
   * @return string
   */
  public static function get($path, RequestOptions $options = null)
  {
    return parent::getResponse(self::METHOD_GET, $path, $options);
  }
}
