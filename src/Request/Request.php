<?php


namespace PhrestSDK\Request;

use PhrestSDK\PhrestSDK;

abstract class Request
{
  const METHOD_OPTIONS = 'OPTIONS';
  const METHOD_POST = 'POST';
  const METHOD_PATCH = 'PATCH';
  const METHOD_HEAD = 'HEAD';
  const METHOD_GET = 'GET';
  const METHOD_PUT = 'PUT';
  const METHOD_DELETE = 'DELETE';

  protected static function getResponse(
    $method,
    $path,
    RequestOptions $options = null
  )
  {
    $params = $options ? $options->toArray() : [];
    return PhrestSDK::getResponse($method, $path, $params);
  }
}
