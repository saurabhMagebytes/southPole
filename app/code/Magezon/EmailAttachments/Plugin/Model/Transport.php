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

namespace Magezon\EmailAttachments\Plugin\Model;

use Magento\Framework\Mail\TransportInterface as TransportSubject;
use Magento\Framework\Registry;
use Zend\Mime\Message;
use Zend\Mime\Part;
use Zend_Mime;
use GuzzleHttp\Psr7\MimeType;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\ObjectManagerInterface;

class Transport
{
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var Filesystem\Directory\WriteInterface
     */
    protected $mediaDirectory;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var \Magezon\EmailAttachments\Helper\Data
     */
    protected $dataHelper;

    /**
     * @var \Magezon\Core\Helper\Data
     */
    protected $coreHelper;

    /**
     * Transport constructor.
     * @param Registry $registry
     * @param Filesystem $filesystem
     * @param ObjectManagerInterface $objectManager
     * @param \Magezon\Core\Helper\Data $coreHelper
     * @param \Magezon\EmailAttachments\Helper\Data $dataHelper
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function __construct(
        Registry $registry,
        Filesystem $filesystem,
        ObjectManagerInterface $objectManager,
        \Magezon\Core\Helper\Data $coreHelper,
        \Magezon\EmailAttachments\Helper\Data $dataHelper
    ) {
        $this->objectManager = $objectManager;
        $this->registry = $registry;
        $this->mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $this->coreHelper = $coreHelper;
        $this->dataHelper = $dataHelper;
    }

    /**
     * before sending the message
     *
     * @param TransportSubject $subject
     * @return void
     */
    public function beforeSendMessage(
        TransportSubject $subject
    ) {
        $type = $this->registry->registry('mgz_email_attachments_type');
        $source = $this->registry->registry('mgz_email_attachments_source');
        if ($type) {
            $storeId = $source->getStoreId();
        }
        if ($type && $this->dataHelper->isEnabledModule($storeId) && $this->dataHelper->isEnabled($type, $storeId)) {
            $message = $subject->getMessage();
            $addtitionalFiles = $this->coreHelper->unserialize($this->dataHelper->getAdditionalFiles($type, $storeId));
            foreach ($this->dataHelper->getCcTo($type, $storeId) as $email) {
                $message->addCc(trim($email));
            }
            foreach ($this->dataHelper->getBccTo($type, $storeId) as $email) {
                $message->addBcc(trim($email));
            }
            if ($type != 'order' && $this->dataHelper->isAttachPdf($type, $storeId)) {
                try {
                    $pdfModel = 'Magento\Sales\Model\Order\Pdf\\' . ucfirst($type);
                    $pdf = $this->objectManager->create($pdfModel)->getPdf([$source]);
                    $this->prepareMessage(
                        $message,
                        $pdf->render(),
                        $type . '.pdf',
                        'application/pdf'
                    );
                } catch (\Exception $e) {
                }
            }
            if ($this->dataHelper->isEnableAdditionalFiles($type, $storeId) && $addtitionalFiles) {
                foreach ($addtitionalFiles as $file) {
                    try {
                        $this->prepareMessage(
                            $message,
                            file_get_contents($this->getAbsolutePathFile($file['file'])),
                            $file['file'],
                            MimeType::fromFilename($this->getFileUrl($file['file']))
                        );
                    } catch (\Exception $e) {
                    }
                }
            }
        }
    }

    /**
     * prepare massage
     *
     * @param $message
     * @param $content
     * @param $name
     * @param $type
     * @return void
     */
    public function prepareMessage($message, $content, $name, $type)
    {
        $this->setParts($message->getBody()->getParts());
        $this->createAttachment(
            $content,
            $type,
            Zend_Mime::DISPOSITION_ATTACHMENT,
            Zend_Mime::ENCODING_BASE64,
            $name
        );
        $parts = $this->getParts();
        $mimeMessage = new Message();
        $mimeMessage->setParts($parts);
        $message->setBody($mimeMessage);
    }

    /**
     * Create attachment
     *
     * @param $body
     * @param $mimeType
     * @param $disposition
     * @param $encoding
     * @param $filename
     * @return Part
     */
    public function createAttachment(
        $body,
        $mimeType,
        $disposition = Zend_Mime::DISPOSITION_ATTACHMENT,
        $encoding = Zend_Mime::ENCODING_BASE64,
        $filename = null
    ) {
        $mp = new Part($body);
        $mp->encoding = $encoding;
        $mp->type = $mimeType;
        $mp->disposition = $disposition;
        $mp->filename = $filename;
        $this->_addAttachment($mp);
        return $mp;
    }

    /**
     * Adds an existing attachment to the mail message
     *
     * @param Zend_Mime_Part $attachment
     * @return Zend_Mail Provides fluent interface
     */
    public function _addAttachment($attachment)
    {
        $this->addPart($attachment);
        return $this;
    }

    /**
     * @param Zend_Mime_Part $part
     */
    public function addPart($part)
    {
        $this->_parts[] = $part;
    }

    /**
     * @return array
     */
    public function getParts()
    {
        return $this->_parts;
    }

    /**
     * @param array $parts
     */
    public function setParts($parts)
    {
        $this->_parts = $parts;
        return $this;
    }

    /**
     * @return string
     */
    public function getAbsolutePathFile($fileName)
    {
        return $this->mediaDirectory->getAbsolutePath('emailattachments/files/') . $fileName;
    }

    /**
     * @return string
     */
    public function getFileUrl($fileName)
    {
        return $this->coreHelper->getMediaUrl() . 'emailattachments/files/' . $fileName;
    }
}
