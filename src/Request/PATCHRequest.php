<?php

namespace Phrest\SDK\Request;

class PATCHRequest extends Request
{
  protected $path;

  public function updateByPath($path, RequestOptions $options)
  {
    return parent::getResponse(self::METHOD_PATCH, $path, $options);
  }
}
