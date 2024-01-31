<?php
namespace Iwmart\ApiSyncing\Cron;

class UpdateStock
{
    protected $helper;

    public function __construct(
        \Iwmart\ApiSyncing\Helper\Data $helper
    )
    {
        $this->helper = $helper;
    }
	public function execute()
	{
        $allDataResultArr = $this->helper->getAllCreditsData();		
        if (isset($allDataResultArr) && !empty($allDataResultArr) )
        {
            foreach ($allDataResultArr as $data)
            {
                $return = $this->helper->UpdateStockData($data);
            }
        }
	}
}
