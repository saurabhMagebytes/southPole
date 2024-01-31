<?php
namespace SouthPole\PaymentDisable\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Payment\Model\Config as PaymentConfig;

class PaymentMethods implements OptionSourceInterface
{
    /**
     * @var PaymentConfig
     */
    protected $paymentConfig;

    /**
     * @param PaymentConfig $paymentConfig
     */
    public function __construct(
        PaymentConfig $paymentConfig
    ) {
        $this->paymentConfig = $paymentConfig;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];

        $methods = $this->paymentConfig->getActiveMethods();
        foreach ($methods as $code => $method) {
            $options[] = [
                'value' => $code,
                'label' => $method->getTitle(),
            ];
        }

        return $options;
    }
}
