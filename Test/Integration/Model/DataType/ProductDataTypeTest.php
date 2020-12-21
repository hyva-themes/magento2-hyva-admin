<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Integration\Model\DataType;

use Hyva\Admin\Model\DataType\ProductDataType;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\ObjectManager;
use PHPUnit\Framework\TestCase;

class ProductDataTypeTest extends TestCase
{
    public function testMatchesProducts(): void
    {
        $product = ObjectManager::getInstance()->create(ProductInterface::class);
        $sut = new ProductDataType();

        $this->assertSame('magento_product', $sut->valueToTypeCode($product));
        $this->assertNull($sut->valueToTypeCode(new \stdClass()));
    }

    public function testReturnsNullIfTypeDoesNotMatch(): void
    {
        $this->assertNull((new ProductDataType())->toString([]));
        $this->assertNull((new ProductDataType())->toHtmlRecursive([]));
    }

    public function testToString(): void
    {
        /** @var ProductInterface $product */
        $product = ObjectManager::getInstance()->create(ProductInterface::class);
        $product->setName('Test');
        $product->setSku('1234xyz');
        $product->setId(23);

        $this->assertSame('Test', (new ProductDataType())->toString($product));
    }

    public function testToStringRecursive(): void
    {
        /** @var ProductInterface $product */
        $product = ObjectManager::getInstance()->create(ProductInterface::class);
        $product->setName('Test');
        $product->setSku('1234xyz');
        $product->setId(23);

        $this->assertSame('Test [SKU 1234xyz]', (new ProductDataType())->toHtmlRecursive($product));
    }

    public function testEmptyProduct(): void
    {
        /** @var ProductInterface $product */
        $product = ObjectManager::getInstance()->create(ProductInterface::class);

        $this->assertSame('(not initialized)', (new ProductDataType())->toString($product));
        $this->assertSame('(not initialized) [SKU ?]', (new ProductDataType())->toHtmlRecursive($product));
    }
}
