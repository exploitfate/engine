<?php

namespace engine;

/**
 * Class AbstractEngine
 * @package engine
 */
abstract class AbstractEngine
{
    const REFERRER_DEFAULT = 'Direct';
    const COUPON_PREFIX = 'REF10';
    const QUEUE_DELIVERY = 'engine';
    const QUEUE_SESSION = 'engine';

    /**
     * @var bool
     */
    protected $isShowReferral = false;

    /**
     * @var string
     */
    protected $refSourceParam = 'source';

    /**
     * @var string
     */
    protected $refSubAffParam = 'aff_sub';

    /**
     * @var string
     */
    protected $refAffKeyParam = 'r';

    /**
     * @var int
     */
    protected $cookieLifetime = 10368000; // 120 days

    /**
     * @var string
     */
    protected $CRCValue;

    /**
     * @var EngineQueue
     */
    protected $queue;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var Cookie
     */
    protected $cookie;

    /**
     * @inheritdoc
     */
    protected function getRequest()
    {
        return $this->request;
    }

    /**
     * @inheritdoc
     */
    protected function setRequest(Request $request)
    {
        $request->setEngine($this);
        $this->request = $request;

        return $this;
    }

    /**
     * @inheritdoc
     */
    protected function getSession()
    {
        return $this->session;
    }

    /**
     * @inheritdoc
     */
    protected function setSession(Session $session)
    {
        $session->setEngine($this);
        $this->session = $session;

        return $this;
    }

    /**
     * @inheritdoc
     */
    protected function getCookie()
    {
        return $this->cookie;
    }

    /**
     * @inheritdoc
     */
    protected function setCookie(Cookie $cookie)
    {
        $cookie->setEngine($this);
        $this->cookie = $cookie;

        return $this;
    }

    /**
     * @param bool $val
     * @return static
     */
    public function setIsShowReferral($val)
    {
        $this->isShowReferral = (bool)$val;

        return $this;
    }

    /**
     * @return int
     */
    protected function getCookieLifetime()
    {
        return $this->cookieLifetime;
    }

    /**
     * Simple md5 hash string validator
     *
     * @param string $string
     * @return bool
     */
    protected function isValidCRC($string)
    {
        return !empty($string) && strlen($string) == 32;
    }

    /**
     * @return string
     */
    protected function generateSalt()
    {
        return md5(uniqid() . microtime(true));
    }

    /**
     * @return string
     */
    protected function getReferrerValue()
    {
        $referrerValue = $this->session->getSessionValue('referrer');
        if (empty($referrerValue)) {
            $requestReferrer = $this->request->getServerValue('HTTP_REFERER');
            if (!empty($requestReferrer) && stripos($requestReferrer, $this->request->getHostname()) === false) {
                $referrerValue = $requestReferrer;
            } else {
                $referrerValue = self::REFERRER_DEFAULT;
            }
        }
        $this->session->removeSessionValue('referrer');

        return (string)$referrerValue;
    }

    /**
     * Get request campaign value from query param than if param empty from alias param
     * @param string $param
     * @param string $aliasParam
     * @return mixed|null
     */
    protected function getRequestCampaignValue($param, $aliasParam)
    {
        $requestCampaignValue = $this->request->getQueryParam($param);
        if (empty($requestCampaignValue)) {
            if (!empty($aliasParam)) {
                $requestCampaignValue = $this->request->getQueryParam($aliasParam);
            }
        }

        return $requestCampaignValue;
    }

    /**
     * @param string $domain
     * @param integer $cookieExpire
     * @param integer $cookieCouponExpire
     * @internal param int $expire
     */
    protected function proceedReferralParams($domain, $cookieExpire, $cookieCouponExpire)
    {
        $referralCoupon = $this->request->getQueryParam($this->refAffKeyParam);
        if ($this->isShowReferral) {
            if (!empty($referralCoupon) && substr($referralCoupon, 0, 5) == self::COUPON_PREFIX) {
                $this->cookie->setCookieValue('referral_coupon', $referralCoupon, $domain, $cookieCouponExpire);
            }
        }

        $referralSource = $this->request->getQueryParam($this->refSourceParam, false);
        $referralSubAff = $this->request->getQueryParam($this->refSubAffParam, false);

        if ($referralSource !== false && $referralSubAff !== false) {
            $this->cookie->setCookieValue($this->refSourceParam, $referralSource, $domain, $cookieExpire);
            $this->cookie->setCookieValue($this->refSubAffParam, $referralSubAff, $domain, $cookieExpire);
        }
    }

    /**
     * Get visitors params
     * @return array
     */
    abstract public function getData();

    /**
     * Add to rabbitmq queue
     * @return void
     */
    public function notify()
    {
        if (!$this->request->isAjax()) {
            $this->queue->publish(self::QUEUE_DELIVERY, $this->getData());
        }
        $this->request->finish();
    }

    public function addToSession()
    {
        if (!$this->request->isAjax()) {
            $data = $this->getSession()->getSessionValue(self::QUEUE_SESSION);

            if (!is_array($data)) {
                $data = [];
            }

            $data[] = $this->getData();

            $this->getSession()->setSessionValue(self::QUEUE_SESSION, $data);
        }
    }
}
