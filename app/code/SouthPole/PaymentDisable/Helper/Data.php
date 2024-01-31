<?php
namespace SouthPole\PaymentDisable\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;

class Data extends AbstractHelper
{

    const ENABLE = 'payment_disable/general/enable';
    const PAYMENT_OPTIONS = 'payment_disable/general/enabled_payment_methods';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param Context $context
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Context $context
    ) {
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context);
    }

    /**
     * @return mixed|string
     */
    public function getPaymentOptions()
    {
        if($this->getEnableDisable()) {
            return $this->scopeConfig->getValue(
                self::PAYMENT_OPTIONS,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
        } else {
            return '';
        }

    }

    /**
     * @return mixed
     */
    public function getEnableDisable() {
        return $this->scopeConfig->getValue(
            self::ENABLE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
}
