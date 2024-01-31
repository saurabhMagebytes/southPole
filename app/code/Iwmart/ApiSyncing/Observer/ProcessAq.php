<?php
 
namespace Iwmart\ApiSyncing\Observer;
 
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Model\Order;

 
class ProcessAq implements ObserverInterface
{
    protected $logger;
    protected $scopeConfig;
    protected $orderRepository;
    protected $invoiceSender;
    /**
     * @var \Magento\Sales\Model\Service\InvoiceService
     */
    protected $_invoiceService;
    /**
     * @var \Magento\Framework\DB\Transaction
     */
    protected $_transaction;
 
    public function __construct(
         LoggerInterface $logger,
         ScopeConfigInterface $scopeConfig,
         OrderRepositoryInterface $orderRepositoryInterface,
         \Magento\Sales\Model\Service\InvoiceService $invoiceService,
         \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender,
         \Magento\Framework\DB\Transaction $transaction,
         \Magento\Framework\Event\ManagerInterface $eventManager
    )
    {
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->orderRepository = $orderRepositoryInterface;
        $this->_invoiceService = $invoiceService;
        $this->invoiceSender = $invoiceSender;
        $this->_transaction = $transaction;
        $this->eventManager = $eventManager;
    }
 
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/preOrderAq.log');
        $logger = new \Zend_Log();
		$logger->addWriter($writer);
        try {
            $order = $observer->getEvent()->getData('order');
            if(!$order->getEntityId()) $order = $observer->getOrder();
            $order = $this->orderRepository->get($order->getEntityId());
            $logger->info('Process AQ For Order Entity Id : '.$order->getEntityId());
            $logger->info('Process AQ For Order Status : '.$order->getStatus());
            $bucketNewPreorderData = $this->postBucketNewPreorder($order,$logger);
            $logger->info(print_r($bucketNewPreorderData,true));
            if(isset($bucketNewPreorderData['id']) && !empty($bucketNewPreorderData['id'])){
                $bucketId = $bucketNewPreorderData['id'];
                $bucketUpdatePreorderResult = $this->putBucketUpdatePreorder($bucketId,$order,$logger);
                $logger->info(print_r($bucketUpdatePreorderResult,true));
                if(isset($bucketUpdatePreorderResult['id']) && !empty($bucketUpdatePreorderResult['id'])){
                     $bucketId = $bucketUpdatePreorderResult['id'];
                     $order->setBucketPreorderId($bucketId);
                     $order->save();
                     $orderState = \Magento\Sales\Model\Order::STATE_PROCESSING;
                     if(!empty($order->getStatus()) && $order->getStatus() == $orderState){
                      $logger->info('inside if condition : '.$order->getStatus());
                         if($order->canInvoice()) {

                             $invoice = $this->_invoiceService->prepareInvoice($order);
                             $invoice->register();
                             $invoice->save();

                             $transactionSave = $this->_transaction->addObject(
                                                    $invoice
                                                )->addObject(
                                                    $invoice->getOrder()
                                                );
                             $transactionSave->save();
							 
                             $orderState = \Magento\Sales\Model\Order::STATE_PROCESSING;
                             $order->setState($orderState, true)->setStatus($orderState);
                             $order->save();

                             $this->invoiceSender->send($invoice);
                             $newState = \Magento\Sales\Model\Order::STATE_COMPLETE;
                             $order->setState($newState, true)->setStatus($newState);
                             $order->save();
                             //send notification code
                             $order->addStatusHistoryComment(
                                 __('invoice was auto created #%1.', $invoice->getId())
                             )
                             ->setIsCustomerNotified(true)
                             ->save();
                             $logger->info('-------- fire event --------');
                             $this->eventManager->dispatch('sales_order_invoice_save_after',['invoice' => $invoice]);
                         }
                     }
                }
            }
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
        }
    }
    /*
     * @return bool
     */
    public function isEnabled($scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT)
    {
        return $this->scopeConfig->isSetFlag(
            'iwmart_apisyncing/general/enabled',
            $scope
        );
    }
    /*
     * @return string
     */
    public function getApiCreditBucketUrl($scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT)
    {
        return $this->scopeConfig->getValue(
            'iwmart_apisyncing/oauth/webservice_creditbucketurl',
            $scope
        );
    }
    /*
     * @return string
     */
    public function getVendorID($scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT)
    {
        return $this->scopeConfig->getValue(
            'iwmart_apisyncing/oauth/webservice_vendorid',
            $scope
        );
    }
    /*
     * @return array
     */
    public function postBucketNewPreorder($order,$logger)
    {
        if(!$this->isEnabled()) return array('');
        // logined customer
        if ($order->getCustomerFirstname()) {
            $customerName = $order->getCustomerName();
        } else {
            // guest customer
            $billingAddress = $order->getBillingAddress();
            $customerName = $billingAddress->getFirstname() . ' ' . 
            $billingAddress->getLastname();
        }
        $billingAddress = $order->getBillingAddress();
        $billingAddressArr = array($billingAddress->getData('street'),$billingAddress->getData('city'),$billingAddress->getData('region'),$billingAddress->getData('postcode'),$billingAddress->getData('country_id'));
        $billingAddressString = implode(",", $billingAddressArr);	
        $email = $order->getCustomerEmail();
        $vendorID = $this->getVendorID();		
        $data = array("vendor" => $vendorID,"account_name" => $customerName,"billing_email" => $email,"billing_address" => $billingAddressString);

        $apiUrl = $this->getApiCreditBucketUrl();
        $apiKey = $this->getToken();
        $dataJson = json_encode($data);
        $dataResultArr = [];		
        $dataResultArr = $this->postApiServiceData($apiUrl,$apiKey,$dataJson);
        return $dataResultArr;
    }
    /*
     * @return string
     */
    public function putBucketUpdatePreorder($bucketId,$order,$logger)
    {
        $addItems = [];
        $vintageYear = '';
        $items = $order->getAllVisibleItems();
        foreach($items as $item):
           $itemSku = $item->getSku();
           $itemQty = $item->getQtyOrdered();
           $options = $item->getBuyRequest()->getData('options');
           foreach ($options as $optionId => $optionValueId){
               foreach ($item->getProduct()->getOptions() as $option) {
                  if($option->getOptionId() == $optionId && $option->getTitle() == 'Vintage'){
                    foreach($option->getValues() as $v){
                        if($v->getOptionTypeId() == $optionValueId){
                            $vintageYear = $v->getTitle();
                        }
                    }
                  }
               }
           }
           $addItems[] = array("project_no" => $itemSku,"vintage_year" => $vintageYear,"volume" => $itemQty);		   
        endforeach;		

        $data = array("items"=>$addItems);

        $apiUrl = $this->getApiCreditBucketUrl().'/'.$bucketId;
        $apiKey = $this->getToken();
        $dataJson = json_encode($data);
        $dataResultArr = [];		
        $dataResultArr = $this->putApiServiceData($apiUrl,$apiKey,$dataJson);
        return $dataResultArr;
    }
    /*
     * @return string
     */
    public function getToken($scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT)
    {
        return $this->scopeConfig->getValue(
            'iwmart_apisyncing/oauth/access_token',
            $scope
        );
    }
    /*
     * @return array
     */	
    public function postApiServiceData($apiUrl,$apiKey,$data)
    {
        $headers = array(
            "POST",
            "Content-Type: application/json; charset=utf-8",
            "X-API-Key: ".$apiKey
        ); 

        $url = $apiUrl;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        $response = curl_exec($ch); 
        curl_close($ch);
        $allDataResultArr = json_decode($response, true);
        return $allDataResultArr;
    }
    /*
     * @return array
     */	
    public function putApiServiceData($apiUrl,$apiKey,$data)
    {
        $headers = array(
            "Content-Type: application/json; charset=utf-8",
            'Content-Length: ' . strlen($data),
            "X-API-Key: ".$apiKey
        ); 

        $url = $apiUrl;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        $response = curl_exec($ch); 
        curl_close($ch);
        $allDataResultArr = json_decode($response, true);
        return $allDataResultArr;
    }
}