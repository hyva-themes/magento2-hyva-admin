<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Functional\Model\GridSourceType;

use Hyva\Admin\Model\GridSourceType\RepositoryGridSourceType;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea adminhtml
 */
class RepositoryGridSourceTypeTest extends TestCase
{
    public function testExtractsColumnKeys(): void
    {
        $repoGetListMethod  = ProductRepositoryInterface::class . '::getList';
        $args = [
            'gridName'            => 'test',
            'sourceConfiguration' => ['repositoryListMethod' => $repoGetListMethod],
        ];
        /** @var RepositoryGridSourceType $sut */
        $sut  = ObjectManager::getInstance()->create(RepositoryGridSourceType::class, $args);
        $keys = $sut->getColumnKeys();

        // getter based keys (a.k.a. "System Attributes")
        $this->assertContains('id', $keys);
        $this->assertContains('sku', $keys);
        $this->assertContains('name', $keys);

        // extension attribute based keys
        $this->assertContains('website_ids', $keys);
        $this->assertContains('bundle_product_options', $keys);
        $this->assertContains('configurable_product_options', $keys);
        $this->assertContains('configurable_product_links', $keys);

        // custom attribute based keys (a.k.a. EAV attributes)
        $this->assertContains('weight', $keys);
        $this->assertContains('activity', $keys);
        $this->assertContains('gender', $keys);
    }

    public function testExtractsGetterBasedColumnDefinition(): void
    {
        $repoGetListMethod  = ProductRepositoryInterface::class . '::getList';
        $args = [
            'gridName'            => 'test',
            'sourceConfiguration' => ['repositoryListMethod' => $repoGetListMethod],
        ];

        /** @var RepositoryGridSourceType $sut */
        $sut  = ObjectManager::getInstance()->create(RepositoryGridSourceType::class, $args);

        $columnDefinition = $sut->getColumnDefinition('name');
        $this->assertSame('string', $columnDefinition->getType());
        $this->assertSame('Product Name', $columnDefinition->getLabel()); // from EAV table even though system attribute
        $this->assertSame([], $columnDefinition->getOptionArray());
    }

    public function testExtractsExtensionAttributesColumnDefinition(): void
    {
        $repoGetListMethod  = ProductRepositoryInterface::class . '::getList';
        $args = [
            'gridName'            => 'test',
            'sourceConfiguration' => ['repositoryListMethod' => $repoGetListMethod],
        ];

        /** @var RepositoryGridSourceType $sut */
        $sut  = ObjectManager::getInstance()->create(RepositoryGridSourceType::class, $args);

        $columnDefinition = $sut->getColumnDefinition('website_ids');
        $this->assertSame('array', $columnDefinition->getType());
        $this->assertSame('Website Ids', $columnDefinition->getLabel());
    }

    public function testExtractsScalarCustomAttributeColumnDefinition(): void
    {
        $repoGetListMethod  = ProductRepositoryInterface::class . '::getList';
        $args = [
            'gridName'            => 'test',
            'sourceConfiguration' => ['repositoryListMethod' => $repoGetListMethod],
        ];

        /** @var RepositoryGridSourceType $sut */
        $sut  = ObjectManager::getInstance()->create(RepositoryGridSourceType::class, $args);

        $columnDefinition = $sut->getColumnDefinition('activity');
        $this->assertSame('string', $columnDefinition->getType());
        $this->assertSame('Activity', $columnDefinition->getLabel());
        $this->assertNotEmpty($columnDefinition->getOptionArray());
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/customer.php
     */
    public function testExtractsRepositoryRecords(): void
    {
        $repoGetListMethod  = CustomerRepositoryInterface::class . '::getList';
        $args = [
            'gridName'            => 'test',
            'sourceConfiguration' => ['repositoryListMethod' => $repoGetListMethod],
        ];

        /** @var RepositoryGridSourceType $sut */
        $sut  = ObjectManager::getInstance()->create(RepositoryGridSourceType::class, $args);
        $data = $sut->fetchData();
        $records = $sut->extractRecords($data);
        $this->assertIsArray($records);
        $this->assertNotEmpty($records);
        $this->assertContainsOnly(CustomerInterface::class, $records);
    }
}
