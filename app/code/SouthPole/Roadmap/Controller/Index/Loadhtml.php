<?php

namespace SouthPole\Roadmap\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;

class Loadhtml extends Action
{
    public function __construct(
        Context $context
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $htmlContent = file_get_contents('/var/www/html/test.html');
        $result = $this->resultFactory->create(ResultFactory::TYPE_RAW);
        $result->setContents($htmlContent);
        return $result;
    }
    
}
