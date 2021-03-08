<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Integration\Model\TypeReflection;

use Hyva\Admin\Model\TypeReflection\TypeMethod;
use Hyva\Admin\Test\Integration\TestingGridDataProvider;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product as ProductResourceModel;
use Magento\Cms\Api\Data\PageInterface;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Cms\Model\Page;
use Magento\Cms\Model\ResourceModel\Page as PageResourceModel;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Customer as CustomerModel;
use Magento\Customer\Model\ResourceModel\Customer as CustomerResourceModel;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class TypeMethodTest extends TestCase
{
    private function createSut(): TypeMethod
    {
        /** @var TypeMethod $sut */
        $sut = ObjectManager::getInstance()->create(TypeMethod::class);
        return $sut;
    }

    public function throwNoSuchEntityException(): void
    {
        throw new NoSuchEntityException();
    }

    public function returnEmptyArray(): array
    {
        return [];
    }

    public function returnZero(): int
    {
        return 0;
    }

    public function returnOne(): int
    {
        return 1;
    }

    public function expectsSecondArgToBeZero($foo, $bar): bool
    {
        if ($bar !== 0) {
            $this->fail('Expected $bar to be zero, got "' . $bar . '"');
        }
        return true;
    }

    public function expectsFirstArgToBeNull($foo, $bar): bool
    {
        if (!is_null($foo)) {
            $this->fail('Expected $foo to be null, got "' . $foo . '"');
        }
        return true;
    }

    public function expectsFirstArgToBeDefault($foo = 'default', $bar = 0): bool
    {
        if ($foo !== 'default') {
            $this->fail('Expected $foo to be "default", got "' . $foo . '"');
        }
        return true;
    }

    // --------------------------- tests -------------------------------

    public function testNoSuchEntityExceptionReturnsNull(): void
    {
        $this->assertNull($this->createSut()->invoke(__CLASS__, 'throwNoSuchEntityException', []));
    }

    public function testModelLoadNonExistentIdReturnsNull(): void
    {
        $this->assertNull($this->createSut()->invoke(Product::class, 'load', [
            'modelId' => [
                'method' => __CLASS__ . '::returnZero',
            ],
        ]));
    }

    public function testEmptyArrayResultReturnsNull(): void
    {
        $this->assertNull($this->createSut()->invoke(__CLASS__, 'returnEmptyArray', []));
    }

    public function testPassesArgumentsInPositionBasedOnParameterName(): void
    {
        $this->assertTrue($this->createSut()->invoke(__CLASS__, 'expectsSecondArgToBeZero', [
            'bar' => [
                'method' => __CLASS__ . '::returnZero',
            ],
        ]));
    }

    public function testPassesNullForNotConfiguredArgumentsBetweenConfiguredValues(): void
    {
        $this->assertTrue($this->createSut()->invoke(__CLASS__, 'expectsFirstArgToBeNull', [
            'bar' => [
                'method' => __CLASS__ . '::returnZero',
            ],
        ]));
    }

    public function testPassesDefaultForNotConfiguredArgumentsBetweenConfiguredValues(): void
    {
        $this->assertTrue($this->createSut()->invoke(__CLASS__, 'expectsFirstArgToBeDefault', [
            'bar' => [
                'method' => __CLASS__ . '::returnZero',
            ],
        ]));
    }

    /**
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     */
    public function testCanLoadProductViaRepository(): void
    {
        $result = $this->createSut()->invoke(
            ProductRepositoryInterface::class,
            'getById',
            ['productId' => ['method' => __CLASS__ . '::returnOne']]
        );
        $this->assertInstanceOf(ProductInterface::class, $result);
        $this->assertEquals(1, $result->getId());
    }

    /**
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     */
    public function testCanLoadProductViaDeprecatedLoadOnModels(): void
    {
        $sut    = $this->createSut();
        $result = $sut->invoke(Product::class, 'load', ['modelId' => ['method' => __CLASS__ . '::returnOne']]);
        $this->assertInstanceOf(Product::class, $result);
        $this->assertEquals(1, $result->getId());
    }

    public function testCanReturnArrayFromArrayProvider()
    {
        $data          = [['foo' => 'bar'], ['foo' => 'baz'], ['foo' => 'qux']];
        $providerClass = TestingGridDataProvider::withArray($data);
        $sut           = $this->createSut();
        $result        = $sut->invoke($providerClass, 'getHyvaGridData', []);
        $this->assertEquals($data, $result);
    }

    /**
     * @magentoDataFixture Magento/Cms/_files/pages.php
     */
    public function testCanLoadCmsPageViaRepository(): void
    {
        $result = $this->createSut()->invoke(
            PageRepositoryInterface::class,
            'getById',
            ['pageId' => ['method' => __CLASS__ . '::returnOne']]
        );
        $this->assertInstanceOf(PageInterface::class, $result);
        $this->assertEquals(1, $result->getId());
    }

    /**
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     */
    public function testRepositoryLoadMethodAutomaticallyCreatesModelArgument(): void
    {
        $result = $this->createSut()->invoke(
            ProductResourceModel::class,
            'load',
            ['entityId' => ['method' => __CLASS__ . '::returnOne']]
        );
        $this->assertInstanceOf(Product::class, $result);
        $this->assertEquals(1, $result->getId());
    }

    /**
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     */
    public function testRepositoryLoadReturnsNullIfModelLoadFails(): void
    {
        $result = $this->createSut()->invoke(
            ProductResourceModel::class,
            'load',
            ['entityId' => ['method' => __CLASS__ . '::returnZero']]
        );
        $this->assertNull($result);
    }

    /**
     * @magentoDataFixture Magento/Cms/_files/pages.php
     */
    public function testCanLoadFlatTableOrmModelViaResourceModel(): void
    {
        $result = $this->createSut()->invoke(
            PageResourceModel::class,
            'load',
            ['value' => ['method' => __CLASS__ . '::returnOne']]
        );
        $this->assertInstanceOf(Page::class, $result);
        $this->assertEquals(1, $result->getId());
    }

    /**
     * @magentoDataFixture Magento/Cms/_files/pages.php
     */
    public function testCanLoadFlatTableOrmModelViaResourceModelByOtherField(): void
    {
        $result = $this->createSut()->invoke(
            PageResourceModel::class,
            'load',
            [
                'value' => ['value' => 'page100'],
                'field' => ['value' => 'identifier'],
            ]
        );
        $this->assertInstanceOf(Page::class, $result);
        $this->assertSame('Cms Page 100', $result->getTitle());
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/customer.php
     */
    public function testCanLoadCustomerOrmModelViaResourceModel(): void
    {
        $result = $this->createSut()->invoke(
            CustomerResourceModel::class,
            'load',
            ['entityId' => ['method' => __CLASS__ . '::returnOne']]
        );
        $this->assertInstanceOf(CustomerModel::class, $result);
        $this->assertEquals(1, $result->getId());
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/customer.php
     */
    public function testCanLoadCustomerOrmModelViaDeprecatedLoadMethod(): void
    {
        $result = $this->createSut()->invoke(
            CustomerModel::class,
            'load',
            ['modelId' => ['method' => __CLASS__ . '::returnOne']]
        );
        $this->assertInstanceOf(CustomerModel::class, $result);
        $this->assertEquals(1, $result->getId());
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/customer.php
     */
    public function testCanLoadCustomerDataModelViaRepository(): void
    {
        $result = $this->createSut()->invoke(
            CustomerRepositoryInterface::class,
            'getById',
            ['customerId' => ['method' => __CLASS__ . '::returnOne']]
        );
        $this->assertInstanceOf(CustomerInterface::class, $result);
        $this->assertEquals(1, $result->getId());
    }
}
