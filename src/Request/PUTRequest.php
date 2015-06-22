<?php

namespace Phrest\SDK\Request;

class PUTRequest extends Request
{
  protected $path;

  public function setByPath($path, RequestOptions $options)
  {
    return parent::getResponse(self::METHOD_PUT, $path, $options);
  }
}
