<?php

namespace Phrest\SDK\Request;

class DELETERequest extends AbstractRequest
{
  public static function deleteByPath($path, RequestOptions $options = null)
  {
    return parent::getResponse(self::METHOD_DELETE, $path, $options);
  }
}
