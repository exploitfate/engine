<?php

namespace engine;

/**
 * Class Session
 * @package engine
 */
class Session
{
    /**
     * @var AbstractEngine
     */
    private $engine;

    /**
     * @void
     */
    private function init()
    {
        $this->sessionOpen();
    }

    /**
     * @return bool
     */
    private function sessionOpen()
    {
        $sessionId = session_id();
        if (empty($sessionId)) {
            return session_start();
        }

        return true;
    }

    /**
     * Session constructor.
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
     * @return Session
     */
    public function setEngine(AbstractEngine $engine)
    {
        $this->engine = $engine;

        return $this;
    }

    /**
     * @param string $name
     * @param null|mixed $default
     * @return mixed|null
     */
    public function getSessionValue($name, $default = null)
    {
        return array_key_exists($name, $_SESSION) ? $_SESSION[$name] : $default;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return bool
     */
    public function setSessionValue($name, $value)
    {
        $_SESSION[$name] = $value;

        return true;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function removeSessionValue($name)
    {
        if (isset($_SESSION[$name])) {
            unset($_SESSION[$name]);
        }

        return true;
    }
}
