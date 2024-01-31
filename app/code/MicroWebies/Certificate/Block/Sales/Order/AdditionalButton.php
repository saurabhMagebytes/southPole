<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package PDF Customizer for Magento 2
 */

namespace MicroWebies\Certificate\Block\Sales\Order;

use Amasty\PDFCustom\Model\Source\LinkType;
use Magento\Sales\Model\Order;

class AdditionalButton extends \Amasty\PDFCustom\Block\Sales\Order\AdditionalButton
{
    /**
     * @var \Amasty\PDFCustom\Model\ConfigProvider
     */
    private $configProvider;

    /**
     * @var \Magento\Framework\App\Http\Context
     */
    private $httpContext;

    /**
     * @var string
     */
    protected $_template = 'Amasty_PDFCustom::sales/order.phtml';

    /**
     * @var \Amasty\PDFCustom\Model\ResourceModel\TemplateRepository
     */
    private $templateRepository;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Amasty\PDFCustom\Model\ConfigProvider $configProvider,
        \Magento\Framework\App\Http\Context $httpContext,
        \Amasty\PDFCustom\Model\ResourceModel\TemplateRepository $templateRepository,
        array $data = []
    ) {
        parent::__construct($context, $configProvider, $httpContext, $templateRepository, $data);
        $this->configProvider = $configProvider;
        $this->httpContext = $httpContext;
        $this->templateRepository = $templateRepository;
    }

    /**
     * @return Order
     */
    protected function getOrder()
    {
        return $this->getParentBlock()->getOrder();
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _toHtml()
    {
        $order = $this->getOrder();
        $storeId = $order->getStoreId();
        if (!$this->configProvider->isEnabled($storeId) ||
            !$this->templateRepository->getOrderTemplateId($storeId, $order->getCustomerGroupId()) ||
            $this->configProvider->getOrderLinkType($storeId) != LinkType::TYPE_ADD
        ) {
            return '';
        }

        return parent::_toHtml();
    }

    /**
     * @return string
     */
    public function getLinkLabel()
    {
        return $this->configProvider->getOrderLinkLabel();
    }

    /**
     * @return string
     */
    public function getPdfUrl()
    {
        if (!$this->httpContext->getValue(\Magento\Customer\Model\Context::CONTEXT_AUTH)) {
            return $this->getUrl('custompdf/guest/order', ['order_id' => $this->getOrder()->getId()]);
        }
        return $this->getUrl('custompdf/sales/order', ['order_id' => $this->getOrder()->getId()]);
    }
}
