<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Integration\Model\FormEntity;

use Hyva\Admin\Model\FormEntity\FormLoadEntityRepository;
use Hyva\Admin\ViewModel\HyvaForm\FormFieldDefinitionInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Cms\Api\BlockRepositoryInterface;
use Magento\Cms\Api\Data\BlockInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Model\Order as OrderModel;
use Magento\Sales\Model\ResourceModel\Order as OrderResourceModel;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

use function array_filter as filter;
use function array_values as values;

/**
 * @covers \Hyva\Admin\Model\FormEntity\FormLoadEntity
 * @covers FormLoadEntityRepository
 */
class FormLoadEntityRepositoryTest extends TestCase
{
    public function aTestMethod()
    {
        return 0;
    }

    private function getCmsBlockFixtureBlockId(): int
    {
        /** @var BlockRepositoryInterface $blockRepository */
        /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
        $blockRepository = ObjectManager::getInstance()->get(BlockRepositoryInterface::class);
        $searchCriteriaBuilder = ObjectManager::getInstance()->create(SearchCriteriaBuilder::class);
        $searchCriteriaBuilder->addFilter(BlockInterface::IDENTIFIER, 'enabled_block');
        return (int) values($blockRepository->getList($searchCriteriaBuilder->create())->getItems())[0]->getId();
    }

    private function assertContainsField(string $expected, array $fields): void
    {
        $matches = filter($fields, function (FormFieldDefinitionInterface $f) use ($expected): bool {
            return $f->getName() === $expected;
        });
        $this->assertTrue(count($matches) > 0, sprintf('Expected field "%s" was not present', $expected));
    }

    public function testReturnsLoadedEntity(): void
    {
        /** @var FormLoadEntityRepository $sut */
        $sut    = ObjectManager::getInstance()->create(FormLoadEntityRepository::class);
        $result = $sut->fetchTypeAndMethod(__CLASS__ . '::aTestMethod', [], 'int');
        $this->assertSame(0, $result->getValue());
        $this->assertSame([], $result->getFieldDefinitions());
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/customer.php
     */
    public function testReturnsCustomerAttributes(): void
    {
        /** @var FormLoadEntityRepository $sut */
        $sut           = ObjectManager::getInstance()->create(FormLoadEntityRepository::class);
        $bindArguments = ['customerId' => ['value' => 1]];
        $result        = $sut->fetchTypeAndMethod(
            CustomerRepositoryInterface::class . '::getById',
            $bindArguments,
            CustomerInterface::class
        );
        $this->assertInstanceOf(CustomerInterface::class, $result->getValue());
        $fields = $result->getFieldDefinitions();

        // system attribute
        $this->assertContainsField('firstname', $fields);

        // extension attribute
        $this->assertContainsField('is_subscribed', $fields);

        // custom attribute
        $this->assertContainsField('password_hash', $fields);
    }

    /**
     * @magentoDataFixture Magento/Cms/_files/blocks.php
     */
    public function testReturnsCmsBlockAttributes(): void
    {
        /** @var FormLoadEntityRepository $sut */
        $sut     = ObjectManager::getInstance()->create(FormLoadEntityRepository::class);
        $bindArguments = ['blockId' => ['value' => $this->getCmsBlockFixtureBlockId()]];
        $result        = $sut->fetchTypeAndMethod(
            BlockRepositoryInterface::class . '::getById',
            $bindArguments,
            BlockInterface::class
        );
        $this->assertInstanceOf(BlockInterface::class, $result->getValue());
        $fields = $result->getFieldDefinitions();

        $this->assertContainsField(BlockInterface::IDENTIFIER, $fields);
        $this->assertContainsField(BlockInterface::TITLE, $fields);
    }

    /**
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     */
    public function testReturnsProductAttributes(): void
    {
        /** @var FormLoadEntityRepository $sut */
        $sut     = ObjectManager::getInstance()->create(FormLoadEntityRepository::class);
        $bindArguments = ['sku' => ['value' => 'simple']];
        $result        = $sut->fetchTypeAndMethod(
            ProductRepositoryInterface::class . '::get',
            $bindArguments,
            ProductInterface::class
        );
        $this->assertInstanceOf(ProductInterface::class, $result->getValue());
        $fields = $result->getFieldDefinitions();

        // system attribute
        $this->assertContainsField('sku', $fields);
        $this->assertContainsField('name', $fields);

        // extension attribute
        $this->assertContainsField('stock_item', $fields);

        // custom attribute
        $this->assertContainsField('image', $fields);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testReturnsOrderAttributes(): void
    {
        /** @var FormLoadEntityRepository $sut */
        $sut     = ObjectManager::getInstance()->create(FormLoadEntityRepository::class);
        $bindArguments = [
            'value' => ['value' => '100000001'],
            'field' => ['value' => 'increment_id']
        ];
        $result        = $sut->fetchTypeAndMethod(
            OrderResourceModel::class . '::load',
            $bindArguments,
            OrderModel::class
        );
        $this->assertInstanceOf(OrderModel::class, $result->getValue());

        $fields = $result->getFieldDefinitions();
        $this->assertContainsField('id', $fields);
        $this->assertContainsField('customer', $fields);
    }

    /**
     * @magentoDataFixture Magento/Store/_files/store.php
     */
    public function testReturnsStoreAttributes(): void
    {
        /** @var FormLoadEntityRepository $sut */
        $sut     = ObjectManager::getInstance()->create(FormLoadEntityRepository::class);
        $bindArguments = [
            'code' => ['value' => 'test'],
        ];
        $result        = $sut->fetchTypeAndMethod(
            StoreRepositoryInterface::class . '::get',
            $bindArguments,
            StoreInterface::class
        );
        $this->assertInstanceOf(StoreInterface::class, $result->getValue());

        $fields = $result->getFieldDefinitions();
        $this->assertContainsField('id', $fields);
        $this->assertContainsField('name', $fields);
        $this->assertContainsField('store_group_id', $fields);
    }
}
