<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package PDF Customizer for Magento 2
 */

namespace MicroWebies\Certificate\Model\Template;

use Amasty\Base\Model\MagentoVersion;
use Amasty\PDFCustom\Model\Template;
use Magento\Framework\Module\Manager;

class VariablesOptionRepository extends \Amasty\PDFCustom\Model\Template\VariablesOptionRepository
{
    /**
     * @var Manager
     */
    private $moduleManager;

    /**
     * @var MagentoVersion
     */
    private $magentoVersion;

    public function __construct(
        Manager $moduleManager,
        MagentoVersion $magentoVersion
    ) {
        $this->moduleManager = $moduleManager;
        $this->magentoVersion = $magentoVersion;
        parent::__construct($moduleManager,$magentoVersion);
    }
    /**
     * @param Template $template
     *
     * @return array
     */
    public function getAdditionalVariables($template)
    {
        $options = [];

        if ($this->moduleManager->isEnabled('Amasty_Deliverydate')
            && version_compare($this->magentoVersion->get(), '2.2.0', '>=')
        ) {
            /**
             * extension_attributes is not supported on Magento 2.1
             * @see \Magento\Framework\Filter\Template::getVariable
             * in the first elseif there is instanceof \Magento\Framework\DataObject
             *  but extension attributes object is not
             * should be another elseif which is added in 2.2
             */

            $options[] = [
                'label' => __('Amasty Delivery Date: Date'),
                'value' => '{{var order.extension_attributes.getAmdeliverydateDate()|raw}}'
            ];
            $options[] = [
                'label' => __('Amasty Delivery Date: Time'),
                'value' => '{{var order.extension_attributes.getAmdeliverydateTime()|raw}}'
            ];
            $options[] = [
                'label' => __('Amasty Delivery Date: Comment'),
                'value' => '{{var order.extension_attributes.getAmdeliverydateComment()|raw}}'
            ];
        }

        if ($this->moduleManager->isEnabled('Amasty_Perm')) {
            $options[] = [
                'label' => __('Amasty Sales Reps and Dealers: Dealer Name'),
                'value' => '{{var order.getOrderDealer().getContactname()|raw}}'
            ];
            $options[] = [
                'label' => __('Amasty Sales Reps and Dealers: Dealer Email'),
                'value' => '{{var order.getOrderDealer().getEmail()|raw}}'
            ];
            $options[] = [
                'label' => __('Amasty Sales Reps and Dealers: Dealer Description'),
                'value' => '{{var order.getOrderDealer().getDescription()}}'
            ];
        }
		
        $options[] = [
            'label' => __('Certificate: Certificate Name'),
            'value' => '{{var certificate_name}}'
        ];
        $options[] = [
            'label' => __('Certificate: Certificate Special Instructions'),
            'value' => '{{var certificate_special_Instructions}}'
        ];
        $options[] = [
            'label' => __('Order: Projects Total Qty'),
            'value' => '{{var projects_total_qty}}'
        ];
        $options[] = [
            'label' => __('Order : Projects Name'),
            'value' => '{{var projects_name}}'
        ];
        $options[] = [
            'label' => __('Company : Is Company User'),
            'value' => '{{var company_id}}'
        ];
        $options[] = [
            'label' => __('Company: Company Name'),
            'value' => '{{var company_name}}'
        ];
        $options[] = [
            'label' => __('Company: Company Location'),
            'value' => '{{var company_location}}'
        ];
        return $options;
    }
}
