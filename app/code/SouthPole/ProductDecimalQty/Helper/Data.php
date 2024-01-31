<?php
namespace SouthPole\ProductDecimalQty\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku; 
use Magento\Store\Model\StoreManagerInterface;
use Magento\Directory\Model\CurrencyFactory;

class Data extends AbstractHelper
{

    const PRICE_LABEL = 'price_label/general/label_for_price';
    const ENABLE_DISABLE = 'price_label/general/enable_disable';
    const OPTION_LABEL = 'price_label/general/label_for_option';
    const MAXIMUM_QTY = 'price_label/general/maximum_qty';
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

   /**
     * @var GetSalableQuantityDataBySku 
     */
    protected $getSalableQuantityDataBySku;
    
    
    /**
     * @var StoreManagerInterface
     */
    private $storeConfig;

    /**
     * @var CurrencyFactory
     */
    private $currencyCode;

    /**
     * Declarative Initialization
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param Context $context
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Context $context,
        GetSalableQuantityDataBySku $getSalableQuantityDataBySku,
           StoreManagerInterface $storeConfig,
        CurrencyFactory $currencyFactory
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->getSalableQuantityDataBySku = $getSalableQuantityDataBySku;
        $this->storeConfig = $storeConfig;
        $this->currencyCode = $currencyFactory->create();
        parent::__construct($context);
    }

    /**
     * Returns Label for Price
     *
     * @return mixed
     */
    public function getPriceLabel()
    {
       if($this->getEnableDisable()) {
           return $this->scopeConfig->getValue(
               self::PRICE_LABEL,
               \Magento\Store\Model\ScopeInterface::SCOPE_STORE
           );
       } else {
           return '';
       }

    }
    
        /**
     * Returns label for options
     *
     * @return mixed|string
     */
    public function getOptionLabel()
    {
        if($this->getEnableDisable()) {
            return $this->scopeConfig->getValue(
                self::OPTION_LABEL,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
        } else {
            return '';
        }

    }

    /**
     * Returns module is enable or not
     *
     * @return mixed
     */
    public function getEnableDisable()
    {
        return $this->scopeConfig->getValue(
            self::ENABLE_DISABLE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }


   public function getMaximumQty()
    {
        return $this->scopeConfig->getValue(
            self::MAXIMUM_QTY,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

 public function getProductSalableQty($sku)
    {
  	$maximumQty = $this->getMaximumQty();
      $salablesQty = $this->getSalableQuantityDataBySku->execute($sku);
     foreach($salablesQty as $salable){
     $qty = $salable['qty'];
     }
     if($maximumQty<$qty)
	{
          return $maximumQty;
	}
     if($maximumQty>$qty)
	{
          return $qty;
	}
    }
    

    /**
     * @return string
     */
    public function getSymbol()
    {
        $currentCurrency = $this->storeConfig->getStore()->getCurrentCurrencyCode();
        $currency = $this->currencyCode->load($currentCurrency);
        return $currency->getCurrencySymbol();
    }
}
