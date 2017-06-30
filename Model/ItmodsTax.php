<?php

namespace Itmods\Tax\Model;

class ItmodsTax extends \Magento\Framework\Model\AbstractModel
{
    public $apiBaseUrl = 'http://138.68.80.247/framework/ems_tax/';
    public $rates;
    public $urlInterface;
    public $scopeConfig;
    public $resourceConnection;
    public $configWriter;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\UrlInterface $urlInterface,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Config\Storage\WriterInterface $configWriter,
        \Itmods\Tax\Model\Rates $rates,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->rates = $rates;
        $this->urlInterface = $urlInterface;
        $this->scopeConfig = $scopeConfig;
        $this->configWriter = $configWriter;
    }

    public function storeActivation($activationHash)
    {
        try {
            if (!empty($activationHash)) {
                $baseUrl = $this->urlInterface->getBaseUrl();
                $activationUrl = $this->apiBaseUrl
                    . 'stores/activation?store_url=' . urlencode($baseUrl)
                    . '&activation_hash=' . $activationHash;
                $arResponse = $this->request($activationUrl);
                if (isset($arResponse['storeId'])) {
                    $storeId = $arResponse['storeId'];
                }
            }
            if (!empty($storeId)) {
                $this->configWriter->save('itmods_tax/general/store_id', $storeId);
                $resultResponse = [
                    'data' => [
                        'activated' => true
                    ]
                ];
            } else {
                $resultResponse = [
                    'error' => [
                        'message' => 'Empty store Id'
                    ]
                ];
            }
        } catch (\Magento\Framework\Webapi\Exception $e) {
            $resultResponse = [
                'error' => [
                    'message' => $e->getMessage()
                ]
            ];
        }

        return $resultResponse;
    }

    public function ratesUpdate()
    {
        $storeId = $this->scopeConfig->getValue(
            'itmods_tax/general/store_id',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $updateRatesUrl = $this->apiBaseUrl . $storeId . '/rates';
        $arResponse = $this->request($updateRatesUrl);
        if (isset($arResponse['rates'])) {
            $taxCalculationRates = $arResponse['rates'];
            $resultResponse = $this->rates->update($taxCalculationRates);
        } else {
            $errorMessage = 'Failed to receive rates!';
            if (isset($arResponse['error']['message']) && !empty($arResponse['error']['message'])) {
                $errorMessage = $arResponse['error']['message'];
            }
            $resultResponse = [
                'error' => [
                    'message' => $errorMessage
                ]
            ];
        }
        return $resultResponse;
    }

    public function request($url)
    {
        try {
            $jsonResponse = file_get_contents($url);
            $arResponse = json_decode($jsonResponse, true);
            return $arResponse;
        } catch (\Magento\Framework\Webapi\Exception $e) {
            return false;
        }
    }
}
