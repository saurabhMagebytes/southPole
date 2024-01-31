<?php
 
namespace Iwmart\ApiSyncing\Observer;
 
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
 
class AfterSaveProcessAq implements ObserverInterface
{
    protected $logger;
    protected $scopeConfig;
    protected $orderRepository;
 
    public function __construct(
         LoggerInterface $logger,
         ScopeConfigInterface $scopeConfig,
         OrderRepositoryInterface $orderRepositoryInterface
    )
    {
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->orderRepository = $orderRepositoryInterface;
    }
 
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/invoicePurchaseAq.log');
        $logger = new \Zend_Log();
		$logger->addWriter($writer);
        try {
            $invoice = $observer->getEvent()->getInvoice();
            $order = $invoice->getOrder();
            $order = $this->orderRepository->get($order->getEntityId());
	
            $bucketId = $order->getBucketPreorderId();
            $aqPurchaseFlag = $order->getAqOrderPurchaseFlag();
            if(!empty($bucketId) && $aqPurchaseFlag == 0){
                $bucketOrderPurchase = $this->postBucketOrderPurchase($bucketId,$order,$logger);
                $logger->info(print_r($bucketOrderPurchase,true));
                if(isset($bucketOrderPurchase['id']) && !empty($bucketOrderPurchase['id'])){
                    $order->setAqOrderPurchaseFlag(1);
                    $order->save();
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
     * @return array
     */
    public function postBucketOrderPurchase($bucketId,$order,$logger=null)
    {
        $email = $order->getCustomerEmail();
        $purchaseDate = $order->getCreatedAt();
        $salesforceAccountId = null;
        $payment = $order->getPayment();
        $paymentMethod = $payment->getMethodInstance();
        //$paymentMethodCode = $paymentMethod->getCode();
        $paymentMethodCode = 'credit_card';
        //$logger->info(' paymentMethod '.$paymentMethodCode);
        $currency = $order->getOrderCurrencyCode();
        $paymentAmount = $order->getGrandTotal();
        $prices = [];
        $items = $order->getAllVisibleItems();
        foreach($items as $item):
           $itemSku = $item->getSku();
           $itemPrice = $item->getPrice();
           $prices[] = array("project_no" => $itemSku,"price" => $itemPrice,"currency" => $currency);
        endforeach;
		
        $data = array("purchase_date" => $purchaseDate,"salesforce_account_id" => "","payment_method" => $paymentMethodCode,"payment_amount" => $paymentAmount,"currency" => $currency,"prices" => $prices);
		
        $apiUrl = $this->getApiCreditBucketUrl().'/'.$bucketId.'/purchase';
        $apiKey = $this->getToken();
        $dataJson = json_encode($data);
        $dataResultArr = [];		
        $dataResultArr = $this->postApiServiceData($apiUrl,$apiKey,$dataJson);
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
}