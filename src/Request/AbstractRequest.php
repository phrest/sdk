<?php

namespace Phrest\SDK\Request;

abstract class AbstractRequest implements RequestInterface
{
  const METHOD_OPTIONS = 'OPTIONS';
  const METHOD_POST = 'POST';
  const METHOD_PATCH = 'PATCH';
  const METHOD_HEAD = 'HEAD';
  const METHOD_GET = 'GET';
  const METHOD_PUT = 'PUT';
  const METHOD_DELETE = 'DELETE';
}
