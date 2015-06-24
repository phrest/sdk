<?php

namespace Phrest\SDK\Request;

use Phrest\SDK\PhrestSDK;

abstract class AbstractRequest
{
  const METHOD_OPTIONS = 'OPTIONS';
  const METHOD_POST = 'POST';
  const METHOD_PATCH = 'PATCH';
  const METHOD_HEAD = 'HEAD';
  const METHOD_GET = 'GET';
  const METHOD_PUT = 'PUT';
  const METHOD_DELETE = 'DELETE';

  /**
   * @param                $method
   * @param                $path
   * @param RequestOptions $options
   *
   * @return \Phrest\API\Responses\Response|string
   * @throws \Phalcon\Exception
   */
  protected static function getResponse(
    $method,
    $path,
    RequestOptions $options = null
  )
  {
    return PhrestSDK::getResponse($method, $path, $options);
  }
}
