<?php

namespace SouthPole\CustomCheckout\Controller\Order;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Checkout\Model\Cart;

class InsertQuoteData extends Action
{
    protected $_checkoutSession;
    protected $_cart;
    protected $jsonResultFactory;

    public function __construct(
        Context $context,
        Cart $cart,
        \Magento\Checkout\Model\Session $checkoutSession,
        JsonFactory $jsonResultFactory
    ) {
        parent::__construct($context);
        $this->_cart = $cart;
        $this->_checkoutSession = $checkoutSession;
        $this->jsonResultFactory = $jsonResultFactory;
    }

    public function execute()
    {
        $result = $this->jsonResultFactory->create();

        try {
            $certificateName = $this->getRequest()->getParam('certificate_name');
            $specialInstructions = $this->getRequest()->getParam('special_instructions');
            $quote = $this->_cart->getQuote();

            if ($certificateName) {
                $quote->setCertificateName($certificateName);
            }
            if ($specialInstructions) {
                $quote->setSpecialInstructions($specialInstructions);
            }

            $quote->save();
            $this->_checkoutSession->setQuoteId($quote->getId());

            $result->setData(['success' => true]);
        } catch (\Exception $e) {
            $result->setData(['error' => $e->getMessage()]);
        }

        return $result;
    }
}

