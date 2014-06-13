<?php


namespace PhrestSDK\Request;

/**
 * RequestOptions will handle any additional options that are sent through
 * i.e. expand, fields etc.
 *
 * todo this could be automated into the request objects based on annotations
 */
class RequestOptions
{

  private $searchTerm;
  private $parameters;

  /**
   * Add a parameter to the request used for filtering queries etc.
   *
   * @param      $param
   * @param bool $value
   *
   * @return $this
   * @throws \Exception
   */
  public function addParameter($param, $value = true)
  {
    if(!is_scalar($param))
    {
      throw new \Exception("Search filter must be scalar");
    }

    if(!isset($this->parameters))
    {
      $this->parameters = [];
    }

    // Filter strings
    if(is_string($value))
    {
      $value = trim($value);
    }


    $this->parameters[trim($param)] = $value;

    return $this;
  }


  /**
   * Set a search term for the request
   *
   * @param $searchTerm
   *
   * @return $this
   * @throws \Exception
   */
  public function setSearchTerm($searchTerm)
  {
    if(!is_scalar($searchTerm))
    {
      throw new \Exception("Search term must be a string");
    }

    $this->searchTerm = trim($searchTerm);

    return $this;
  }

  /**
   * Unset search term
   *
   * @return $this
   */
  public function unsetSearchTerm()
  {
    unset($this->searchTerm);

    return $this;
  }

  public function toArray()
  {
    $params = [];

    if(isset($this->searchTerm))
    {
      $params['q'] = $this->searchTerm;
    }

    if(isset($this->parameters))
    {
      foreach($this->parameters as $paramKey => $paramVal)
      {
        $params[$paramKey] = $paramVal;
      }
    }

    return $params;
  }
}
