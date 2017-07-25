<?php

namespace Itmods\Tax\Model;

class Reports extends \Magento\Framework\Model\AbstractModel
{
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Itmods\Tax\Model\ResourceModel\Reports $resourceReports,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->resourceReports = $resourceReports;
    }

    public function all($taxType, $year = null)
    {
        return $this->resourceReports->all($taxType, $year);
    }

    public function detail($taxType, $regionCode, $date)
    {
        return $this->resourceReports->detail($taxType, $regionCode, $date);
    }
}
