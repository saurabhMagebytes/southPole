<?php

namespace MicroWebies\Certificate\Model;

class PdfProvider extends \Amasty\PDFCustom\Model\PdfProvider
{
    /**
     * @var Pdf[]
     */
    protected $invoicePdfStorage = [];
    /**
     * @var Pdf[]
     */
    protected $orderPdfStorage = [];

    /**
     * @var Pdf[]
     */
    protected $preOrderPdfStorage = [];

    /**
     * @var Pdf[]
     */
    protected $shipmentPdfStorage = [];

    /**
     * @var Pdf[]
     */
    private $creditmemoPdfStorage = [];

    /**
     * @var Order\Pdf\Invoice
     */
    private $invoicePdf;

    /**
     * @var Order\Pdf\Order
     */
    private $orderPdf;

    /**
     * @var Order\Pdf\Shipment
     */
    private $shipmentPdf;

    /**
     * @var Order\Pdf\Creditmemo
     */
    private $creditmemoPdf;
    private $customOrderPdf;

    public function __construct(
        \Amasty\PDFCustom\Model\Order\Pdf\Invoice $invoicePdf,
        \Amasty\PDFCustom\Model\Order\Pdf\Order $orderPdf,
        \Amasty\PDFCustom\Model\Order\Pdf\Shipment $shipmentPdf,
        \Amasty\PDFCustom\Model\Order\Pdf\Creditmemo $creditmemoPdf,
        \MicroWebies\Certificate\Model\Order\Pdf\Order $customOrderPdf
    ) {
        $this->invoicePdf = $invoicePdf;
        $this->orderPdf = $orderPdf;
        $this->shipmentPdf = $shipmentPdf;
        $this->creditmemoPdf = $creditmemoPdf;
        $this->customOrderPdf = $customOrderPdf;
        parent::__construct($invoicePdf,$orderPdf,$shipmentPdf,$creditmemoPdf);
    }

    /**
     * @param \Magento\Sales\Model\Order $saleObject
     *
     * @return Pdf
     */
    public function getOrderPdf(\Magento\Sales\Model\AbstractModel $saleObject, $type = null)
    {
        if (!isset($this->orderPdfStorage[$saleObject->getId()])) {
            $this->orderPdfStorage[$saleObject->getId()] = $this->customOrderPdf->getPdf([$saleObject], $type);
        }
        if ($type == 'preorder'){
            if (!isset($this->preOrderPdfStorage[$saleObject->getId()])) {
                $this->preOrderPdfStorage[$saleObject->getId()] = $this->customOrderPdf->getPdf([$saleObject], $type);
            }
            return $this->preOrderPdfStorage[$saleObject->getId()];
        }
        return $this->orderPdfStorage[$saleObject->getId()];
    }
}
