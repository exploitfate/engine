<?php

namespace engine;

/**
 * Interface EngineQueueConnectionConfig
 * @package engine
 */
interface EngineQueueConnectionConfig
{
    /**
     * The host that the RabbitMQ server is running on.
     *
     * @return string
     */
    public function getHost();

    /**
     * The RabbitMQ vhost.
     *
     * @return string
     */
    public function getVirtualHost();

    /**
     * The port that the RabbitMQ server is running on.
     *
     * @return string
     */
    public function getPort();

    /**
     * The username for logging into the server.
     *
     * @return string
     */
    public function getUser();

    /**
     * The password for logging into the server.
     *
     * @return string
     */
    public function getPassword();
}
