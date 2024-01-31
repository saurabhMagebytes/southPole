<?php
namespace SouthPole\PaymentDisable\Plugin\Payment\Model;

use SouthPole\PaymentDisable\Helper\Data;
use Magento\Customer\Api\CustomerRepositoryInterface;

class MethodList
{

    protected $customerRepository;

    /**
     * @param Data $helperData
     */
    public function __construct(
        Data $helperData,
        CustomerRepositoryInterface $customerRepository
    ) {
        $this->helperData = $helperData;
        $this->customerRepository = $customerRepository;
    }

    /**
     * @param \Magento\Payment\Model\MethodList $subject
     * @param $result
     * @param \Magento\Quote\Api\Data\CartInterface|null $quote
     * @return mixed
     * @throws \Zend_Log_Exception
     */
    public function afterGetAvailableMethods(
        \Magento\Payment\Model\MethodList $subject,
                                          $result,
        \Magento\Quote\Api\Data\CartInterface $quote = null
    ) {
        $disabledPaymentOptions = array_map('trim', explode(',', $this->helperData->getPaymentOptions()));
        if (!$quote || !$quote->getCustomerId()) {
            foreach ($result as $key => $method) {
                if (in_array($method->getCode(), $disabledPaymentOptions)) {
                    unset($result[$key]);
                }
            }
        } elseif ($quote->getCustomerId()) {
            $customerData = $this->customerRepository->getById($quote->getCustomerId());
            $companyId = $this->getUserCompanyId($customerData);
            if($companyId) {
                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                $companyRepo = $objectManager->get('Aheadworks\Ca\Api\CompanyRepositoryInterface');
                $company = $companyRepo->get($companyId, true);
                if($company->getStatus() != 'approved') {
                    foreach ($result as $key => $method) {
                        if (in_array('stripe_payments', $disabledPaymentOptions)) {
                            unset($result[$key]);
                        }
                    }
                }
            }
        }
        return $result;
    }

    public function getUserCompanyId(\Magento\Customer\Api\Data\CustomerInterface $customer)
    {
        $companyId = 0;
        if ($customer->getExtensionAttributes() && $customer->getExtensionAttributes()->getAwCaCompanyUser()) {
            $companyId = (int)$customer->getExtensionAttributes()->getAwCaCompanyUser()->getCompanyId();
      }
        return $companyId;
    }
}
