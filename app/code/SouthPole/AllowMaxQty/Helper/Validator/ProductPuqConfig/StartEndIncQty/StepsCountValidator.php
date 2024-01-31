<?php
namespace SouthPole\AllowMaxQty\Helper\Validator\ProductPuqConfig\StartEndIncQty;

use Aitoc\ProductUnitsAndQuantities\Helper\PuqConfig\Adjuster\ByAspect\Qty\ByIncrementsQtyAdjuster;
use Aitoc\ProductUnitsAndQuantities\Api\Data\Source\PuqConfig\PuqConfigWithoutUseConfigGettersInterface;
use Aitoc\ProductUnitsAndQuantities\Api\Data\Source\QtyTypeInterface;
use Aitoc\ProductUnitsAndQuantities\Api\Data\Source\ReplaceQtyInterface;

class StepsCountValidator extends \Aitoc\ProductUnitsAndQuantities\Helper\Validator\ProductPuqConfig\StartEndIncQty\StepsCountValidator
{
    const MAX_STEPS_COUNT = 10000000000;//Aitoc/ProductUnitsAndQuantities/view/adminhtml/web/js/validation/qty-max-steps-count-mixin.js:MAX_STEPS_COUNT

    const ERROR_MESSAGE = 'Too many possible values for product quantities. Maximum is %1.<br/>Either increase values in field "Minimum Qty Allowed in Shopping Cart" or "Qty Increments". Alternatively, decrease value in field "Maximum Qty Allowed in Shopping Cart".';

    private $byIncrementsQtyAdjuster;

   public function __construct(ByIncrementsQtyAdjuster $byIncrementsQtyAdjuster)
   {
       parent::__construct($byIncrementsQtyAdjuster);
       $this->byIncrementsQtyAdjuster = $byIncrementsQtyAdjuster;
   }

    public function isValid($value)
    {
        $this->_clearMessages();

        if (!$this->validationRequired($value)) {
            return true;
        }

        $stepsCount = $this->getStepsCount($value);

        if ($stepsCount > self::MAX_STEPS_COUNT) {
            $message = __(self::ERROR_MESSAGE, self::MAX_STEPS_COUNT, $stepsCount);
            $this->_addMessages([$message]);

            return false;
        }

        return true;
    }

    /**
     * @param PuqConfigWithoutUseConfigGettersInterface $value
     * @return bool
     */
    private function validationRequired(PuqConfigWithoutUseConfigGettersInterface $value)
    {
        if ($value->getReplaceQty() == ReplaceQtyInterface::OFF) {
            return false;
        }

        if ($value->getQtyType() != QtyTypeInterface::TYPE_DYNAMIC) {
            return false;
        }

        return true;
    }

    private function getStepsCount(PuqConfigWithoutUseConfigGettersInterface $value)
    {
        $minQty = $value->getStartQty();
        $maxQty = $value->getEndQty();
        $incQty = $value->getQtyIncrement();

        $qtyAdjuster = $this->byIncrementsQtyAdjuster;
        $adjustedMinQty = $qtyAdjuster->getAdjustedMinValue($minQty, $incQty);
        $adjustedMaxQty = $qtyAdjuster->getAdjustedMinValue($maxQty, $incQty);

        return $this->getStepsCountByResultQtyParams($adjustedMinQty, $adjustedMaxQty, $incQty);
    }

    /**
     * @param float $minQty
     * @param float $maxQty
     * @param float $incQty
     * @return float|int
     */
    private function getStepsCountByResultQtyParams($minQty, $maxQty, $incQty)
    {
        return 1 +(($maxQty - $minQty) / $incQty);
    }
}


