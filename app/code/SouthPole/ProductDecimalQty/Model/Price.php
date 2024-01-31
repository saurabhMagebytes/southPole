<?php

namespace SouthPole\ProductDecimalQty\Model;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use MageWorx\OptionBase\Helper\Data as BaseHelper;
use MageWorx\OptionBase\Helper\Price as BasePriceHelper;
use MageWorx\OptionFeatures\Model\ResourceModel\BundleSelected;
use Magento\Framework\Model\AbstractModel;
use Magento\Catalog\Model\Product\Option\Value;

class Price extends \MageWorx\OptionFeatures\Model\Price
{

    /**
     * @var ObjectManagerInterface
     */
    private \Magento\Framework\ObjectManagerInterface $objectManager;

    /**
     * @param ProductRepositoryInterface $productRepository
     * @param DataObject $specialPriceModel
     * @param DataObject $tierPriceModel
     * @param ManagerInterface $eventManager
     * @param BaseHelper $baseHelper
     * @param BasePriceHelper $basePriceHelper
     * @param ObjectManagerInterface $objectManager
     * @param BundleSelected $bundleSelected
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        DataObject $specialPriceModel,
        DataObject $tierPriceModel,
        ManagerInterface $eventManager,
        BaseHelper $baseHelper,
        BasePriceHelper $basePriceHelper,
        ObjectManagerInterface $objectManager,
        BundleSelected $bundleSelected
    ) {
        parent::__construct($productRepository, $specialPriceModel, $tierPriceModel, $eventManager, $baseHelper, $basePriceHelper, $objectManager, $bundleSelected);
        $this->objectManager = $objectManager;
    }

    /**
     * @param $option
     * @param $value
     * @return float|int|mixed|null
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function getPrice($option, $value)
    {
        if (!($this->specialPriceModel instanceof AbstractModel)
            || !($this->tierPriceModel instanceof AbstractModel)
        ) {
            return $value->getPrice(true);
        }

        $originalProduct = $option->getProduct();
        $infoBuyRequest  = $this->baseHelper->getInfoBuyRequest($originalProduct);

        $valueQty   = $this->getValueQty($option, $value, $infoBuyRequest);
        $productQty = $this->getProductQty();
        if (empty($productQty)) {
            $productQty = !empty($infoBuyRequest['qty']) ? $infoBuyRequest['qty'] : 1;
        }

        $originalProductOptions = $originalProduct->getData('options');
        foreach ($originalProductOptions as $originalProductOption) {
            $originalProductOptionValues = $originalProductOption->getValues();
            if (!empty($originalProductOptionValues[$value->getOptionTypeId()])) {
                $originalValue = $originalProductOptionValues[$value->getOptionTypeId()];
                break;
            }
        }
        if (empty($originalValue)) {
            return $value->getPrice(true);
        }

        $specialPrice         = $this->specialPriceModel->getActualSpecialPrice($originalValue);
        $tierPrices           = $this->tierPriceModel->getSuitableTierPrices($originalValue);
        $suitableTierPrice    = null;
        $suitableTierPriceQty = null;

        $isOneTime = $option->getData('one_time');
        if ($isOneTime) {
            $totalQty = (int)$valueQty; // Explicitly cast to integer
        } else {
            $totalQty = (int)($productQty * $valueQty); // Explicitly cast to integer
        }
        if (!isset($tierPrices[$totalQty])) {
            foreach ($tierPrices as $tierPriceItemQty => $tierPriceItem) {
                if ($suitableTierPriceQty < $tierPriceItemQty && $totalQty >= $tierPriceItemQty) {
                    $suitableTierPrice    = $tierPriceItem;
                    $suitableTierPriceQty = $tierPriceItemQty;
                }
            }
        } else {
            $suitableTierPrice = $tierPrices[$totalQty];
        }

        $actualTierPrice = isset($suitableTierPrice['price']) ? $suitableTierPrice['price'] : null;

        if ($suitableTierPrice && ($actualTierPrice < $specialPrice || $specialPrice === null)) {
            $price = $actualTierPrice;
        } elseif ($specialPrice !== null) {
            $price = $specialPrice;
        } else {
            if ($originalValue->getPriceType() == 'percent') {
                $productFinalPrice = $originalProduct->getPriceModel()->getBasePrice($originalProduct, $totalQty);
                $originalProduct->setFinalPrice($productFinalPrice);
                $this->eventManager->dispatch(
                    'catalog_product_get_final_price',
                    ['product' => $originalProduct, 'qty' => $totalQty]
                );
                $productFinalPrice = $originalProduct->getData('final_price');

                $price = $productFinalPrice * $originalValue->getPrice() / 100;
            } else {
                $price = $originalValue->getPrice();
            }
        }

        if ($originalValue->getPriceType() !== Value::TYPE_PERCENT &&
            $this->baseHelper->checkModuleVersion('104.0.2-p1', '', '>=', '<', 'Magento_Catalog')) {
            $calculateCustomOptionCatalogRule = $this->objectManager->get(
                \Magento\Catalog\Pricing\Price\CalculateCustomOptionCatalogRule::class
            );
            $catalogPriceValue                = $calculateCustomOptionCatalogRule->execute(
                $option->getProduct(),
                (float)$price,
                false
            );

            $price = $catalogPriceValue ? $catalogPriceValue : $price;
        }

        return $price;
    }

    protected function getValueQty($option, $value, $infoBuyRequest)
    {
        $valueQty = 1;
        if (!empty($infoBuyRequest['options_qty'][$option->getOptionId()][$value->getOptionTypeId()])) {
            $valueQty = $infoBuyRequest['options_qty'][$option->getOptionId()][$value->getOptionTypeId()];
        } elseif (!empty($infoBuyRequest['options_qty'][$option->getOptionId()])) {
            $valueQty = $infoBuyRequest['options_qty'][$option->getOptionId()];
        }

        return $valueQty;
    }
}
