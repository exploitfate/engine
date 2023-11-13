<?php

namespace engine;

class SubEngine extends AbstractEngine
{
    /**
     * @var string
     */
    protected $subIdValue;

    /**
     * @var SubEngineParams
     */
    private $engineParams;

    /**
     * Engine constructor.
     * @param EngineQueue $queue
     * @param Request $request
     * @param SubEngineParams $engineParams
     */
    public function __construct(
        EngineQueue $queue,
        Request $request,
        SubEngineParams $engineParams
    ) {
        $this->queue = $queue;
        $this->engineParams = $engineParams;
        $this
            ->setRequest($request)
            ->setCookie(new Cookie())
            ->setSession(new Session());
    }

    /**
     * Get visitor campaign. Look for request first than cookies and default value afterwards
     *
     * @param int|mixed $cookieValue
     * @param int|mixed $requestValue
     * @param int|mixed $defaultValue
     * @return mixed
     */
    private function determinateCampaignValue($cookieValue, $requestValue, $defaultValue)
    {
        if (!empty($requestValue)) {
            $campaignValue = $requestValue;
        } elseif (!empty($cookieValue)) {
            $campaignValue = $cookieValue;
        } else {
            $campaignValue = $defaultValue;
        }

        return $campaignValue;
    }

    /**
     * Check already exists campaign in current visitor
     *
     * @param int|mixed $requestCampaignValue
     * @param int|mixed $cookieCampaignValue
     *
     * @return bool
     */
    private function isCompetitionCampaign($requestCampaignValue, $cookieCampaignValue)
    {
        if (empty($requestCampaignValue) || empty($cookieCampaignValue)) {
            return false;
        }

        return $cookieCampaignValue != $requestCampaignValue;
    }

    /**
     * @inheritdoc
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
        $requestSubIdValue = $this->request->getQueryParam($this->engineParams->getRequestSubIdParam());
        // When visitor with request campaign remove all previous data with new one
        if ($this->isCompetitionCampaign($requestCampaignValue, $cookieCampaignValue)) {
            $cookieSaltValue = $this->generateSalt();
            $this->CRCValue = '';
        } else {
            $cookieSaltValue = $this->cookie->getCookieValue(
                $this->engineParams->getCookieSaltName(),
                $this->generateSalt()
            );
            $this->CRCValue = $this->cookie->getCookieValue($this->engineParams->getCookieCRCName());
        }
        $subIdValue = $this->session->getSessionValue('sub_id');
        // Generate new on not exists
        if (!$this->isValidCRC($this->CRCValue)) {
            if (empty($cookieSaltValue)) {
                $cookieSaltValue = $this->generateSalt();
            }
            $this->CRCValue = md5($cookieSaltValue . $hostname . $campaignValue);
            $uniqueValue = 1;
            $this->session->setSessionValue('unique', $uniqueValue);
            if (!empty($requestSubIdValue)) {
                $subIdValue = $requestSubIdValue;
                $this->session->setSessionValue('sub_id', $requestSubIdValue);
            }
        }
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
                    $this->engineParams->getRequestSubIdParam(),
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

        return [
            'timestamp' => (integer)$this->request->getTimestamp(),
            'user_id' => (integer)$campaignValue,
            'sub_id' => (string)$subIdValue,
            'crc' => (string)$this->CRCValue,
            'referrer' => (string)$this->getReferrerValue(),
            'coupon' => '',
            'hostname' => (string)$this->request->getHostname(),
            'url' => (string)$url,
            'type' => (int)$uniqueValue,
            'ip_address' => (string)$this->request->getClientIp(),
            'user_agent' => (string)$this->request->getServerValue('HTTP_USER_AGENT'),
            'user_host' => (string)$this->request->getServerValue('REMOTE_HOST'),
        ];
    }
}
