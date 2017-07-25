<?php
namespace Itmods\Tax\Model\ResourceModel;

class Reports extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    public $scopeConfig;
    public $resourceConnection;
    public $taxType;

    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        parent::__construct($context);
        $this->scopeConfig = $scopeConfig;
        $this->resourceConnection = $context->getResources();
        $this->connection = $this->getConnection();
    }

    /**
     * Define resource model
     */
    public function _construct()
    {
        $this->taxType = [
            'SST',
            'SUT'
        ];
    }

    public function all($taxType, $year)
    {
        $salesOrderTaxTableName = $this->resourceConnection->getTableName('sales_order_tax');
        $salesOrderTableName = $this->resourceConnection->getTableName('sales_order');
        $salesOrderAddressTableName = $this->resourceConnection->getTableName('sales_order_address');
        $directoryCountryRegionTableName = $this->resourceConnection->getTableName('directory_country_region');
        $salesInvoicesTableName = $this->resourceConnection->getTableName('sales_invoice');
        $taxType = strtoupper($taxType);
        if (!in_array($taxType, $this->taxType)) {
            return [];
        }
        $sql = $this->connection->select()
            ->from(
                ['sales_order' => $salesOrderTableName],
                [
                    'SUM(sales_order.total_paid) AS total_paid',
                    'SUM(sales_order_tax.amount) AS tax_amount',
                    'status'
                ]
            )
            ->joinInner(
                ['sales_invoice' => $salesInvoicesTableName],
                'sales_invoice.order_id = sales_order.entity_id',
                [
                    'created_at'
                ]
            )
            ->joinLeft(
                ['sales_order_tax' => $salesOrderTaxTableName],
                'sales_order_tax.order_id = sales_order.entity_id',
                [
                    'amount',
                ]
            )
            ->joinInner(
                ['sales_order_address' => $salesOrderAddressTableName],
                'sales_order.billing_address_id = sales_order_address.entity_id',
                [
                    'region'
                ]
            )
            ->joinInner(
                ['directory_country_region' => $directoryCountryRegionTableName],
                'sales_order_address.region_id = directory_country_region.region_id',
                [
                    'code AS region_code',
                    'default_name AS region_default_name'
                ]
            )
            ->where('sales_order.status IN (\'complete\', \'processing\')')
            ->where('YEAR(sales_invoice.created_at) = ?', $year)
            ->where('sales_order_tax.code LIKE ?', '(' . $taxType . ')%')
            ->group(['sales_order_address.region'])
            ->order(['sales_order_address.region ASC']);
        $result = $this->connection->fetchAll($sql);
        return $result;
    }

    public function detail($taxType, $regionCode, $date)
    {
        $salesOrderTaxTableName = $this->resourceConnection->getTableName('sales_order_tax');
        $salesOrderTableName = $this->resourceConnection->getTableName('sales_order');
        $salesOrderAddress = $this->resourceConnection->getTableName('sales_order_address');
        $directoryCountryRegion = $this->resourceConnection->getTableName('directory_country_region');
        $salesInvoicesTableName = $this->resourceConnection->getTableName('sales_invoice');
        $taxType = strtoupper($taxType);
        if (!in_array($taxType, $this->taxType)) {
            return [];
        }
        $expDate = explode('.', $date);
        $month = (int)$expDate[0];
        $year = (int)$expDate[1];
        $sql = $this->connection->select()
            ->from(
                ['sales_order' => $salesOrderTableName],
                [
                    'SUM(sales_order.total_paid) AS total_paid',
                    'SUM(sales_order_tax.amount) AS tax_amount',
                    'status'
                ]
            )
            ->joinInner(
                ['sales_invoice' => $salesInvoicesTableName],
                'sales_invoice.order_id = sales_order.entity_id',
                [
                    'created_at'
                ]
            )
            ->joinInner(
                ['sales_order_tax' => $salesOrderTaxTableName],
                'sales_order_tax.order_id = sales_order.entity_id',
                [
                    'MAX(percent) as percent',
                ]
            )
            ->joinInner(
                ['sales_order_address' => $salesOrderAddress],
                'sales_order.billing_address_id = sales_order_address.entity_id',
                [
                    'postcode',
                    'city'
                ]
            )->joinInner(
                ['directory_country_region' => $directoryCountryRegion],
                'sales_order_address.region_id = directory_country_region.region_id',
                [
                    'default_name AS region'
                ]
            )
            ->where('sales_order.status IN (\'complete\', \'processing\')')
            ->where('directory_country_region.code = ?', $regionCode)
            ->where('sales_order_tax.code LIKE ?', '(' . $taxType . ')%')
            ->where('YEAR(sales_invoice.created_at) = ?', $year)
            ->where('MONTH(sales_invoice.created_at) = ?', $month)
            ->group(['sales_order_address.postcode'])
            ->order(['sales_order_address.postcode ASC']);
        $result = $this->connection->fetchAll($sql);
        return $result;
    }
}
