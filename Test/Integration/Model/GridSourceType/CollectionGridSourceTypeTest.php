<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Integration\Model\GridSourceType;

use Hyva\Admin\Model\DataType\ArrayDataType;
use Hyva\Admin\Model\DataType\BooleanDataType;
use Hyva\Admin\Model\DataType\IntDataType;
use Hyva\Admin\Model\DataType\LongTextDataType;
use Hyva\Admin\Model\DataType\UnknownDataType;
use Hyva\Admin\Model\GridSourceType\CollectionGridSourceType;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Framework\Api\SearchCriteria;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea adminhtml
 */
class CollectionGridSourceTypeTest extends TestCase
{
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
        $this->assertSame(LongTextDataType::TYPE_LONG_TEXT, $columnDefinition->getType());
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

        $this->assertSame('meta title', $sut->extractValue($records[0], 'meta_title'));
        $this->assertSame('simple', $sut->extractValue($records[0], 'sku'));
        $this->assertSame('Simple Product', $sut->extractValue($records[0], 'name'));
    }
}
