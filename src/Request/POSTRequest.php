<?php


namespace PhrestSDK\Request;

class POSTRequest extends Request
{
  protected $path;
  protected $options = null;

  public function create()
  {
    return parent::getResponse(self::METHOD_POST, $this->path, $this->options);
  }
}
