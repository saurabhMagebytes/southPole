<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package PDF Customizer for Magento 2
 */
namespace MicroWebies\Certificate\Model\Order\Html;

use Magento\Framework\DataObject;

class Invoice extends \Amasty\PDFCustom\Model\Order\Html\Invoice
{
    /**
     * @param \Magento\Sales\Model\Order\Invoice $invoice
     *
     * @return string
     */
    public function getHtml($invoice)
    {
        $order = $this->orderRepository->get($invoice->getOrderId());
        $order->setCreatedAt($this->getFormattedDate($order->getCreatedAt(), $order->getStoreId()));
        $invoice->setCreatedAt($this->getFormattedDate($invoice->getCreatedAt(), $invoice->getStoreId()));
        $templateId = $this->templateRepository->getInvoiceTemplateId(
            $invoice->getStoreId(),
            $order->getCustomerGroupId()
        );

        if (!$templateId) {
            return '';
        }

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $price = $objectManager->get('Magento\Framework\Pricing\Helper\Data');
        $currencyModel = $objectManager->create('Magento\Directory\Model\Currency'); // Instance of Currency Model
        $currencyCode = $order->getOrderCurrencyCode();
        $currencySymbol = $currencyModel->load($currencyCode)->getCurrencySymbol();
        $precision = 2;   

        $vars = [
            'order' => $order,
            'order_id' => $order->getId(),
            'invoice' => $invoice,
            'invoice_id' => $invoice->getId(),
            'comment' => $invoice->getCustomerNoteNotify() ? $invoice->getCustomerNote() : '',
            'billing' => $order->getBillingAddress(),
            'payment_html' => $this->getPaymentHtml($order),
            'store' => $order->getStore(),
            'formattedShippingAddress' => $this->getFormattedShippingAddress($order),
            'formattedBillingAddress' => $this->getFormattedBillingAddress($order),
            'orderHistoryComments' => $this->getFormattedOrderHistoryComments($order),
            'order_data' => [
                'customer_name' => $order->getCustomerName(),
                'is_not_virtual' => $order->getIsNotVirtual(),
                'email_customer_note' => $order->getEmailCustomerNote(),
                'order_subtotal' => $currencyModel->format($order->getSubTotal(), ['symbol' => $currencySymbol, 'precision'=> $precision], false, false),
                'invoice_formatted_date' => $this->getFormatedDate($order),
	            'total_quantity' => $order->getTotalQtyOrdered(),
	            'payment_method' => $order->getPayment()->getAdditionalInformation()['method_title'],
	            'amount_paid' => $currencyModel->format($order->getPayment()->getAmountPaid(), ['symbol' => $currencySymbol, 'precision'=> $precision], false, false),
                'frontend_status_label' => $order->getFrontendStatusLabel(),
                'currency_code' => $currencyCode,
                'total_due' => $currencyModel->format($order->getTotalDue(), ['symbol' => $currencySymbol, 'precision'=> $precision], false, false)
            ]
        ];
        $transportObject = new DataObject($vars);
        $this->eventManager->dispatch(
            'email_invoice_set_template_vars_before',
            ['sender' => $this, 'transport' => $transportObject->getData(), 'transportObject' => $transportObject]
        );

        $template = $this->templateFactory->get($templateId)
            ->setVars($transportObject->getData())
            ->setOptions(
                [
                    'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                    'store' => $invoice->getStoreId()
                ]
            );

        return $template->processTemplate();
    }

    public function getFormatedDate($order)
    {  	
        $originalDate = $order->getCreatedAt();
        $newFormattedDate = date('Y/m/d', strtotime($originalDate));
      	return $newFormattedDate;
    }
}
