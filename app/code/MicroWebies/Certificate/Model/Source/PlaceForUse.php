<?php

namespace MicroWebies\Certificate\Model\Source;

class PlaceForUse extends \Amasty\PDFCustom\Model\Source\PlaceForUse
{
    public const TYPE_ORDER = 1;
    public const TYPE_INVOICE = 2;
    public const TYPE_SHIPPING = 3;
    public const TYPE_CREDIT_MEMO = 4;
    public const TYPE_PREORDER = 5;

    /**
     * @inheritDoc
     */
    public function toOptionArray()
    {
        return [
            ['value' => 0, 'label' => ''],
            ['value' => self::TYPE_ORDER, 'label' => __('Order')],
            ['value' => self::TYPE_INVOICE, 'label' => __('Invoice')],
            ['value' => self::TYPE_SHIPPING, 'label' => __('Shipping')],
            ['value' => self::TYPE_CREDIT_MEMO, 'label' => __('Credit Memo')],
            ['value' => self::TYPE_PREORDER, 'label' => __('PreOrder')],
        ];
    }
}
