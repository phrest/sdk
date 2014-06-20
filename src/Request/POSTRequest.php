<?php


namespace PhrestSDK\Request;

class POSTRequest extends Request
{
  public function create($path, RequestOptions $options)
  {
    return parent::getResponse(self::METHOD_POST, $path, $options);
  }
}
