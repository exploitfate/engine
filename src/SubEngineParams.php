<?php

namespace engine;

/**
 * Interface EngineParams
 * @package engine
 */
interface SubEngineParams extends EngineParams
{
    /**
     * Optional sub id request param
     *
     * @return string
     */
    public function getRequestSubIdParam();
}
