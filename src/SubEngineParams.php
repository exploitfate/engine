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

    /**
     * Optional cp request param (CPA)
     *
     * @return string
     */
    public function getRequestCpParam();

    /**
     * Optional cp request param (CPA)
     *
     * @return string
     */
    public function getCookieCpName();
}
