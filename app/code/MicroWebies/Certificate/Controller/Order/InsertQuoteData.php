<?php

namespace MicroWebies\Certificate\Controller\Order;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;

class InsertQuoteData extends Action
{
    protected $_checkoutSession;
    protected $_cart;

    public function __construct(
        Context $context,
        \Magento\Checkout\Model\Cart $cart,   
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        parent::__construct($context);
        $this->_cart = $cart;
        $this->_checkoutSession = $checkoutSession;
    }

    public function execute()
    {
        $certificateName = $this->getRequest()->getParam('certificate_name');
        $specialInstructions = $this->getRequest()->getParam('special_instructions');
        $quote = $this->_cart->getQuote();
        if($certificateName) $quote->setCertificateName($certificateName);
        if($specialInstructions) $quote->setSpecialInstructions($specialInstructions);
        $quote->save();
        $quote = $this->_cart->getQuote();
        return true;
    }
}
