<?php

namespace engine;

/**
 * Class Request
 * @package engine
 */
abstract class Request
{
    /**
     * @var integer
     */
    private $timestamp;

    /**
     * @var AbstractEngine
     */
    private $engine;

    /**
     * @var string
     */
    private $hostname;

    /**
     * @var string
     */
    private $url;

    /**
     * @var integer
     */
    private $errorReporting;

    /**
     * @var integer
     */
    private $displayError;

    /**
     * @var integer
     */
    private $ignoreUserAbort;

    /**
     * @var array|null
     */
    private $queryParam;

    /**
     * @var array|null
     */
    private $serverParam;

    /**
     * @return string
     */
    public abstract function getClientIp();

    /**
     * @void
     */
    private function init()
    {
        $this->timestamp = time();
        $this->errorReporting = error_reporting(0);
        $this->displayError = ini_set('display_errors', 0);
        $this->ignoreUserAbort = ignore_user_abort(1);
        $this->addP3PHeaders();
        $this->queryParam = $_GET;
        $this->serverParam = $_SERVER;
    }

    /**
     * Convert string  to lowercase
     *
     * @param string $value
     * @return string mixed
     */
    private function lower($value)
    {
        return strtolower($value);
    }

    /**
     * Remove item from array
     *
     * @param array $array
     * @param string|array $key
     * @return array
     */
    private function arrayRemove($array, $key)
    {
        if (!is_array($array)) {
            return [];
        }
        if (is_array($key)) {
            foreach ($key as $item) {
                $array = $this->arrayRemove($array, $item);
            }
        } else {
            foreach ($array as $param => $value) {
                if ($this->lower($param) == $this->lower($key))
                    unset($array[$param]);
            }
        }

        return $array;
    }


    private function addP3PHeaders()
    {
        header('P3P: CP="NOI DSP COR NID CURa ADMa DEVa PSAa PSDa OUR BUS COM INT OTC PUR STA"');
    }

    /**
     * @return bool
     */
    private function isPost()
    {
        $method = 'GET';
        $httpMethodOverride = $this->getServerValue('HTTP_X_HTTP_METHOD_OVERRIDE');
        if (!empty($httpMethodOverride)) {
            $method = $httpMethodOverride;
        } else {
            $httpRequestMethod = $this->getServerValue('REQUEST_METHOD');
            if (!empty($httpRequestMethod)) {
                $method = $httpRequestMethod;
            }
        }

        return strtoupper($method) === 'POST';
    }

    /**
     * @return bool
     */
    public function isAjax()
    {
        $httpRequestWith = $this->getServerValue('HTTP_X_REQUESTED_WITH');

        return !empty($httpRequestWith) && $httpRequestWith === 'XMLHttpRequest';
    }

    /**
     * Request constructor.
     */
    public function __construct()
    {
        $this->init();
    }

    /**
     * @return AbstractEngine
     */
    public function getEngine()
    {
        return $this->engine;
    }

    /**
     * @param AbstractEngine $engine
     * @return Request
     */
    public function setEngine(AbstractEngine $engine)
    {
        $this->engine = $engine;

        return $this;
    }

    /**
     * @void
     */
    public function finish()
    {
        error_reporting($this->errorReporting);
        ini_set('display_errors', $this->displayError);
        ignore_user_abort($this->ignoreUserAbort);
    }

    /**
     * @return integer
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * @param string $name
     * @param null|mixed $default
     * @return mixed|null
     */
    public function getQueryParam($name, $default = null)
    {
        if (!empty($this->queryParam)) {
            $param = $this->lower($name);
            foreach ($this->queryParam as $key => $value) {
                if ($this->lower($key) == $param) {
                    return $value;
                }
            }
        }

        return $default;
    }

    /**
     * @param string $name
     * @param null|mixed $default
     * @return mixed|null
     */
    public function getServerValue($name, $default = null)
    {
        return array_key_exists($name, $this->serverParam) ? $this->serverParam[$name] : $default;
    }

    /**
     * @return string
     */
    public function getHostname()
    {
        if (empty($this->hostname)) {
            $hostname = $this->getServerValue('HTTP_HOST');

            if (!empty($hostname)) {
                if (($pos = strpos($hostname, ':')) !== false) {
                    $hostname = substr($hostname, 0, $pos);
                }
            } else {
                $hostname = $this->getServerValue('SERVER_NAME');
            }
            if (stripos($hostname, 'www.') === 0) {
                $hostname = substr($hostname, 4);
            }
            $this->hostname = $this->lower($hostname);
        }

        return (string)$this->hostname;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        if ($this->url === null) {
            $this->url = $this->resolveRequestUri();
        }

        return $this->url;
    }

    /**
     * @return string
     */
    protected function resolveRequestUri()
    {
        $httpRewriteUrl = $this->getServerValue('HTTP_X_REWRITE_URL');
        $httpRequestUri = $this->getServerValue('REQUEST_URI');
        $httpOrigPathInfo = $this->getServerValue('ORIG_PATH_INFO');
        if (!empty($httpRewriteUrl)) { // IIS
            $requestUri = $httpRewriteUrl;
        } elseif (!empty($httpRequestUri)) {
            $requestUri = $httpRequestUri;
            if ($requestUri !== '' && $requestUri[0] !== '/') {
                $requestUri = preg_replace('/^(http|https):\/\/[^\/]+/i', '', $requestUri);
            }
        } elseif (!empty($httpOrigPathInfo)) { // IIS 5.0 CGI
            $requestUri = $httpOrigPathInfo;
            $httpQueryString = $this->getServerValue('QUERY_STRING');
            if (!empty($httpQueryString)) {
                $requestUri .= '?' . $httpQueryString;
            }
        } else {
            $requestUri = '/';
        }

        return (string)$requestUri;
    }

    /**
     * @param string|array $name
     * @return array
     */
    public function getRedirectQueryParams($name)
    {
        if (!empty($this->queryParam)) {
            $params = $this->arrayRemove($this->queryParam, $name);
        } else {
            $params = $this->queryParam;
        }

        return $params;
    }

    /**
     * @param string $url
     * @param int $httpResponseCode
     * @return bool
     */
    public function redirect($url, $httpResponseCode)
    {
        if ($this->canRedirect()) {
            header('Location: ' . $url, true, $httpResponseCode);

            /* Make sure that code below does not get executed when we redirect. */

            return true;
        }

        return false;
    }

    /**
     * @param string|array $key
     * @param int $httpResponseCode
     * @return bool
     */
    public function reload($key, $httpResponseCode = 302)
    {
        $params = $this->getRedirectQueryParams($key);
        $redirectQuery = http_build_query($params);
        $redirectPath = parse_url($this->getUrl(), PHP_URL_PATH);
        if (!empty($redirectQuery)) {
            $redirectQuery = '?'.$redirectQuery;
        }

        return $this->redirect($redirectPath . $redirectQuery, $httpResponseCode);
    }

    /**
     * @return bool
     */
    public function canRedirect()
    {
        return !$this->isAjax() && !$this->isPost();
    }
}
