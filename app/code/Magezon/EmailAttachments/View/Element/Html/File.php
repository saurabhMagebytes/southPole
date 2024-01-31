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

namespace Magezon\EmailAttachments\View\Element\Html;

use Magento\Framework\View\Element\AbstractBlock;

class File extends AbstractBlock
{
    /**
     * Set "name" for <dynamicRow> element
     *
     * @param string $value
     * @return $this
     */
    public function setInputName($value)
    {
        return $this->setName($value);
    }

    /**
     * Set "id" for <dynamicRow> element
     *
     * @param $value
     * @return $this
     */
    public function setInputId($value)
    {
        return $this->setId($value);
    }

    /**
     * Set element's HTML ID
     *
     * @param string $elementId ID
     * @return $this
     */
    public function setId($elementId)
    {
        $this->setData('id', $elementId);
        return $this;
    }

    /**
     * Render HTML
     *
     * @return string
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function _toHtml()
    {
        if (!$this->_beforeToHtml()) {
            return '';
        }
        $columnName = $this->getColumnName();
        $inputName2 = $this->_getCellInputElementName($columnName . '_orig');
        $html = '<input type="hidden" name="' .
            $inputName2 .
            '" value="<%- ' . $columnName . ' %>"/><input name="' .
            $this->getName() .
            '" value="<%- file %>" type="file"/><% if (option_extra_attrs.link) { %><a href="<%- option_extra_attrs.link %>" target="_blank"><%- ' . $columnName . ' %></a><% } %>';
        return $html;
    }

    /**
     * Get name for cell element
     *
     * @param string $rowId
     * @param string $columnName
     * @return string
     */
    protected function _getCellInputElementId($rowId, $columnName)
    {
        return $rowId . '_' . $columnName;
    }

    /**
     * Get id for cell element
     *
     * @param string $columnName
     * @return string
     */
    protected function _getCellInputElementName($columnName)
    {
        return $this->getName() . '[<%- _id %>][' . $columnName . ']';
    }
}
