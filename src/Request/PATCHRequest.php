<?php


namespace PhrestSDK\Request;

class PATCHRequest extends Request
{
 // protected $path;
 // protected $options = null;

  public function update($path, $options) //dirty hack to make patch work //todo fix this
  {
    return parent::getResponse(self::METHOD_PATCH, $path, $options);
  }
}
