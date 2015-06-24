<?php

namespace Phrest\SDK\Request;

/**
 * GETRequest
 */
class GETRequest extends AbstractRequest
{
  /**
   * Perform a GET request to the API
   *
   * @param                $path
   * @param RequestOptions $options
   *
   * @return string
   */
  public static function getByPath($path, RequestOptions $options = null)
  {
    return parent::getResponse(self::METHOD_GET, $path, $options);
  }
}
