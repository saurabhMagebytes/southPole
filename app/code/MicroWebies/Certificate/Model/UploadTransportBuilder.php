<?php

namespace MicroWebies\Certificate\Model;

use Amasty\PDFCustom\Model\ResourceModel\TemplateRepository;
use Magento\Framework\HTTP\Mime;
use Magento\Framework\Mail\AddressConverter;
use Magento\Framework\Mail\EmailMessageInterface;
use Magento\Framework\Mail\EmailMessageInterfaceFactory;
use Magento\Framework\Mail\MailMessageInterface;
use Magento\Framework\Mail\MessageInterface;
use Magento\Framework\Mail\MessageInterfaceFactory;
use Magento\Framework\Mail\MimeMessageInterfaceFactory;
use Magento\Framework\Mail\Template\FactoryInterface;
use Magento\Framework\Mail\Template\SenderResolverInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Mail\TransportInterfaceFactory;
use Magento\Framework\ObjectManagerInterface;
use Amasty\PDFCustom\Model\ConfigProvider;
use Amasty\PDFCustom\Model\PdfProvider;
use Amasty\PDFCustom\Model\MailMessage;
use Amasty\PDFCustom\Model\MailMessageFactory;

class UploadTransportBuilder extends \Amasty\PDFCustom\Model\UploadTransportBuilder
{
    /**#@+
     * supported template types
     */
    public const TYPE_INVOICE = 'invoice';
    public const TYPE_SHIPPING = 'shipment';
    public const TYPE_CREDITMEMO = 'creditmemo';
    public const TYPE_ORDER = 'order';
    public const TYPE_PREORDER = 'preorder';
    /**#@-*/

    /**
     * @var MailMessageFactory
     */
    private $ammessageFactory;

    /**
     * @var PdfProvider
     */
    private $pdfProvider;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var MimeMessageInterfaceFactory
     */
    private $mimeMessageInterfaceFactory;

    /**
     * @var EmailMessageInterfaceFactory
     */
    private $emailMessageInterfaceFactory;

    /**
     * @var AddressConverter
     */
    private $addressConverter;

    /**
     * @var array
     */
    private $messageData;

    /**
     * @var array
     */
    private $attachments = [];

    /**
     * @var TemplateRepository
     */
    private $templateRepository;

    public function __construct(
        PdfProvider $pdfProvider,
        ConfigProvider $configProvider,
        FactoryInterface $templateFactory,
        MessageInterface $message,
        SenderResolverInterface $senderResolver,
        ObjectManagerInterface $objectManager,
        TransportInterfaceFactory $mailTransportFactory,
        MessageInterfaceFactory $messageFactory,
        TemplateRepository $templateRepository
    ) {
        parent::__construct(
            $pdfProvider,
            $configProvider,
            $templateFactory,
            $message,
            $senderResolver,
            $objectManager,
            $mailTransportFactory,
            $messageFactory,
            $templateRepository
        );
        /** @var MailMessage message */
        $this->message = $message;
        $this->ammessageFactory = $messageFactory;
        if (interface_exists(MailMessageInterface::class)) {
            $this->message = $objectManager->create(MailMessage::class);
            $this->ammessageFactory = $objectManager->create(MailMessageFactory::class);
        }
        $this->configProvider = $configProvider;
        $this->pdfProvider = $pdfProvider;
        if (interface_exists(EmailMessageInterface::class)) {
            $this->mimeMessageInterfaceFactory = $objectManager->create(MimeMessageInterfaceFactory::class);
            $this->emailMessageInterfaceFactory = $objectManager->create(EmailMessageInterfaceFactory::class);
            $this->addressConverter = $objectManager->create(AddressConverter::class);
        }
        $this->templateRepository = $templateRepository;
    }

