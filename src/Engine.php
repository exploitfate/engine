<?php

namespace engine;

/**
 * Class Engine
 * @package engine
 */
class Engine extends AbstractEngine
{
    /**
     * @var EngineParams
     */
    private $engineParams;

    /**
     * Engine constructor.
     * @param EngineQueue $queue
     * @param Request $request
     * @param EngineParams $engineParams
     */
    public function __construct(
        EngineQueue $queue,
        Request $request,
        EngineParams $engineParams
    ) {
        $this->queue = $queue;
        $this->engineParams = $engineParams;
        $this
            ->setRequest($request)
            ->setCookie(new Cookie())
            ->setSession(new Session());
    }

    /**
     * Get visitor campaign. Look for cookies first than request and default value afterwards
     *
     * @param int|mixed $cookieValue
     * @param int|mixed $requestValue
     * @param int|mixed $defaultValue
     * @return mixed
     */
    private function determinateCampaignValue($cookieValue, $requestValue, $defaultValue)
    {
        if (!empty($cookieValue)) {
            $campaignValue = $cookieValue;
        } elseif (!empty($requestValue)) {
            $campaignValue = $requestValue;
        } else {
            $campaignValue = $defaultValue;
        }

        return $campaignValue;
    }

    /**
     * Store in session campaign value for competition detection
     *
     * @param int|mixed|null $sessionCampaignValue
     * @param int|mixed|null $requestCampaignValue
     */
    private function setSessionRequestCampaignValue($sessionCampaignValue, $requestCampaignValue)
    {
        if (empty($sessionCampaignValue) && !empty($requestCampaignValue)) {
            $this->session->setSessionValue('request_campaign', $requestCampaignValue);
        }
    }

    /**
     * Add visitor data to RabbitMQ queue.
     * Note: This method has redirect with exit;
     */
    public function getData()
    {
        $hostname = $this->request->getHostname();
        $timestamp = $this->request->getTimestamp();
        $cookieExpire = $timestamp + $this->cookieLifetime;
        $cookieCampaignValue = $this->cookie->getCookieValue($this->engineParams->getCookieCampaignName());
        $requestCampaignValue = $this->getRequestCampaignValue(
            $this->engineParams->getRequestCampaignParam(),
            $this->engineParams->getRequestCampaignAliasParam()
        );
        $campaignValue = $this->determinateCampaignValue(
            $cookieCampaignValue,
            $requestCampaignValue,
            $this->engineParams->getDefaultCampaignValue()
        );
        $sessionCampaignValue = $this->session->getSessionValue('request_campaign');
        // Also save request campaign (Only when request campaign exists)
        $this->setSessionRequestCampaignValue($sessionCampaignValue, $requestCampaignValue);
        // Get cookie data
        $cookieSaltValue = $this->cookie->getCookieValue(
            $this->engineParams->getCookieSaltName(),
            $this->generateSalt()
        );
        $this->CRCValue = $this->cookie->getCookieValue($this->engineParams->getCookieCRCName());
        // Generate new on not exists
        if (!$this->isValidCRC($this->CRCValue)) {
            $this->CRCValue = md5($cookieSaltValue . $hostname . $campaignValue);
            $uniqueValue = 1;
            $this->session->setSessionValue('unique', $uniqueValue);
        }
        // Save data to cookie
        $this->cookie->setCookieValue(
            $this->engineParams->getCookieCampaignName(),
            $campaignValue,
            '.' . $hostname,
            $cookieExpire
        );
        $this->cookie->setCookieValue(
            $this->engineParams->getCookieCRCName(),
            $this->CRCValue,
            '.' . $hostname,
            $cookieExpire
        );
        $this->cookie->setCookieValue(
            $this->engineParams->getCookieSaltName(),
            $cookieSaltValue,
            '.' . $hostname,
            $cookieExpire
        );
        $this->proceedReferralParams('.' . $hostname, $cookieExpire, $timestamp + 3600);
        $requestUrl = $this->request->getUrl();
        if ($this->request->canRedirect() && $this->engineParams->isRedirectAllowed() && !empty($requestCampaignValue)) {
            $this->session->setSessionValue('come_url', $requestUrl);
            $this->session->setSessionValue('referrer', $this->request->getServerValue('HTTP_REFERER'));
            $this->request->reload(
                [
                    $this->engineParams->getRequestCampaignParam(),
                    $this->engineParams->getRequestCampaignAliasParam(),
                    $this->refAffKeyParam,
                    $this->refSourceParam,
                    $this->refSubAffParam,
                ]
            );
            exit(0);
        }
        $url = $this->session->getSessionValue('come_url');
        if (empty($url)) {
            $url = $requestUrl;
            $this->session->setSessionValue('come_url', $requestUrl);
        }
        $this->session->removeSessionValue('come_url');
        if (!isset($uniqueValue)) {
            $uniqueValue = (int)$this->session->getSessionValue('unique');
            $this->session->removeSessionValue('unique');
        }
        $data = [
            'timestamp' => (integer)$this->request->getTimestamp(),
            'compain_id' => (integer)$campaignValue,
            'hash' => (string)$cookieSaltValue,
            'crc' => (string)$this->CRCValue,
            'referrer' => (string)$this->getReferrerValue(),
            'coupon' => '',
            'host' => (string)$this->request->getHostname(),
            'url' => (string)$url,
            'type' => (int)$uniqueValue,
            'ipv4' => (string)$this->request->getClientIp(),
            'user_agent' => (string)$this->request->getServerValue('HTTP_USER_AGENT'),
            'user_host' => (string)$this->request->getServerValue('REMOTE_HOST'),
        ];
        // Add Competition campaign value if exists
        if ($sessionCampaignValue != $campaignValue) {
            $data['request_campaign'] = $sessionCampaignValue;
        }

        return $data;
    }
}
