<?php

namespace engine;

/**
 * Class Cookie
 * @package engine
 */
class Cookie
{
    /**
     * @var AbstractEngine
     */
    private $engine;

    /**
     * @var array
     */
    private $cookieParam;

    /**
     * @void
     */
    private function init()
    {
        $this->cookieParam = $_COOKIE;
    }

    /**
     * Cookie constructor.
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
     * @return Cookie
     */
    public function setEngine(AbstractEngine $engine)
    {
        $this->engine = $engine;

        return $this;
    }

    /**
     * @param string $name Cookie name
     * @param null $default
     * @return null|string
     */
    public function getCookieValue($name, $default = null)
    {
        return array_key_exists($name, $this->cookieParam) ? $this->cookieParam[$name] : $default;
    }

    /**
     * @param string $name
     * @param string $value
     * @param string $domain
     * @param integer $expire
     * @param string $path
     * @param bool $secure
     * @param bool $httpOnly
     * @return bool
     */
    public function setCookieValue(
        $name,
        $value,
        $domain = '',
        $expire = 0,
        $path = '/',
        $secure = false,
        $httpOnly = false
    ) {
        if ($expire === null) {
            $expire = $this->engine->getRequest()->getTimestamp() + $this->engine->getCookieLifetime();
        }
        if ($domain === null) {
            $domain = '.'.$this->engine->getRequest()->getHostname();
        }

        return setcookie((string)$name, $value, (integer)$expire, (string)$path, (string)$domain, $secure, $httpOnly);
    }
}
