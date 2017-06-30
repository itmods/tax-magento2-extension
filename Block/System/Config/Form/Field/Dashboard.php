<?php
namespace Itmods\Tax\Block\System\Config\Form\Field;

class Dashboard extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * Tax model instance
     * @var \Itmods\Tax\Model\ItmodsTax
     */
    public $itmodsTax;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    public $scopeConfig;
    /**
     * @var \Magento\Framework\UrlInterface
     */
    public $urlInterface;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Itmods\Tax\Model\ItmodsTax $itmodsTax,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->scopeConfig = $context->getScopeConfig();
        $this->urlInterface = $context->getUrlBuilder();
        $this->itmodsTax = $itmodsTax;
    }

    public function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $element = null;
        $storeId = $this->scopeConfig->getValue(
            'itmods_tax/general/store_id',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        $apiBaseUrl = $this->itmodsTax->apiBaseUrl;
        $getParams = '?store_url=' . urlencode($this->urlInterface->getBaseUrl());
        if (isset($storeId) && $storeId != 'not_registered') {
            $src = $apiBaseUrl . $storeId . $getParams; //store index
        } else {
            $src = $apiBaseUrl . 'reg' . $getParams; //registration
        }
        $elementHtml = '<iframe height="600px" width="100%" frameborder="0" src="' . $src . '"></iframe>';
        return $elementHtml;
    }
}
