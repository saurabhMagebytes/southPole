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

namespace Magezon\EmailAttachments\Model\Config\Backend;

use Magento\Framework\Serialize\Serializer\Json;
use Magezon\Core\Helper\Data;

class AdditionalFiles extends \Magento\Config\Model\Config\Backend\Serialized
{
    const PATH = 'emailattachments/files/';

    /**
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     */
    protected $mediaDirectory;

    /**
     * @var \Magento\MediaStorage\Model\File\UploaderFactory
     */
    protected $fileUploaderFactory;

    /**
     * @var Data
     */
    protected $coreHelper;

    /**
     * @var \Magento\Framework\Filesystem\Driver\File
     */
    protected $file;

    /**
     * @var \Magento\Config\Model\Config\Backend\File\RequestData
     */
    protected $requestData;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * AdditionalFiles constructor.
     *
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\MediaStorage\Model\File\UploaderFactory $fileUploaderFactory
     * @param \Magento\Framework\Filesystem\Driver\File $file
     * @param Data $coreHelper
     * @param array $data
     * @param Json|null $serializer
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\MediaStorage\Model\File\UploaderFactory $fileUploaderFactory,
        \Magento\Framework\Filesystem\Driver\File $file,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magezon\Core\Helper\Data $coreHelper,
        \Magento\Config\Model\Config\Backend\File\RequestData $requestData,
        array $data = [],
        Json $serializer = null
    ) {
        $this->mediaDirectory = $filesystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);
        $this->fileUploaderFactory = $fileUploaderFactory;
        $this->file = $file;
        $this->messageManager = $messageManager;
        $this->coreHelper = $coreHelper;
        $this->requestData = $requestData;
        parent::__construct(
            $context,
            $registry,
            $config,
            $cacheTypeList,
            $resource,
            $resourceCollection,
            $data,
            $serializer
        );
    }

    /**
     * Process data after load
     *
     * @return void
     */
    protected function _afterLoad()
    {
        $value = $this->getValue();
        $value = $this->coreHelper->unserialize($value);
        $this->setValue($value);
    }

    /**
     * Unset array element with '__empty' key
     *
     * @return $this
     */
    public function beforeSave()
    {
        $value = $this->getValue() ?: [];
        if (is_array($value)) {
            unset($value['__empty']);
        }
        $target = $this->mediaDirectory->getAbsolutePath(self::PATH);
        foreach ($value as $field => &$item) {
            try {
                $oldFile = isset($item['file'][$field]['file_orig']) ? $item['file'][$field]['file_orig'] : '';
                $fileData = $this->getFileData($field);
                if (isset($fileData['tmp_name']) && $fileData['tmp_name']) {
                    try {
                        $uploader = $this->fileUploaderFactory->create(['fileId' => $fileData]);
                        $uploader->setAllowedExtensions(['pdf', 'doc', 'zip', 'txt', 'mp4', 'xls', 'jpg']);
                        $uploader->setAllowRenameFiles(true);
                        $result = $uploader->save($target);
                        $item['file'] = $result['file'];
                    } catch (\Exception $e) {
                        $this->messageManager->addError(__('%1 Disallowed file type.', $fileData['name']));
                        unset($value[$field]);
                    }
                } elseif ($oldFile) {
                    $item['file'] = $oldFile;
                } else {
                    unset($value[$field]);
                }
            } catch (\Exception $e) {
            }
        }

        $oldValue = $this->coreHelper->unserialize($this->getOldValue());
        if ($oldValue) {
            foreach ($oldValue as $oldfield => $val) {
                if (!array_key_exists($oldfield, $value)) {
                    $fileName = $val['file'];
                    try {
                        if ($this->file->isExists($target . $fileName)) {
                            $this->file->deleteFile($target . $fileName);
                        }
                    } catch (\Exception $e) {
                    }
                }
            }
        }
        $this->setValue($this->coreHelper->serialize($value));
        return $this;
    }

    /**
     * Receiving uploaded file data
     *
     * @return array
     * @since 100.1.0
     */
    protected function getFileData($fileId)
    {
        $file = [];
        $value = $this->getValue();
        $path = $this->getPath();
        $tmpName = $this->requestData->getTmpName($path);
        if (isset($tmpName[$fileId]['file'])) {
            $file['tmp_name'] = $tmpName[$fileId]['file'];
            $name = $this->requestData->getName($this->getPath());
            $file['name'] = $name[$fileId]['file'];
        } elseif (!empty($value['tmp_name'])) {
            $file['tmp_name'] = $value['tmp_name'];
            $file['name'] = isset($value['value']) ? $value['value'] : $value['name'];
        }
        return $file;
    }
}
