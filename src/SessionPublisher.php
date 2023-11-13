<?php

namespace engine;

class SessionPublisher
{
    /**
     * @var Session
     */
    protected $session;

    /**
     * @var EngineQueue
     */
    protected $queue;

    public function __construct(EngineQueue $queue)
    {
        $this->session = new Session();
        $this->queue = $queue;
    }

    public function publish()
    {
        $data = $this->session->getSessionValue(AbstractEngine::QUEUE_SESSION);

        if (!empty($data) && is_array($data)) {
            foreach ($data as $key => $item) {
                $this->queue->publish(AbstractEngine::QUEUE_DELIVERY, $item);
                unset($data[$key]);
            }

            $this->session->setSessionValue(AbstractEngine::QUEUE_SESSION, $data);
        }
    }
}