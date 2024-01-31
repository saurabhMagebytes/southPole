<?php

namespace Iwmart\ApiSyncing\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateStock extends Command
{
    const NAME_ARGUMENT = "name";
    const NAME_OPTION = "option";

    public function __construct(
        \Magento\Framework\Filesystem\Driver\File $filesystem,
        \Magento\Framework\Xml\Parser $parser,
        \Magento\Framework\Json\Helper\Data $jsonHelper
    )
    {
        $this->filesystem = $filesystem;
        $this->parser = $parser;
        $this->jsonHelper = $jsonHelper;
        
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName("WebService:api_updatestock");
        $this->setDescription("Syncing Data from WebService");
        $this->setDefinition([
            new InputArgument(self::NAME_ARGUMENT, InputArgument::OPTIONAL, "Name"),
            new InputOption(self::NAME_OPTION, "-a", InputOption::VALUE_NONE, "Option functionality")
        ]);
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument(self::NAME_ARGUMENT);
        $option = $input->getOption(self::NAME_OPTION);
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $state = $objectManager->get('Magento\Framework\App\State');
        $state->setAreaCode('adminhtml');
		
        $helper = $objectManager->get('Iwmart\ApiSyncing\Helper\Data');
        $allDataResultArr = $helper->getAllCreditsData();		
        if (isset($allDataResultArr) && !empty($allDataResultArr) )
        {
            foreach ($allDataResultArr as $data)
            {
                $return = $helper->UpdateStockData($data);
                $output->writeln($return);
                //die;
            }
        }
        else
        {
            $output->writeln("No data found for import.");
        }

        $output->writeln("========= Syncing Data Import Process End=============");
        return 1;
    }
}
