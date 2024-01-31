<?php
namespace SouthPole\ProductDecimalQty\Block;

use Magento\Customer\Model\Session;
use Magento\Framework\View\Element\Template;

class CompanyAccountLink extends Template
{
    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @param Template\Context $context
     * @param Session $customerSession
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Session $customerSession,
        array $data = []
    ) {
        $this->customerSession = $customerSession;
        parent::__construct($context, $data);
    }

    /**
     * Check if the customer is not logged in
     *
     * @return bool
     */
    public function isCustomerNotLoggedIn()
    {
        return !$this->customerSession->isLoggedIn();
    }


    /**
     * Get the label for the link
     *
     * @return string
     */
    public function getLinkLabel()
    {
        return __('Create Company Account');
    }

    /**
     * Get the path for the link
     *
     * @return string
     */
    public function getLinkPath()
    {
        return $this->getUrl('aw_ca/company/create');
    }
}


