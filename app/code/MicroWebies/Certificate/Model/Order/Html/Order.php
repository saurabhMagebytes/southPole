<?php

namespace MicroWebies\Certificate\Model\Order\Html;

use Magento\Framework\DataObject;
use Magento\Framework\Stdlib\DateTime;

class Order extends \Amasty\PDFCustom\Model\Order\Html\Order
{
    /**
     * @param \Magento\Sales\Model\Order $order
     *
     * @return string
     */
    public function getHtml($order, $type = null)
    {
        $order = $this->orderRepository->get($order->getId());

        if ($type == 'preorder') {
            $templateId = $this->templateRepository->getPreOrderTemplateId($order->getStoreId(), $order->getCustomerGroupId());
        }else{
            $templateId = $this->templateRepository->getOrderTemplateId($order->getStoreId(), $order->getCustomerGroupId());			
		}
		
        if (!$templateId) {
            return '';
        }
        $companyId = '';	
        $company_name = '';	
        $company_location = '';
        $orderCustomerId = $order->getCustomerId();
      
        if ($orderCustomerId) {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();	
            $customerRepository = $objectManager->create('Magento\Customer\Api\CustomerRepositoryInterface');
            $customer = $customerRepository->getById($orderCustomerId);
            $companyId = $this->getUserCompanyId($customer);
            if ($companyId) {
                 $companyRepository = $objectManager->get('Aheadworks\Ca\Api\CompanyRepositoryInterface');
                 $company = $companyRepository->get($companyId, true);
                 $company_name = $company->getName();
                 $street = '';
                 if(!empty($company->getStreet())) $street = $company->getStreet();    
                 $company_location = $street.' '.$company->getCity().' '.$company->getRegion().' '.$company->getCountryId();
            }
        }
		
        $items = $order->getAllVisibleItems();
        $projects_name = '';
        $projectsNameArr = [];
        $projects_total_qty = 0;
        foreach($items as $item):
           $_productName = $item->getName();
           $projects_total_qty = $projects_total_qty+$item->getQtyOrdered();	
           $projectsNameArr[] = $_productName;		   
        endforeach;
        if(!empty($projectsNameArr)) $projects_name = implode(',',$projectsNameArr);
        $vars = [
            'order' => $order,
            'order_id' => $order->getId(),
            'billing' => $order->getBillingAddress(),
            'payment_html' => $this->getPaymentHtml($order),
            'store' => $order->getStore(),
            'formattedShippingAddress' => $this->getFormattedShippingAddress($order),
            'formattedBillingAddress' => $this->getFormattedBillingAddress($order),
            'order_data' => [
                'customer_name' => $order->getCustomerName(),
                'is_not_virtual' => $order->getIsNotVirtual(),
                'email_customer_note' => $order->getEmailCustomerNote(),
                'frontend_status_label' => $order->getFrontendStatusLabel()
            ],
            'certificate_name' => $order->getCertificateName(),
            'certificate_special_Instructions' => $order->getSpecialInstructions(),
            'projects_total_qty' => $projects_total_qty,
            'projects_name' => $projects_name,
            'company_id' => $companyId,
            'company_name' => $company_name,
            'company_location' => $company_location,
           'order_formated_date' => $this->getFormatedDate($order),
        ];
            $transportObject = new DataObject($vars);
        $this->eventManager->dispatch(
            'email_order_set_template_vars_before',
            ['sender' => $this, 'transport' => $transportObject, 'transportObject' => $transportObject]
        );
        $template = $this->templateFactory->get($templateId)
            ->setVars($transportObject->getData())
            ->setOptions(
                [
                    'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                    'store' => $order->getStoreId()
                ]
            );

        return $template->processTemplate();
    }
    /**
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @return int|string
     */
    public function getUserCompanyId(\Magento\Customer\Api\Data\CustomerInterface $customer)
    {
        $companyId = '';
        if ($customer->getExtensionAttributes() && $customer->getExtensionAttributes()->getAwCaCompanyUser()) {
            $companyId = (int)$customer->getExtensionAttributes()->getAwCaCompanyUser()->getCompanyId();        
      }
        return $companyId;
    }
  public function getFormatedDate($order)
    {  	
        $originalDate = $order->getCreatedAt();
        $newFormattedDate = date('Y/m/d', strtotime($originalDate));
      return $newFormattedDate;
    }
}
