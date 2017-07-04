<?php

namespace Itmods\Tax\Controller\Rates;

class Update extends \Magento\Framework\App\Action\Action
{
    /**
     * Tax model instance
     * @var \Itmods\Tax\Model\ItmodsTax
     */
    public $itmodsTax;
    /**
     * Json data helper
     * @var \Magento\Framework\Json\Helper\Data
     */
    public $jsonDataHelper;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Itmods\Tax\Model\ItmodsTax $itmodsTax,
        \Magento\Framework\Json\Helper\Data $jsonDataHelper
    ) {
        parent::__construct($context);
        $this->itmodsTax = $itmodsTax;
        $this->jsonDataHelper = $jsonDataHelper;
    }

    public function execute()
    {
        $response = $this->itmodsTax->ratesUpdate();
        return $this->getResponse()->representJson(
            $this->jsonDataHelper->jsonEncode($response)
        );
    }
}
