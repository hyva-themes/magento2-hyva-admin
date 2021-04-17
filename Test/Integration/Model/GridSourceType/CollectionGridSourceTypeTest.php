<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Integration\Model\GridSourceType;

use Hyva\Admin\Model\DataType\ArrayDataType;
use Hyva\Admin\Model\DataType\BooleanDataType;
use Hyva\Admin\Model\DataType\IntDataType;
use Hyva\Admin\Model\DataType\TextDataType;
use Hyva\Admin\Model\GridSourceType\CollectionGridSourceType;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Cms\Model\ResourceModel\Page\Collection as CmsPageCollection;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\App\ResourceConnection;
use Magento\Sales\Model\ResourceModel\Order\Collection as OrderCollection;
use Magento\Sales\Model\ResourceModel\Order\Grid\Collection as OrderGridCollection;
use Magento\Sales\Setup\SalesSetup;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea adminhtml
 */
class CollectionGridSourceTypeTest extends TestCase
{
    const TEST_FIELD = [
        'table' => 'sales_order',
        'name'  => 'test_field_foo',
    ];

    /**
     * @before
     * @after
     */
    public static function removeFixtureColumn()
    {
        $db = ObjectManager::getInstance()->get(ResourceConnection::class)->getConnection();
        if (
            0 === $db->getTransactionLevel() &&
            $db->tableColumnExists(self::TEST_FIELD['table'], self::TEST_FIELD['name'])
        ) {
            $db->dropColumn(self::TEST_FIELD['table'], self::TEST_FIELD['name']);
        }
    }

    /**
     * @magentoDataFixture Magento/Catalog/_files/multiselect_attribute.php
     */
    public function testExtractsColumnKeys(): void
    {
        $args = [
            'gridName'            => 'test',
            'sourceConfiguration' => ['collection' => ProductCollection::class],
        ];
        /** @var CollectionGridSourceType $sut */
        $sut  = ObjectManager::getInstance()->create(CollectionGridSourceType::class, $args);
        $keys = $sut->getColumnKeys();

        // getter based keys (a.k.a. "System Attributes")
        $this->assertContains('id', $keys);
        $this->assertContains('sku', $keys);
        $this->assertContains('name', $keys);

        // custom attribute based keys (a.k.a. EAV attributes)
        $this->assertContains('multiselect_attribute', $keys);
    }

    public function testExtractsGetterBasedColumnDefinition(): void
    {
        $args = [
            'gridName'            => 'test',
            'sourceConfiguration' => ['collection' => ProductCollection::class],
        ];
        /** @var CollectionGridSourceType $sut */
        $sut = ObjectManager::getInstance()->create(CollectionGridSourceType::class, $args);

        $storeIdColumnDefinition = $sut->getColumnDefinition('store_id');
        $this->assertSame(IntDataType::TYPE_INT, $storeIdColumnDefinition->getType());

        $columnDefinition = $sut->getColumnDefinition('name');
        $this->assertSame(TextDataType::TYPE_TRUNCATED_TEXT, $columnDefinition->getType());
        $this->assertSame('Product Name', $columnDefinition->getLabel()); // from EAV table even though system attribute
        $this->assertSame([], $columnDefinition->getOptionArray());
    }

    public function testExtractsAnnotationMethodBasedColumnDefinition(): void
    {
        $args = [
            'gridName'            => 'test',
            'sourceConfiguration' => ['collection' => ProductCollection::class],
        ];
        /** @var CollectionGridSourceType $sut */
        $sut = ObjectManager::getInstance()->create(CollectionGridSourceType::class, $args);

        $columnDefinition = $sut->getColumnDefinition('has_error');
        $this->assertSame(BooleanDataType::TYPE_BOOL, $columnDefinition->getType());
        $this->assertSame('Has Error', $columnDefinition->getLabel()); // from EAV table even though system attribute
        $this->assertSame([], $columnDefinition->getOptionArray());
    }

    /**
     * @magentoDataFixture Magento/Catalog/_files/multiselect_attribute.php
     */
    public function testExtractsEavCustomAttributeBasedColumnDefinition(): void
    {
        $args = [
            'gridName'            => 'test',
            'sourceConfiguration' => ['collection' => ProductCollection::class],
        ];
        /** @var CollectionGridSourceType $sut */
        $sut = ObjectManager::getInstance()->create(CollectionGridSourceType::class, $args);

        $columnDefinition = $sut->getColumnDefinition('multiselect_attribute');
        $this->assertSame(ArrayDataType::TYPE_ARRAY, $columnDefinition->getType());
        $this->assertSame('Multiselect Attribute', $columnDefinition->getLabel());
        $this->assertNotEmpty($columnDefinition->getOptionArray());
    }

