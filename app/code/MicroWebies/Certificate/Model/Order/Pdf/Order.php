<?php

namespace MicroWebies\Certificate\Model\Order\Pdf;

class Order extends \Amasty\PDFCustom\Model\Order\Pdf\Order
{
    /**
     * @var \Amasty\PDFCustom\Model\PdfFactory
     */
    private $pdfFactory;

    /**
     * @var \Amasty\PDFCustom\Model\Order\Html\Order
     */
    private $orderHtml;

    public function __construct(
        \Amasty\PDFCustom\Model\PdfFactory $pdfFactory,
        \Amasty\PDFCustom\Model\Order\Html\Order $orderHtml,
        array $data = []
    ) {
        $this->pdfFactory = $pdfFactory;
        $this->orderHtml = $orderHtml;
        parent::__construct($pdfFactory,$orderHtml,$data);
    }

    /**
     * Return PDF document
     *
     * @param array|\Magento\Sales\Model\ResourceModel\Order\Collection $orders
     * @return \Amasty\PDFCustom\Model\Pdf
     */
    public function getPdf($orders = [], $type = null)
    {
        /** @var \Amasty\PDFCustom\Model\Pdf $pdf */
        $pdf = $this->pdfFactory->create();
        $html = '';
        /** @var \Magento\Sales\Model\Order $order */
        foreach ($orders as $order) {
            $html .= $this->orderHtml->getHtml($order, $type);
            $orderId = $order->getId();
        }
        /*if ($type == 'preorder'){
           echo $html; die;
        }*/
        $pdf->setHtml($html);
        /*$objectManager = \Magento\Framework\App\ObjectManager::getInstance();	
        $_storeManager = $objectManager->create('Magento\Store\Model\StoreManagerInterface');
        $mediaPath = $_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
        $pdfPath = $mediaPath.'certificate/certificate_'.$orderId. '.pdf';
        file_put_contents($pdfPath, $pdf->convertToZendPDF());
        echo $pdfPath; die;*/
        return $pdf;
    }
}
