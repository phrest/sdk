<?php


namespace PhrestSDK\Request;

class PATCHRequest extends Request
{
  protected $path;
  protected $options = null;

  public function update()
  {
    return parent::getResponse(self::METHOD_PATCH, $this->path, $this->options);
  }
}
