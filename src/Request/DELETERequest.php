<?php


namespace PhrestSDK\Request;

class DELETERequest extends Request
{
  public static function deleteByPath($path, RequestOptions $options = null)
  {
    return parent::getResponse(self::METHOD_DELETE, $path, $options);
  }
}
