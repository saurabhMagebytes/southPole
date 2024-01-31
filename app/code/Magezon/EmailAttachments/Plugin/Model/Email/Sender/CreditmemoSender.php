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

namespace Magezon\EmailAttachments\Plugin\Model\Email\Sender;

use Magento\Framework\Registry;

class CreditmemoSender
{
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @param Registry $registry
     */
    public function __construct(
        Registry $registry
    ) {
        $this->registry = $registry;
    }

    /**
     * Prepare and send email message
     *
     * @param \Magento\Sales\Model\Order\Email\Sender\CreditmemoSender $subject
     * @param $creditmemo
     * @return void
     */
    public function beforeSend(
        \Magento\Sales\Model\Order\Email\Sender\CreditmemoSender $subject,
        $creditmemo
    ) {
        $this->registry->unregister('mgz_email_attachments_type');
        $this->registry->unregister('mgz_email_attachments_source');
        $this->registry->register('mgz_email_attachments_type', 'creditmemo');
        $this->registry->register('mgz_email_attachments_source', $creditmemo);
    }
}
