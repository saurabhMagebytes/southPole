<?php

namespace MicroWebies\Certificate\Controller\Order;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

class ViewCertificate  extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Sales\Controller\AbstractController\OrderViewAuthorizationInterface
     */
    private $orderAuthorization;

    /**
     * @var \Magento\Sales\Controller\AbstractController\OrderLoaderInterface
     */
    private $orderLoader;

    /**
     * @var \Amasty\PDFCustom\Model\Order\Pdf\Order
     */
    private $orderPdf;

    /**
     * @var \Magento\Framework\Registry
     */
    private $registry;

    /**
     * @var \Magento\Framework\App\Response\Http\FileFactory
     */
    private $fileFactory;
	
    protected $orderRepository;

    public function __construct(
        \Magento\Sales\Controller\AbstractController\OrderViewAuthorizationInterface $orderAuthorization,
        \Magento\Sales\Controller\AbstractController\OrderLoaderInterface $orderLoader,
        \Amasty\PDFCustom\Model\Order\Pdf\Order $orderPdf,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        OrderRepositoryInterface $orderRepository,
        \Magento\Framework\App\Action\Context $context
    ) {
        $this->orderAuthorization = $orderAuthorization;
        $this->orderLoader = $orderLoader;
        $this->orderPdf = $orderPdf;
        $this->registry = $registry;
        $this->fileFactory = $fileFactory;
        $this->orderRepository = $orderRepository;
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->orderLoader->load($this->_request);
        try {
            $orderId = $this->getRequest()->getParam('order_id');
            $order = $this->orderRepository->get($orderId);

            $filename = 'order' . $order->getIncrementId() . '.pdf';

            /** @var \Amasty\PDFCustom\Model\Pdf $pdf */
            $pdf = $this->orderPdf->getPdf([$order]);

            $content = $pdf->render();

            $response = $this->fileFactory->create(
                $filename,
                $content,
                \Magento\Framework\App\Filesystem\DirectoryList::TMP,
                'application/pdf',
                strlen($content)
            );
            // avoid double headers or double content
            return $response;
        } catch (\Exception $e) {
            return $this->getRedirect();
        }
    }

    protected function getRedirect()
    {
        return $this->_redirect('sales/guest/form');
    }
}  
  
