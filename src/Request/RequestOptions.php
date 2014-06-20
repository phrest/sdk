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
  // todo phase out
  private $parameters;

  private $postParams = [];
  private $getParams = [];

  /**
   * Use addGetParam/addPostParam accordingly
   *
   * @param      $param
   * @param bool $value
   *
   * @return $this
   * @throws \Exception
   * @deprecated
   */
  public function addParameter($param, $value = true)
  {
    if(!is_scalar($param))
    {
      throw new \Exception("Parameter filter must be scalar");
    }

    if(!isset($this->parameters))
    {
      $this->parameters = [];
    }

    return $this->addGetParam($param, $value);
  }

  /**
   * Add a GET parameter
   *
   * @param      $param
   * @param bool $value
   *
   * @return $this
   * @throws \Exception
   */
  public function addGetParam($param, $value = true)
  {
    // Validate
    if(!is_scalar($param))
    {
      throw new \Exception("Param name must be scalar");
    }

    // Filter
    if(is_string($value))
    {
      $value = trim($value);
    }

    // Set
    $this->getParams[trim($param)] = $value;

    return $this;
  }

  /**
   * Add a POST parameter
   *
   * @param      $param
   * @param bool $value
   *
   * @return $this
   * @throws \Exception
   */
  public function addPostParam($param, $value = true)
  {
    // Validate
    if(!is_scalar($param))
    {
      throw new \Exception("Param name must be scalar");
    }

    // Filter
    if(is_string($value))
    {
      $value = trim($value);
    }

    // Set
    $this->postParams[trim($param)] = $value;

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

  /**
   * Get the request GET params
   *
   * @return array
   */
  public function getGetParams()
  {
    if(isset($this->searchTerm))
    {
      $this->getParams['q'] = $this->searchTerm;
    }

    return $this->getParams;
  }

  /**
   * Get the request POST params
   *
   * @return array
   */
  public function getPostParams()
  {
    return $this->postParams;
  }

  /**
   * This should no longer be needed
   * todo remove
   *
   * @return array
   * @deprecated
   */
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
