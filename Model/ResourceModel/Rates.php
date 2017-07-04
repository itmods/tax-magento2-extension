<?php
namespace Itmods\Tax\Model\ResourceModel;

class Rates extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    public $scopeConfig;
    public $resourceConnection;
    public $taxClasses;
    public $taxCalculationRules;

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
        $this->taxClasses = [
            'STATE_SALES_TAX' => [
                'class_name' => 'Sale tax (STATE_SALES_TAX)',
            ],
            'STATE_USE_TAX' => [
                'class_name' => 'Use tax (STATE_USE_TAX)',
            ]
        ];
        $this->taxCalculationRules = [
            'STATE_SALES_TAX' => [
                'code' => 'STATE_SALES_TAX',
                'short_code' => 'SST',
            ],
            'STATE_USE_TAX' => [
                'code' => 'STATE_USE_TAX',
                'short_code' => 'SUT',
            ]
        ];
    }

    public function update($taxCalculationRates)
    {
        //update data in DB
        try {
            //begin Transaction
            $this->beginTransaction();
            $this->addProductTaxClasses();
            $this->addTaxCalculationRules();
            $this->addTaxCalculationRates($taxCalculationRates);
            $this->adTaxCalculations();
            $this->commit();
            $resultResponse = [
                'data' => [
                    'updated' => true
                ]
            ];
        } catch (\Magento\Framework\Webapi\Exception $e) {
            $this->rollBack();
            $resultResponse = [
                'error' => [
                    'message' => $e->getMessage()
                ]
            ];
        }
        return $resultResponse;
    }

    public function addProductTaxClasses()
    {
        $taxClassTableName = $this->resourceConnection->getTableName('tax_class');
        foreach ($this->taxClasses as $taxClassCode => $taxClass) {
            $sql = $this->connection->select()
                ->from(
                    ['tax_class' => $taxClassTableName],
                    ['*']
                );
            $result = $this->connection->fetchRow($sql);
            if ($result === false) {
                //add tax Class
                $this->connection->insert(
                    $taxClassTableName,
                    ['class_name' => $taxClassTableName, 'class_type' => 'PRODUCT']
                );
                $classId = $this->connection->lastInsertId();
                $this->taxClasses[$taxClassCode]['class_id'] = (int)$classId;
            } else {
                $this->taxClasses[$taxClassCode]['class_id'] = (int)$result['class_id'];
            }
        }
        return $this->taxClasses;
    }

    public function addTaxCalculationRules()
    {
        $taxCalculationRuleTableName = $this->resourceConnection->getTableName('tax_calculation_rule');
        foreach ($this->taxCalculationRules as $taxCalculationRuleCode => $taxCalculationRule) {
            $sql = $this->connection->select()
                ->from(
                    ['tax_calculation_rule' => $taxCalculationRuleTableName],
                    ['*']
                )
                ->where('tax_calculation_rule.code = ?', $taxCalculationRuleCode);
            $result = $this->connection->fetchRow($sql);
            if ($result === false) {
                $this->connection->insert(
                    $taxCalculationRuleTableName,
                    ['code' => $taxCalculationRuleCode]
                );
                $taxCalculationRuleId = $this->connection->lastInsertId();
                $this->taxCalculationRules[$taxCalculationRuleCode]['tax_calculation_rule_id']
                    = (int)$taxCalculationRuleId;
            } else {
                $this->taxCalculationRules[$taxCalculationRuleCode]['tax_calculation_rule_id']
                    = (int)$result['tax_calculation_rule_id'];
            }
        }
    }

    public function addTaxCalculationRates($taxCalculationRates)
    {
        $taxCalculationRateTableName = $this->resourceConnection->getTableName('tax_calculation_rate');
        foreach ($this->taxCalculationRules as $taxCalculationRule) {
            if (is_array($taxCalculationRates)) {
                //mass delete old tax retes
                $this->connection->delete(
                    $taxCalculationRateTableName,
                    ["code LIKE ?" => "(" . $taxCalculationRule['short_code'] . ")%"]
                );
                if (!empty($taxCalculationRates)) {
                    //add new tax rates
                    $taxCountryId = 'US';
                    $taxRegionId = '0';
                    $data = [];
                    foreach ($taxCalculationRates as $key => $taxRate) {
                        $data[] = [
                            $taxCountryId,
                            $taxRegionId,
                            $taxRate['zip_code'],
                            $taxRate[strtolower($taxCalculationRule['code'])],
                            "(" . $taxCalculationRule['short_code'] . ")US-" . $taxRate['state_abbrev']
                            . "-" . $taxRate['zip_code'] . "')"
                        ];
                    }
                    $this->connection->insertArray(
                        $taxCalculationRateTableName,
                        [
                            'tax_country_id',
                            'tax_region_id',
                            'tax_postcode',
                            'rate',
                            'code'
                        ],
                        $data
                    );
                }
            }
        }
    }

    public function adTaxCalculations()
    {
        //default customer tax class, get from magento 2 settings
        $defaultCustomerTaxClassId = $this->scopeConfig->getValue(
            'tax/classes/default_customer_tax_class',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $taxCalculationTableName = $this->resourceConnection->getTableName('tax_calculation');
        $taxCalculationRateTableName = $this->resourceConnection->getTableName('tax_calculation_rate');
        foreach ($this->taxCalculationRules as $taxCalculationRule) {
            $taxCalculationRuleId = $taxCalculationRule['tax_calculation_rule_id'];
            $customerTaxClassId = $defaultCustomerTaxClassId;
            $productTaxClassId = $this->taxClasses[$taxCalculationRule['code']]['class_id'];
            //drop old calculation
            $this->connection->delete(
                $taxCalculationTableName,
                ["tax_calculation_rule_id = '?'" => $taxCalculationRuleId]
            );
            $sql = $this->connection->select()
                ->from(
                    ['tax_calculation_rate' => $taxCalculationRateTableName],
                    ['tax_calculation_rate_id']
                )
                ->where("code LIKE '(" . $taxCalculationRule['short_code'] . ")%'");
            $taxCalculationRates = $this->connection->fetchAll($sql);
            //insert to DB
            if (empty($taxCalculationRates)) {
                continue;
            }
            $data = [];
            foreach ($taxCalculationRates as $i => $taxCalculationRate) {
                $data[] = [
                    $taxCalculationRate['tax_calculation_rate_id'],
                    $taxCalculationRuleId,
                    $customerTaxClassId,
                    $productTaxClassId
                ];
            }
            $this->connection->insertArray(
                $taxCalculationTableName,
                [
                    'tax_calculation_rate_id',
                    'tax_calculation_rule_id',
                    'customer_tax_class_id',
                    'product_tax_class_id',
                ],
                $data
            );
        }
    }
}