    /**
     * @inheritDoc
     */
    public function getTransport()
    {
        try {
            $type = $this->getType();
            if($type == static::TYPE_ORDER){
                if ($this->isAttachmentAllowed($type)) {
                    $this->createAttachmentByType($type);
                }
                $secondtype = static::TYPE_PREORDER;
                if ($this->isAttachmentAllowed($secondtype)) {
                    $this->createAttachmentByType($secondtype);
                }
            }else{
                if ($this->isAttachmentAllowed($type)) {
                    $this->createAttachmentByType($type);
                }	
            }
            $this->prepareMessage();

            $mailTransport = $this->mailTransportFactory->create(['message' => clone $this->message]);
        } finally {
            $this->reset();
        }

        return $mailTransport;
    }
    /**
     * Render HTML template, convert to PDF and attach to email
     *
     * @param string $type
     */
    private function createAttachmentByType($type)
    {
        switch ($type) {
            case static::TYPE_INVOICE:
                /** @var \Magento\Sales\Model\Order\Invoice $saleObject */
                $saleObject = $this->getSaleObjectByType($type);
                $pdf = $this->pdfProvider->getInvoicePdf($saleObject);
                $this->addAttachment($pdf->render(), 'invoice' . $saleObject->getIncrementId() . '.pdf');
                break;
            case static::TYPE_SHIPPING:
                /** @var \Magento\Sales\Model\Order\Shipment $saleObject */
                $saleObject = $this->getSaleObjectByType($type);
                $pdf = $this->pdfProvider->getShipmentPdf($saleObject);
                $this->addAttachment($pdf->render(), 'shipment' . $saleObject->getIncrementId() . '.pdf');
                break;
            case static::TYPE_CREDITMEMO:
                /** @var \Magento\Sales\Model\Order\Creditmemo $saleObject */
                $saleObject = $this->getSaleObjectByType($type);
                $pdf = $this->pdfProvider->getCreditmemoPdf($saleObject);
                $this->addAttachment($pdf->render(), 'creditmemo' . $saleObject->getIncrementId() . '.pdf');
                break;
            case static::TYPE_ORDER:

                /** @var \Magento\Sales\Model\Order $saleObject */
                $saleObject = $this->getSaleObjectByType($type);
                $pdf = $this->pdfProvider->getOrderPdf($saleObject,static::TYPE_ORDER);
                $this->addAttachment($pdf->render(), 'order' . $saleObject->getIncrementId() . '.pdf');
                break;
            case static::TYPE_PREORDER:
                /** @var \Magento\Sales\Model\Order $saleObject */
                $type = static::TYPE_ORDER;
                $saleObject = $this->getSaleObjectByType($type);
                $pdf = $this->pdfProvider->getOrderPdf($saleObject,static::TYPE_PREORDER);
                $this->addAttachment($pdf->render(), 'order' . $saleObject->getIncrementId() . '.pdf');
                break;
        }
    }

    /**
     * Return current sale template type
     *
     * @return string
     */
    private function getType()
    {
        if (isset($this->templateVars[static::TYPE_INVOICE])) {

            return static::TYPE_INVOICE;
        }

        if (isset($this->templateVars[static::TYPE_CREDITMEMO])) {

            return static::TYPE_CREDITMEMO;
        }

        if (isset($this->templateVars[static::TYPE_SHIPPING])) {

            return static::TYPE_SHIPPING;
        }

        // important: order check should be last, because any sales template contains the order variable
        if (isset($this->templateVars[static::TYPE_ORDER])) {

            return static::TYPE_ORDER;
        }
        return 'unsupported';
    }

    /**
     * is current type allowed to render HTML PDF and add to email as attachment
     *
     * @param string $type
     *
     * @return bool
     */
    private function isAttachmentAllowed($type)
    {
        if (!$this->configProvider->isEnabled()) {
            return false;
        }
        switch ($type) {
            case static::TYPE_INVOICE:
                $saleObject = $this->getSaleObjectByType($type);
                $storeId = $saleObject->getStoreId();
                $customerGroupId = $saleObject->getOrder()->getCustomerGroupId();

                return $this->templateRepository->getInvoiceTemplateId($storeId, $customerGroupId)
                    && $this->configProvider->isAttachInvoice($storeId);
            case static::TYPE_SHIPPING:
                $saleObject = $this->getSaleObjectByType($type);
                $storeId = $saleObject->getStoreId();
                $customerGroupId = $saleObject->getOrder()->getCustomerGroupId();

                return $this->templateRepository->getShipmentTemplateId($storeId, $customerGroupId)
                    && $this->configProvider->isAttachShipment($storeId);
            case static::TYPE_CREDITMEMO:
                $saleObject = $this->getSaleObjectByType($type);
                $storeId = $saleObject->getStoreId();
                $customerGroupId = $saleObject->getOrder()->getCustomerGroupId();

                return $this->templateRepository->getCreditmemoTemplateId($storeId, $customerGroupId)
                    && $this->configProvider->isAttachCreditmemo($storeId);
            case static::TYPE_ORDER:
                $saleObject = $this->getSaleObjectByType($type);
                $storeId = $saleObject->getStoreId();
                $customerGroupId = $saleObject->getCustomerGroupId();

                return $this->templateRepository->getOrderTemplateId($storeId, $customerGroupId)
                    && $this->configProvider->isAttachOrder($storeId);
            case static::TYPE_PREORDER:
                $type = static::TYPE_ORDER;
                $saleObject = $this->getSaleObjectByType($type);
                $saleStatus = $saleObject->getStatus();
                if($saleStatus == 'pending'){
                    $storeId = $saleObject->getStoreId();
                    $customerGroupId = $saleObject->getCustomerGroupId();
                    return $this->templateRepository->getPreOrderTemplateId($storeId, $customerGroupId)
                       && $this->configProvider->isAttachOrder($storeId);
                }
			
        }

        return false;
    }
}
