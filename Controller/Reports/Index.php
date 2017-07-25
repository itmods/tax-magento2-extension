<?php

namespace Itmods\Tax\Controller\Reports;

class Index extends \Magento\Framework\App\Action\Action
{
    /**
     * Tax model instance
     * @var \Itmods\Tax\Model\ItmodsTax
     */
    public $itmodsReports;
    /**
     * Json data helper
     * @var \Magento\Framework\Json\Helper\Data
     */
    public $jsonDataHelper;

    public $scopeConfig;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Itmods\Tax\Model\Reports $itmodsReports,
        \Magento\Framework\Json\Helper\Data $jsonDataHelper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        parent::__construct($context);
        $this->itmodsReports = $itmodsReports;
        $this->jsonDataHelper = $jsonDataHelper;
        $this->scopeConfig = $scopeConfig;
    }

    public function execute()
    {
        $year = $this->getRequest()->getParam('year', null);
        $taxType = $this->getRequest()->getParam('tax_type', null);
        $storeId = $this->getRequest()->getParam('store_id', null);
        $thisStoreId = $this->scopeConfig->getValue(
            'itmods_tax/general/store_id',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        if ($storeId == $thisStoreId) {
            $response = $this->itmodsReports->all($taxType, $year);
        } else {
            $response = [
                'error' => [
                    'message' => 'Access denied!'
                ]
            ];
        }
        return $this->getResponse()->representJson(
            $this->jsonDataHelper->jsonEncode($response)
        );
    }
}
