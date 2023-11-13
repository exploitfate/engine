<?php

namespace engine;

/**
 * Interface EngineParams
 * @package engine
 */
interface EngineParams
{
    /**
     * Default campaign value for unique visitor
     *
     * @return integer|string
     */
    public function getDefaultCampaignValue();

    /**
     * Cookies CRC name
     *
     * @return string
     */
    public function getCookieCRCName();

    /**
     * Cookies campaign name
     *
     * @return string
     */
    public function getCookieCampaignName();

    /**
     * Cookies salt name
     *
     * @return string
     */
    public function getCookieSaltName();

    /**
     * @return string
     */
    public function getRequestCampaignParam();

    /**
     * @return string
     */
    public function getRequestCampaignAliasParam();

    /**
     * @return bool
     */
    public function isRedirectAllowed();
}
