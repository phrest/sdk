<?php

namespace Phrest\SDK\Request;

class POSTRequest extends AbstractRequest
{
  protected $path;

  public function createByPath($path, RequestOptions $options)
  {
    return parent::getResponse(self::METHOD_POST, $path, $options);
  }
}
