<?php
namespace Iwmart\ApiSyncing\Cron;

class DataImport
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
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/cron_project_import.log');
        $logger = new \Zend_Log();
		$logger->addWriter($writer);
		$logger->info(__METHOD__);
        $logger->info("========= Data Syncing Process Start===========");
		
        $allDataResultArr = $this->helper->getProjectsData();
		
        if (isset($allDataResultArr) && !empty($allDataResultArr) )
        {
            foreach ($allDataResultArr as $data)
            {
                $return = $this->helper->importData($data);
                $logger->info($return);
            }
        }
        else
        {
            $logger->info("No data found for api response.");
        }
        $logger->info("========= Syncing Data Import Process End=============");
	}
}