    /**
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     * @magentoDataFixture Magento/Catalog/_files/products_list.php
     */
    public function testLoadsAndExtractsData(): void
    {
        $args = [
            'gridName'            => 'test',
            'sourceConfiguration' => ['collection' => ProductCollection::class],
        ];
        /** @var CollectionGridSourceType $sut */
        $sut = ObjectManager::getInstance()->create(CollectionGridSourceType::class, $args);

        $rawGridData = $sut->fetchData((new SearchCriteria())->setPageSize(1));
        $records     = $sut->extractRecords($rawGridData);
        $this->assertCount(1, $records);
        $this->assertContainsOnly(Product::class, $records);
    }

    /**
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     * @magentoDataFixture Magento/Catalog/_files/products_list.php
     */
    public function testExtractsCollectionSize(): void
    {
        $args = [
            'gridName'            => 'test',
            'sourceConfiguration' => ['collection' => ProductCollection::class],
        ];
        /** @var CollectionGridSourceType $sut */
        $sut = ObjectManager::getInstance()->create(CollectionGridSourceType::class, $args);

        $rawGridData = $sut->fetchData((new SearchCriteria())->setPageSize(1));

        $this->assertGreaterThan(1, $sut->extractTotalRowCount($rawGridData));
    }

    /**
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     */
    public function testExtractsValue(): void
    {
        $args = [
            'gridName'            => 'test',
            'sourceConfiguration' => ['collection' => ProductCollection::class],
        ];
        /** @var CollectionGridSourceType $sut */
        $sut = ObjectManager::getInstance()->create(CollectionGridSourceType::class, $args);

        $rawGridData = $sut->fetchData((new SearchCriteria())->setPageSize(1));
        $records     = $sut->extractRecords($rawGridData);

        $this->assertNotEmpty($records);
        $this->assertSame('meta title', $sut->extractValue($records[0] ?? null, 'meta_title'));
        $this->assertSame('simple', $sut->extractValue($records[0], 'sku'));
        $this->assertSame('Simple Product', $sut->extractValue($records[0], 'name'));
    }

    public function testExtractsStoreIdFieldFromCmsPageCollection(): void
    {
        $args = [
            'gridName'            => 'test',
            'sourceConfiguration' => ['collection' => CmsPageCollection::class],
        ];
        /** @var CollectionGridSourceType $sut */
        $sut    = ObjectManager::getInstance()->create(CollectionGridSourceType::class, $args);
        $column = $sut->getColumnDefinition('store_id');
        $this->assertSame('int', $column->getType());
        $result  = $sut->fetchData((new SearchCriteria())->setPageSize(1));
        $records = $sut->extractRecords($result);
        $this->assertCount(1, $records);
        $this->assertArrayHasKey(0, $records);
    }

    public function testReturnsColumnsFromGenericGridCollectionEntityType(): void
    {
        $args = [
            'gridName'            => 'test',
            'sourceConfiguration' => ['collection' => OrderGridCollection::class],
        ];
        /** @var CollectionGridSourceType $sut */
        $sut = ObjectManager::getInstance()->create(CollectionGridSourceType::class, $args);

        $columns = $sut->getColumnKeys();
        $this->assertContains('entity_id', $columns);
        $this->assertContains('customer_name', $columns);
        $this->assertContains('total_refunded', $columns);
    }

    public function testUsesDbSelectExtractorToDetermineColumnTypeWhenNeeded(): void
    {
        $args = [
            'gridName'            => 'test',
            'sourceConfiguration' => ['collection' => OrderGridCollection::class],
        ];
        /** @var CollectionGridSourceType $sut */
        $sut = ObjectManager::getInstance()->create(CollectionGridSourceType::class, $args);

        $this->assertSame('int', $sut->getColumnDefinition('entity_id')->getType());
        $this->assertSame('varchar', $sut->getColumnDefinition('customer_name')->getType());
        $this->assertSame('decimal', $sut->getColumnDefinition('total_refunded')->getType());
    }

    /**
     * @magentoDbIsolation disabled
     */
    public function testIncludesColumnsWithoutGettersInColumnList(): void
    {
        /** @var SalesSetup $setup */
        $setup = ObjectManager::getInstance()->get(SalesSetup::class);
        $setup->addAttribute('order', self::TEST_FIELD['name'], ['type' => 'varchar']);

        $args = [
            'gridName'            => 'test',
            'sourceConfiguration' => ['collection' => OrderCollection::class],
        ];
        /** @var CollectionGridSourceType $sut */
        $sut = ObjectManager::getInstance()->create(CollectionGridSourceType::class, $args);

        $columns = $sut->getColumnKeys();
        // test it contains both regular getter based fields as well as select inspection based fields
        $this->assertContains(self::TEST_FIELD['name'], $columns);
        $this->assertContains('billing_address_id', $columns);
        $this->assertContains('id', $columns);
    }
}
