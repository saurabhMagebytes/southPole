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

namespace Magezon\EmailAttachments\Block\Adminhtml\Form\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;
use Magento\Framework\UrlInterface;
use Magezon\EmailAttachments\View\Element\Html\File;

class AttachCondition extends AbstractFieldArray
{
    /**
     * @var File
     */
    protected $additionalFiles;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @param Context $context
     * @param UrlInterface $urlBuilder
     * @param array $data
     */
    public function __construct(
        Context $context,
        UrlInterface $urlBuilder,
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        parent::__construct($context, $data);
    }

    /**
     * Prepare rendering the new field by adding all the needed columns
     */
    protected function _prepareToRender()
    {
        $this->addColumn('file', [
            'label' => __('File'),
            'renderer' => $this->getAdditionalFiles(),
            'extra_params' => 'multiple="multiple"'
        ]);
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }

    /**
     * Prepare existing row data object
     *
     * @param DataObject $row
     */
    protected function _prepareArrayRow(DataObject $row): void
    {
        $options = [];
        $tax = $row->getTax();
        if ($tax !== null) {
            $options['option_' . $this->getAdditionalFiles()->calcOptionHash($tax)] = 'selected="selected"';
        }
        try {
            $row->setData('option_extra_attrs', [
                'link' => $this->getMediaUrl() . $row->getData('file')
            ]);
        } catch (\Exception $e) {
            $row->setData('option_extra_attrs', [
                'link' => '#'
            ]);
        }
    }

    /**
     * @return File
     */
    private function getAdditionalFiles()
    {
        if (!$this->additionalFiles) {
            $this->additionalFiles = $this->getLayout()->createBlock(
                File::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }
        return $this->additionalFiles;
    }

    /**
     * Get file url
     * @return string
     */
    public function getMediaUrl()
    {
        return $this->urlBuilder->getBaseUrl(['_type' => UrlInterface::URL_TYPE_MEDIA]) . 'emailattachments/files/';
    }
}
