<?php


namespace Phrest\SDK\Request;

use Phrest\API\Enums\AbstractEnum;

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
  private $queryParams = [];

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
    if (!is_scalar($param))
    {
      throw new \Exception("Parameter filter must be scalar");
    }

    if (!isset($this->parameters))
    {
      $this->parameters = [];
    }

    return $this->addGetParam($param, $value);
  }

  /**
   * Set the query type
   *
   * @param AbstractEnum $queryEnum
   *
   * @return $this
   */
  public function setQuery(AbstractEnum $queryEnum)
  {
    $this->addGetParam('get', $queryEnum->getValue());

    return $this;
  }

  /**
   * Add a GET parameter
   *
   * @param       $param
   * @param mixed $value
   *
   * @return $this
   * @throws \Exception
   */
  public function addGetParam($param, $value = true)
  {
    // Validate
    if (!is_scalar($param))
    {
      throw new \Exception("Param name must be scalar");
    }

    // Filter
    if (is_string($value))
    {
      $value = trim($value);
    }

    // Set
    $this->queryParams[trim($param)] = $value;

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
    if (!is_scalar($param))
    {
      throw new \Exception("Param name must be scalar");
    }

    // Filter
    if (is_string($value))
    {
      $value = trim($value);
    }

    // Set
    $this->postParams[trim($param)] = $value;

    return $this;
  }

  public function addPostParams($params = [])
  {
    foreach ($params as $k => $v)
    {
      $this->addPostParam($k, $v);
    }

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
    if (!is_scalar($searchTerm))
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
    if (isset($this->searchTerm))
    {
      $this->queryParams['q'] = $this->searchTerm;
    }

    return $this->queryParams;
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

    if (isset($this->searchTerm))
    {
      $params['q'] = $this->searchTerm;
    }

    if (isset($this->parameters))
    {
      foreach ($this->parameters as $paramKey => $paramVal)
      {
        $params[$paramKey] = $paramVal;
      }
    }

    return $params;
  }

  /**
   * Add a GET param
   *
   * @param $key
   * @param $value
   *
   * @return $this
   */
  public function addQueryParam($key, $value)
  {
    $this->queryParams[$key] = $value;

    return $this;
  }

  /**
   * Set limit
   *
   * @param $limit
   *
   * @return $this
   */
  public function setLimit($limit)
  {
    $this->addQueryParam('limit', (int)$limit);

    return $this;
  }

  /**
   * Get query param by key
   *
   * @param $key
   *
   * @return null|string
   */
  public function getQueryParam($key)
  {
    if (isset($this->queryParams[$key]))
    {
      return $this->queryParams[$key];
    }

    return null;
  }

  /**
   * Get limit option
   *
   * @return null|string
   */
  public function getLimit()
  {
    return $this->getQueryParam('limit');
  }

  /**
   * Set sort order option
   *
   * @param string $order
   *
   * @return $this
   */
  public function setSortOrder($order = 'ASC')
  {
    $this->addQueryParam('sortOrder', $order);

    return $this;
  }

  /**
   * Get sort order option (ASC, DESC, null)
   *
   * @return null|string
   */
  public function getSortOrder()
  {
    return $this->getQueryParam('sortOrder');
  }

  /**
   * Set sort by option
   *
   * @param $sortBy
   *
   * @return $this
   */
  public function setSortBy($sortBy)
  {
    $this->addQueryParam('sortBy', $sortBy);

    return $this;
  }

  /**
   * Get Sort By option
   *
   * @return null|string
   */
  public function getSortBy()
  {
    return $this->getQueryParam('sortBy');
  }

  /**
   * Set IDs for filter
   *
   * @param array $ids
   *
   * @return $this
   */
  public function setIds($ids = [])
  {
    $this->addQueryParam('ids', $ids);

    return $this;
  }

  /**
   * Get ids for filter
   *
   * @return null|string
   */
  public function getIds()
  {
    return $this->getQueryParam('ids');
  }

  /**
   * Set offset option
   *
   * @param $offset
   *
   * @return $this
   */
  public function setOffset($offset)
  {
    $this->addQueryParam('offset', (int)$offset);

    return $this;
  }

  /**
   * Get offset option
   *
   * @return null|string
   */
  public function getOffset()
  {
    return $this->getQueryParam('offset');
  }

  public function isHttp()
  {
    return $this->getQueryParam('http');
  }

  /**
   * Add multiple query params
   *
   * @param array $params
   *
   * @return $this
   */
  public function addQueryParams($params = [])
  {
    if (empty($params))
    {
      return $this;
    }

    foreach ($params as $key => $value)
    {
      $this->addQueryParam($key, $value);
    }

    return $this;
  }
}
