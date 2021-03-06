<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\AdvancedPricingImportExport\Test\Unit\Model\Import;

use \Magento\AdvancedPricingImportExport\Model\Import\AdvancedPricing as AdvancedPricing;
use \Magento\CatalogImportExport\Model\Import\Proxy\Product\ResourceFactory as ResourceFactory;
use \Magento\CatalogImportExport\Model\Import\Product\RowValidatorInterface as RowValidatorInterface;

/**
 * @SuppressWarnings(PHPMD)
 */
class AdvancedPricingTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var ResourceFactory |\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resourceFactory;

    /**
     * @var \Magento\Catalog\Helper\Data |\PHPUnit_Framework_MockObject_MockObject
     */
    protected $catalogData;

    /**
     * @var \Magento\Catalog\Model\Product |\PHPUnit_Framework_MockObject_MockObject
     */
    protected $productModel;

    /**
     * @var \Magento\CatalogImportExport\Model\Import\Product\StoreResolver |\PHPUnit_Framework_MockObject_MockObject
     */
    protected $storeResolver;

    /**
     * @var \Magento\CatalogImportExport\Model\Import\Product|PHPUnit_Framework_MockObject_MockObject
     */
    protected $importProduct;

    /**
     * @var AdvancedPricing\Validator |\PHPUnit_Framework_MockObject_MockObject
     */
    protected $validator;

    /**
     * @var AdvancedPricing\Validator\Website |\PHPUnit_Framework_MockObject_MockObject
     */
    protected $websiteValidator;

    /**
     * @var AdvancedPricing\Validator\GroupPrice |\PHPUnit_Framework_MockObject_MockObject
     */
    protected $groupPriceValidator;

    /**
     * @var \Magento\ImportExport\Model\Resource\Helper |\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resourceHelper;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $connection;

    /**
     * @var \Magento\ImportExport\Model\Resource\Import\Data|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $dataSourceModel;

    /**
     * @var array
     */
    protected $cachedSkuToDelete;

    /**
     * @var array
     */
    protected $oldSkus;

    /**
     * @var AdvancedPricing |\PHPUnit_Framework_MockObject_MockObject
     */
    protected $advancedPricing;

    public function setUp()
    {
        $this->jsonHelper = $this->getMock(
            '\Magento\Framework\Json\Helper\Data',
            [],
            [],
            '',
            false
        );
        $this->_importExportData = $this->getMock(
            '\Magento\ImportExport\Helper\Data',
            [],
            [],
            '',
            false
        );
        $this->resourceHelper = $this->getMock(
            '\Magento\ImportExport\Model\Resource\Helper',
            [],
            [],
            '',
            false
        );
        $this->_resource = $this->getMock(
            '\Magento\Framework\App\Resource',
            ['getConnection'],
            [],
            '',
            false
        );
        $this->connection = $this->getMockForAbstractClass(
            '\Magento\Framework\DB\Adapter\AdapterInterface',
            [],
            '',
            false
        );
        $this->_resource->expects($this->any())->method('getConnection')->willReturn($this->connection);
        $this->dataSourceModel = $this->getMock(
            '\Magento\ImportExport\Model\Resource\Import\Data',
            [],
            [],
            '',
            false
        );
        $this->resourceFactory = $this->getMock(
            '\Magento\CatalogImportExport\Model\Import\Proxy\Product\ResourceFactory',
            [],
            [],
            '',
            false
        );
        $this->productModel = $this->getMock(
            '\Magento\Catalog\Model\Product',
            [],
            [],
            '',
            false
        );
        $this->catalogData = $this->getMock(
            '\Magento\Catalog\Helper\Data',
            [],
            [],
            '',
            false
        );
        $this->storeResolver = $this->getMock(
            '\Magento\CatalogImportExport\Model\Import\Product\StoreResolver',
            [],
            [],
            '',
            false
        );
        $this->importProduct = $this->getMock(
            '\Magento\CatalogImportExport\Model\Import\Product',
            [],
            [],
            '',
            false
        );
        $this->validator = $this->getMock(
            '\Magento\AdvancedPricingImportExport\Model\Import\AdvancedPricing\Validator',
            ['isValid', 'getMessages'],
            [],
            '',
            false
        );
        $this->websiteValidator = $this->getMock(
            '\Magento\AdvancedPricingImportExport\Model\Import\AdvancedPricing\Validator\Website',
            [],
            [],
            '',
            false
        );
        $this->groupPriceValidator = $this->getMock(
            '\Magento\AdvancedPricingImportExport\Model\Import\AdvancedPricing\Validator\GroupPrice',
            [],
            [],
            '',
            false
        );

        $this->advancedPricing = $this->getAdvancedPricingMock([
            'retrieveOldSkus',
            'validateRow',
            'addRowError',
            'saveProductPrices',
            'getCustomerGroupId',
            'getWebSiteId',
            'deleteProductTierAndGroupPrices',
        ]);

        $this->advancedPricing->expects($this->any())->method('retrieveOldSkus')->willReturn([]);
    }

    public function testGetEntityTypeCode()
    {
        $result = $this->advancedPricing->getEntityTypeCode();
        $expectedResult = 'advanced_pricing';

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @dataProvider validateRowResultDataProvider
     */
    public function testValidateRowResult($rowData, $validatedRows, $invalidRows, $behavior, $expectedResult)
    {
        $rowNum = 0;
        $advancedPricingMock = $this->getAdvancedPricingMock([
            'retrieveOldSkus',
            'addRowError',
            'saveProductPrices',
            'getCustomerGroupId',
            'getWebSiteId',
            'getBehavior',
        ]);
        $this->setPropertyValue($advancedPricingMock, '_validatedRows', $validatedRows);
        $this->setPropertyValue($advancedPricingMock, '_invalidRows', $invalidRows);
        $this->validator->expects($this->any())->method('isValid')->willReturn(true);
        $advancedPricingMock->expects($this->any())->method('getBehavior')->willReturn($behavior);

        $result = $advancedPricingMock->validateRow($rowData, $rowNum);
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @dataProvider validateRowAddRowErrorCallDataProvider
     */
    public function testValidateRowAddRowErrorCall($rowData, $validatedRows, $invalidRows, $behavior, $error)
    {
        $rowNum = 0;
        $advancedPricingMock = $this->getAdvancedPricingMock([
            'retrieveOldSkus',
            'addRowError',
            'saveProductPrices',
            'getCustomerGroupId',
            'getWebSiteId',
            'getBehavior',
        ]);
        $this->setPropertyValue($advancedPricingMock, '_validatedRows', $validatedRows);
        $this->setPropertyValue($advancedPricingMock, '_invalidRows', $invalidRows);
        $this->validator->expects($this->any())->method('isValid')->willReturn(true);
        $advancedPricingMock->expects($this->any())->method('getBehavior')->willReturn($behavior);
        $advancedPricingMock->expects($this->once())->method('addRowError')->with($error, $rowNum);

        $advancedPricingMock->validateRow($rowData, $rowNum);
    }

    public function testValidateRowValidatorCall()
    {
        $rowNum = 0;
        $rowData = [
            \Magento\AdvancedPricingImportExport\Model\Import\AdvancedPricing::COL_SKU => 'sku value',
        ];
        $advancedPricingMock = $this->getAdvancedPricingMock([
            'retrieveOldSkus',
            'addRowError',
            'saveProductPrices',
            'getCustomerGroupId',
            'getWebSiteId',
        ]);
        $this->setPropertyValue($advancedPricingMock, '_validatedRows', []);
        $this->validator->expects($this->once())->method('isValid')->willReturn(false);
        $messages = ['value'];
        $this->validator->expects($this->once())->method('getMessages')->willReturn($messages);
        $advancedPricingMock->expects($this->once())->method('addRowError')->with('value', $rowNum);

        $advancedPricingMock->validateRow($rowData, $rowNum);
    }

    public function testSaveAdvancedPricingAddRowErrorCall()
    {
        $rowNum = 0;
        $testBunch = [
            $rowNum => [
                'bunch',
            ]
        ];
        $this->dataSourceModel->expects($this->at(0))->method('getNextBunch')->willReturn($testBunch);
        $this->advancedPricing->expects($this->once())->method('validateRow')->willReturn(false);
        $this->advancedPricing->expects($this->any())->method('saveProductPrices')->will($this->returnSelf());

        $this->advancedPricing
            ->expects($this->once())
            ->method('addRowError')
            ->with(RowValidatorInterface::ERROR_SKU_IS_EMPTY, $rowNum);

        $this->advancedPricing->saveAdvancedPricing();
    }

    /**
     * @dataProvider saveAdvancedPricingDataProvider
     */
    public function testSaveAdvancedPricing(
        $data,
        $tierCustomerGroupId,
        $groupCustomerGroupId,
        $tierWebsiteId,
        $groupWebsiteId,
        $expectedTierPrices,
        $expectedGroupPrices
    ) {
        $this->dataSourceModel->expects($this->at(0))->method('getNextBunch')->willReturn($data);
        $this->advancedPricing->expects($this->once())->method('validateRow')->willReturn(true);

        $this->advancedPricing->expects($this->atLeastOnce())->method('getCustomerGroupId')->willReturnMap([
            [$data[0][AdvancedPricing::COL_TIER_PRICE_CUSTOMER_GROUP], $tierCustomerGroupId],
            [$data[0][AdvancedPricing::COL_GROUP_PRICE_CUSTOMER_GROUP], $groupCustomerGroupId]
        ]);

        $this->advancedPricing->expects($this->atLeastOnce())->method('getWebSiteId')->willReturnMap([
            [$data[0][AdvancedPricing::COL_TIER_PRICE_WEBSITE], $tierWebsiteId],
            [$data[0][AdvancedPricing::COL_GROUP_PRICE_WEBSITE], $groupWebsiteId]
        ]);

        $this->advancedPricing->expects($this->exactly(2))->method('saveProductPrices')->withConsecutive(
            [$expectedTierPrices, AdvancedPricing::TABLE_TIER_PRICE],
            [$expectedGroupPrices, AdvancedPricing::TABLE_GROUPED_PRICE]
        )->will($this->returnSelf());

        $this->advancedPricing->saveAdvancedPricing();
    }

    public function testDeleteAdvancedPricingFormListSkuToDelete()
    {
        $skuOne = 'sku value';
        $skuTwo = 'sku value';
        $data = [
            0 => [
                AdvancedPricing::COL_SKU => $skuOne
            ],
            1 => [
                AdvancedPricing::COL_SKU => $skuTwo
            ],
        ];

        $this->dataSourceModel->expects($this->at(0))->method('getNextBunch')->willReturn($data);
        $this->advancedPricing->expects($this->any())->method('validateRow')->willReturn(true);
        $expectedSkuList = ['sku value'];
        $this->advancedPricing->expects($this->exactly(2))->method('deleteProductTierAndGroupPrices')->withConsecutive(
            [$expectedSkuList, AdvancedPricing::TABLE_GROUPED_PRICE],
            [$expectedSkuList, AdvancedPricing::TABLE_TIER_PRICE]
        )->will($this->returnSelf());

        $this->advancedPricing->deleteAdvancedPricing();
    }

    public function testDeleteAdvancedPricingResetCachedSkuToDelete()
    {
        $this->setPropertyValue($this->advancedPricing, '_cachedSkuToDelete', 'some value');
        $this->dataSourceModel->expects($this->at(0))->method('getNextBunch')->willReturn([]);

        $this->advancedPricing->deleteAdvancedPricing();

        $cachedSkuToDelete = $this->getPropertyValue($this->advancedPricing, '_cachedSkuToDelete');
        $this->assertNull($cachedSkuToDelete);
    }

    public function testReplaceAdvancedPricing()
    {
        $this->markTestSkipped('The method replaceAdvancedPricing is empty');
    }

    public function saveAdvancedPricingDataProvider()
    {
        // @codingStandardsIgnoreStart
        return [
            [
                '$data' => [
                    0 => [
                        AdvancedPricing::COL_SKU => 'sku value',
                        //tier
                        AdvancedPricing::COL_TIER_PRICE_WEBSITE => 'tier price website value',
                        AdvancedPricing::COL_TIER_PRICE_CUSTOMER_GROUP => 'tier price customer group value - not all groups ',
                        AdvancedPricing::COL_TIER_PRICE_QTY => 'tier price qty value',
                        AdvancedPricing::COL_TIER_PRICE => 'tier price value',
                        //group
                        AdvancedPricing::COL_GROUP_PRICE_WEBSITE => 'group price website value',
                        AdvancedPricing::COL_GROUP_PRICE_CUSTOMER_GROUP => 'group price customer group value - not all groups ',
                        AdvancedPricing::COL_GROUP_PRICE => 'group price value',
                    ],
                ],
                '$tierCustomerGroupId' => 'tier customer group id value',
                '$groupCustomerGroupId' => 'group customer group id value',
                '$tierWebsiteId' => 'tier website id value',
                '$groupWebsiteId' => 'group website id value',
                '$expectedTierPrices' => [
                    'sku value' => [
                        [
                            'all_groups' => false,//$rowData[self::COL_TIER_PRICE_CUSTOMER_GROUP] == self::VALUE_ALL_GROUPS
                            'customer_group_id' => 'tier customer group id value',//$tierCustomerGroupId
                            'qty' => 'tier price qty value',
                            'value' => 'tier price value',
                            'website_id' => 'tier website id value',
                        ],
                    ],
                ],
                '$expectedGroupPrices' => [
                    'sku value' => [
                        [
                            'all_groups' => AdvancedPricing::DEFAULT_ALL_GROUPS_GROUPED_PRICE_VALUE,
                            'customer_group_id' => 'group customer group id value',//$groupCustomerGroupId
                            'value' => 'group price value',
                            'website_id' => 'group website id value',
                        ],
                    ],
                ],
            ],
            [// tier customer group is equal to all group
                 '$data' => [
                     0 => [
                         AdvancedPricing::COL_SKU => 'sku value',
                         //tier
                         AdvancedPricing::COL_TIER_PRICE_WEBSITE => 'tier price website value',
                         AdvancedPricing::COL_TIER_PRICE_CUSTOMER_GROUP => AdvancedPricing::VALUE_ALL_GROUPS,
                         AdvancedPricing::COL_TIER_PRICE_QTY => 'tier price qty value',
                         AdvancedPricing::COL_TIER_PRICE => 'tier price value',
                         //group
                         AdvancedPricing::COL_GROUP_PRICE_WEBSITE => 'group price website value',
                         AdvancedPricing::COL_GROUP_PRICE_CUSTOMER_GROUP => 'group price customer group value',
                         AdvancedPricing::COL_GROUP_PRICE => 'group price value',
                     ],
                 ],
                 '$tierCustomerGroupId' => 'tier customer group id value',
                 '$groupCustomerGroupId' => 'group customer group id value',
                 '$tierWebsiteId' => 'tier website id value',
                 '$groupWebsiteId' => 'group website id value',
                 '$expectedTierPrices' => [
                     'sku value' => [
                         [
                             'all_groups' => true,//$rowData[self::COL_TIER_PRICE_CUSTOMER_GROUP] == self::VALUE_ALL_GROUPS
                             'customer_group_id' => 'tier customer group id value',//$tierCustomerGroupId
                             'qty' => 'tier price qty value',
                             'value' => 'tier price value',
                             'website_id' => 'tier website id value',
                         ],
                     ],
                 ],
                 '$expectedGroupPrices' => [
                     'sku value' => [
                         [
                             'all_groups' => AdvancedPricing::DEFAULT_ALL_GROUPS_GROUPED_PRICE_VALUE,
                             'customer_group_id' => 'group customer group id value',//$groupCustomerGroupId
                             'value' => 'group price value',
                             'website_id' => 'group website id value',
                         ],
                     ],
                 ],
            ],
            [
                '$data' => [
                    0 => [
                        AdvancedPricing::COL_SKU => 'sku value',
                        //tier
                        AdvancedPricing::COL_TIER_PRICE_WEBSITE => null,
                        AdvancedPricing::COL_TIER_PRICE_CUSTOMER_GROUP => 'tier price customer group value - not all groups',
                        AdvancedPricing::COL_TIER_PRICE_QTY => 'tier price qty value',
                        AdvancedPricing::COL_TIER_PRICE => 'tier price value',
                        //group
                        AdvancedPricing::COL_GROUP_PRICE_WEBSITE => 'group price website value',
                        AdvancedPricing::COL_GROUP_PRICE_CUSTOMER_GROUP => 'group price customer group value',
                        AdvancedPricing::COL_GROUP_PRICE => 'group price value',
                    ],
                ],
                '$tierCustomerGroupId' => 'tier customer group id value',
                '$groupCustomerGroupId' => 'group customer group id value',
                '$tierWebsiteId' => 'tier website id value',
                '$groupWebsiteId' => 'group website id value',
                '$expectedTierPrices' => [],
                '$expectedGroupPrices' => [
                    'sku value' => [
                        [
                            'all_groups' => AdvancedPricing::DEFAULT_ALL_GROUPS_GROUPED_PRICE_VALUE,
                            'customer_group_id' => 'group customer group id value',//$groupCustomerGroupId
                            'value' => 'group price value',
                            'website_id' => 'group website id value',
                        ],
                    ],
                ],
            ],
            [
                '$data' => [
                    0 => [
                        AdvancedPricing::COL_SKU => 'sku value',
                        //tier
                        AdvancedPricing::COL_TIER_PRICE_WEBSITE => 'tier price website value',
                        AdvancedPricing::COL_TIER_PRICE_CUSTOMER_GROUP => 'tier price customer group value - not all groups',
                        AdvancedPricing::COL_TIER_PRICE_QTY => 'tier price qty value',
                        AdvancedPricing::COL_TIER_PRICE => 'tier price value',
                        //group
                        AdvancedPricing::COL_GROUP_PRICE_WEBSITE => null,
                        AdvancedPricing::COL_GROUP_PRICE_CUSTOMER_GROUP => 'group price customer group value',
                        AdvancedPricing::COL_GROUP_PRICE => 'group price value',
                    ],
                ],
                '$tierCustomerGroupId' => 'tier customer group id value',
                '$groupCustomerGroupId' => 'group customer group id value',
                '$tierWebsiteId' => 'tier website id value',
                '$groupWebsiteId' => 'group website id value',
                '$expectedTierPrices' => [
                    'sku value' => [
                        [
                            'all_groups' => false,//$rowData[self::COL_TIER_PRICE_CUSTOMER_GROUP] == self::VALUE_ALL_GROUPS
                            'customer_group_id' => 'tier customer group id value',//$tierCustomerGroupId
                            'qty' => 'tier price qty value',
                            'value' => 'tier price value',
                            'website_id' => 'tier website id value',
                        ],
                    ]
                ],
                '$expectedGroupPrices' => [],
            ],
        ];
        // @codingStandardsIgnoreEnd
    }

    public function validateRowResultDataProvider()
    {
        return [
            [
                '$rowData' => [
                    AdvancedPricing::COL_SKU => 'sku value',
                ],
                '$validatedRows' => [
                    0 => ['value']
                ],
                '$invalidRows' => [
                    0 => ['value']
                ],
                '$behavior' => null,
                '$expectedResult' => false,
            ],
            [
                '$rowData' => [
                    AdvancedPricing::COL_SKU => null,
                ],
                '$validatedRows' => [],
                '$invalidRows' => [
                    0 => ['value']
                ],
                '$behavior' => \Magento\ImportExport\Model\Import::BEHAVIOR_DELETE,
                '$expectedResult' => false,
            ],
            [
                '$rowData' => [
                    AdvancedPricing::COL_SKU => 'sku value',
                ],
                '$validatedRows' => [],
                '$invalidRows' => [
                    0 => ['value']
                ],
                '$behavior' => \Magento\ImportExport\Model\Import::BEHAVIOR_DELETE,
                '$expectedResult' => true,
            ],
            [
                '$rowData' => [
                    AdvancedPricing::COL_SKU => 'sku value',
                ],
                '$validatedRows' => [],
                '$invalidRows' => [
                    0 => ['value']
                ],
                '$behavior' => null,
                '$expectedResult' => false,
            ],
            [
                '$rowData' => [
                    AdvancedPricing::COL_SKU => 'sku value',
                ],
                '$validatedRows' => [],
                '$invalidRows' => [],
                '$behavior' => null,
                '$expectedResult' => true,
            ],
        ];
    }

    public function validateRowAddRowErrorCallDataProvider()
    {
        return [
            [
                '$rowData' => [
                    AdvancedPricing::COL_SKU => null,
                ],
                '$validatedRows' => [],
                '$invalidRows' => [
                    0 => ['value']
                ],
                '$behavior' => \Magento\ImportExport\Model\Import::BEHAVIOR_DELETE,
                '$error' => RowValidatorInterface::ERROR_SKU_IS_EMPTY,
            ],
            [
                '$rowData' => [
                    AdvancedPricing::COL_SKU => false,
                ],
                '$validatedRows' => [],
                '$invalidRows' => [
                    0 => ['value']
                ],
                '$behavior' => null,
                '$error' => RowValidatorInterface::ERROR_ROW_IS_ORPHAN,
            ],
        ];
    }

    /**
     * Get any object property value.
     *
     * @param $object
     * @param $property
     */
    protected function getPropertyValue($object, $property)
    {
        $reflection = new \ReflectionClass(get_class($object));
        $reflectionProperty = $reflection->getProperty($property);
        $reflectionProperty->setAccessible(true);

        return $reflectionProperty->getValue($object);
    }

    /**
     * Set object property value.
     *
     * @param $object
     * @param $property
     * @param $value
     */
    protected function setPropertyValue(&$object, $property, $value)
    {
        $reflection = new \ReflectionClass(get_class($object));
        $reflectionProperty = $reflection->getProperty($property);
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($object, $value);

        return $object;
    }

    /**
     * Get AdvancedPricing Mock object with predefined methods.
     *
     * @param array $methods
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function getAdvancedPricingMock($methods = [])
    {
        return $this->getMock(
            '\Magento\AdvancedPricingImportExport\Model\Import\AdvancedPricing',
            $methods,
            [
                $this->jsonHelper,
                $this->_importExportData,
                $this->resourceHelper,
                $this->dataSourceModel,
                $this->_resource,
                $this->resourceFactory,
                $this->productModel,
                $this->catalogData,
                $this->storeResolver,
                $this->importProduct,
                $this->validator,
                $this->websiteValidator,
                $this->groupPriceValidator,
            ],
            ''
        );
    }
}
