<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Integration\Model\GridSourceType;

use Hyva\Admin\Api\HyvaGridSourceProcessorInterface;
use Hyva\Admin\Model\DataType\TextDataType;
use Hyva\Admin\Model\GridSourceType\RepositoryGridSourceType;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResults;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

use function array_values as values;

/**
 * @magentoAppArea adminhtml
 */
class RepositoryGridSourceTypeTest extends TestCase
{
    /**
     * @magentoDataFixture Magento/Catalog/_files/multiselect_attribute.php
     */
    public function testExtractsColumnKeys(): void
    {
        $repoGetListMethod = ProductRepositoryInterface::class . '::getList';
        $args              = [
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
        $this->assertContains('multiselect_attribute', $keys);
    }

    public function testExtractsGetterBasedColumnDefinition(): void
    {
        $repoGetListMethod = ProductRepositoryInterface::class . '::getList';
        $args              = [
            'gridName'            => 'test',
            'sourceConfiguration' => ['repositoryListMethod' => $repoGetListMethod],
        ];

        /** @var RepositoryGridSourceType $sut */
        $sut = ObjectManager::getInstance()->create(RepositoryGridSourceType::class, $args);

        $columnDefinition = $sut->getColumnDefinition('name');
        $this->assertSame(TextDataType::TYPE_TRUNCATED_TEXT, $columnDefinition->getType());
        $this->assertSame('Product Name', $columnDefinition->getLabel()); // from EAV table even though system attribute
        $this->assertSame([], $columnDefinition->getOptionArray());
    }

    public function testExtractsExtensionAttributesColumnDefinition(): void
    {
        $repoGetListMethod = ProductRepositoryInterface::class . '::getList';
        $args              = [
            'gridName'            => 'test',
            'sourceConfiguration' => ['repositoryListMethod' => $repoGetListMethod],
        ];

        /** @var RepositoryGridSourceType $sut */
        $sut = ObjectManager::getInstance()->create(RepositoryGridSourceType::class, $args);

        $columnDefinition = $sut->getColumnDefinition('website_ids');
        $this->assertSame('array', $columnDefinition->getType());
        $this->assertSame('Website Ids', $columnDefinition->getLabel());
    }

    /**
     * @magentoDataFixture Magento/Catalog/_files/multiselect_attribute.php
     */
    public function testExtractsScalarCustomAttributeColumnDefinition(): void
    {
        $repoGetListMethod = ProductRepositoryInterface::class . '::getList';
        $args              = [
            'gridName'            => 'test',
            'sourceConfiguration' => ['repositoryListMethod' => $repoGetListMethod],
        ];

        /** @var RepositoryGridSourceType $sut */
        $sut = ObjectManager::getInstance()->create(RepositoryGridSourceType::class, $args);

        $columnDefinition = $sut->getColumnDefinition('multiselect_attribute');
        $this->assertSame('array', $columnDefinition->getType());
        $this->assertSame('Multiselect Attribute', $columnDefinition->getLabel());
        $this->assertNotEmpty($columnDefinition->getOptionArray());
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/customer.php
     */
    public function testExtractsRepositoryRecords(): void
    {
        $repoGetListMethod = CustomerRepositoryInterface::class . '::getList';
        $args              = [
            'gridName'            => 'test',
            'sourceConfiguration' => ['repositoryListMethod' => $repoGetListMethod],
        ];

        /** @var RepositoryGridSourceType $sut */
        $sut            = ObjectManager::getInstance()->create(RepositoryGridSourceType::class, $args);
        $searchCriteria = (new SearchCriteria())->setPageSize(1);
        $records        = $sut->extractRecords($sut->fetchData($searchCriteria));
        $this->assertIsArray($records);
        $this->assertNotEmpty($records);
        $this->assertContainsOnly(CustomerInterface::class, $records);
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/customer.php
     */
    public function testExtractsGetterBasedValues(): void
    {
        $repoGetListMethod = CustomerRepositoryInterface::class . '::getList';
        $args              = [
            'gridName'            => 'test',
            'sourceConfiguration' => ['repositoryListMethod' => $repoGetListMethod],
        ];

        /** @var RepositoryGridSourceType $sut */
        $sut     = ObjectManager::getInstance()->create(RepositoryGridSourceType::class, $args);
        $builder = ObjectManager::getInstance()->create(SearchCriteriaBuilder::class);
        $builder->addFilter('email', 'customer@example.com')->setPageSize(1);
        $records = $sut->extractRecords($sut->fetchData($builder->create()));
        $this->assertNotEmpty($records);
        $this->assertSame('customer@example.com', $sut->extractValue($records[0] ?? null, 'email'));
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/customer.php
     */
    public function testExtractsExtensionAttributeBasedValues(): void
    {
        $repoGetListMethod = CustomerRepositoryInterface::class . '::getList';
        $args              = [
            'gridName'            => 'test',
            'sourceConfiguration' => ['repositoryListMethod' => $repoGetListMethod],
        ];

        /** @var RepositoryGridSourceType $sut */
        $sut            = ObjectManager::getInstance()->create(RepositoryGridSourceType::class, $args);
        $searchCriteria = (new SearchCriteria())->setPageSize(1);
        $records        = $sut->extractRecords($sut->fetchData($searchCriteria));
        $isSubscribed   = $sut->extractValue($records[0], 'is_subscribed');
        $this->assertSame(false, $isSubscribed);
    }

    /**
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     */
    public function testExtractsCustomAttributeBasedValues(): void
    {
        $repoGetListMethod = ProductRepositoryInterface::class . '::getList';
        $args              = [
            'gridName'            => 'test',
            'sourceConfiguration' => ['repositoryListMethod' => $repoGetListMethod],
        ];

        /** @var RepositoryGridSourceType $sut */
        $sut            = ObjectManager::getInstance()->create(RepositoryGridSourceType::class, $args);
        $searchCriteria = (new SearchCriteria())->setPageSize(1);
        $records        = values($sut->extractRecords($sut->fetchData($searchCriteria)));

        $this->assertNotEmpty($records);
        $metaTitle = $sut->extractValue($records[0] ?? null, 'meta_title');
        $this->assertSame('meta title', $metaTitle);
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/customer.php
     */
    public function testAppliesProcessors(): void
    {
        $processor = new class implements HyvaGridSourceProcessorInterface {

            public function beforeLoad($source, SearchCriteriaInterface $searchCriteria, string $gridName): void
            {
                $searchCriteria->setPageSize(1);
            }

            /**
             * @param SearchResults $rawResult
             * @param SearchCriteriaInterface $searchCriteria
             * @param string $gridName
             */
            public function afterLoad($rawResult, SearchCriteriaInterface $searchCriteria, string $gridName)
            {
                /** @var CustomerInterface[] $customers */
                $customers = $rawResult->getItems();
                $first     = values($customers)[0];
                $first->setEmail('test test test');
            }
        };

        $repoGetListMethod = CustomerRepositoryInterface::class . '::getList';
        $args              = [
            'gridName'            => 'test',
            'processors'          => [$processor],
            'sourceConfiguration' => ['repositoryListMethod' => $repoGetListMethod],
        ];

        /** @var RepositoryGridSourceType $sut */
        $sut            = ObjectManager::getInstance()->create(RepositoryGridSourceType::class, $args);
        $searchCriteria = (new SearchCriteria());
        $records        = $sut->extractRecords($sut->fetchData($searchCriteria));
        $this->assertSame(1, $searchCriteria->getPageSize());
        $this->assertSame('test test test', values($records)[0]->getEmail());
    }
}
