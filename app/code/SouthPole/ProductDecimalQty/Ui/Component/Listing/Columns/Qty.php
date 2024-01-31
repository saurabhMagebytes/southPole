<?php
namespace SouthPole\ProductDecimalQty\Ui\Component\Listing\Columns;

class Qty extends \Magento\Ui\Component\Listing\Columns\Column
{
    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item[$fieldName])) {
                    $item[$fieldName] = number_format((float)$item[$fieldName], 2, '.', '');
                }
            }
        }

        return $dataSource;
    }
}
