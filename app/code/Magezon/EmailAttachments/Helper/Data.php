<?php
/**
 * Magezon
 *
 * This source file is subject to the Magezon Software License, which is available at https://www.magezon.com/license
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to https://www.magezon.com for more information.
 *
 * @category  Magezon
 * @package   Magezon_EmailAttachments
 * @copyright Copyright (C) 2022 Magezon (https://www.magezon.com)
 */

namespace Magezon\EmailAttachments\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Data constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->storeManager = $storeManager;
    }

    /**
     * @param string $key
     * @param null|int $_store
     * @return null|string
     */
    public function getConfig($key, $_store = null)
    {
        $store = $this->storeManager->getStore($_store);
        $result = $this->scopeConfig->getValue(
            'mgz_email_attachments/' . $key,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
        return $result;
    }

    /**
     * Get status module
     * @return bool
     */
    public function isEnabledModule($storeId)
    {
        return $this->getConfig('general/enabled', $storeId);
    }

    /**
     * @param $type
     * @return bool
     */
    public function isEnabled($type, $storeId)
    {
        $key = $type . '/enabled';
        return $this->getConfig($key, $storeId);
    }

    /**
     * @param $type
     * @return bool
     */
    public function isAttachPdf($type, $storeId)
    {
        $key = $type . '/is_enable_attach_pdf';
        return $this->getConfig($key, $storeId);
    }

    /**
     * @param $type
     * @return string|null
     */
    public function getAdditionalFiles($type, $storeId)
    {
        $key = $type . '/additional_files';
        return $this->getConfig($key, $storeId);
    }

    /**
     * @param $type
     * @return bool
     */
    public function isEnableAdditionalFiles($type, $storeId)
    {
        $key = $type . '/is_enable_attach_additional';
        return $this->getConfig($key, $storeId);
    }

    /**
     * @param $type
     * @return array
     */
    public function getCcTo($type, $storeId)
    {
        $key = $type . '/cc_email';
        $emails = $this->getConfig($key, $storeId) ? explode(',', trim($this->getConfig($key, $storeId))) : [];
        return $this->isEmail($emails);
    }

    /**
     * @param $type
     * @return array
     */
    public function getBccTo($type, $storeId)
    {
        $key = $type . '/bcc_email';
        $emails = $this->getConfig($key, $storeId) ? explode(',', trim($this->getConfig($key, $storeId))) : [];
        return $this->isEmail($emails);
    }

    /**
     * @param $emails
     * @return array
     */
    public function isEmail($emails)
    {
        foreach ($emails as $key => $email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                unset($emails[$key]);
            }
        }
        return array_values($emails);
    }
}
