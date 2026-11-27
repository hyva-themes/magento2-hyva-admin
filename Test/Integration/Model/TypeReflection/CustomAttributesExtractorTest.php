<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Integration\Model\TypeReflection;

use Hyva\Admin\Model\TypeReflection\CustomAttributesExtractor;
use Magento\Catalog\Model\Product;
use Magento\Cms\Model\Block;
use Magento\Customer\Model\Address;
use Magento\Customer\Model\Customer;
use Magento\Sales\Model\Order;
use Magento\Store\Model\Store;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class CustomAttributesExtractorTest extends TestCase
{
    /**
     * @dataProvider nonEavEntityClassProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('nonEavEntityClassProvider')]
    public function testIsFalseForNonEavEntity(string $class): void
    {
        $sut    = ObjectManager::getInstance()->create(CustomAttributesExtractor::class);
        $entity = ObjectManager::getInstance()->create($class);
        $this->assertFalse($sut->isEavEntity($entity));
    }
    /**
     * @dataProvider eavEntityClassProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('eavEntityClassProvider')]
    public function testIsTrueForEavEntity(string $class): void
    {
        $sut    = ObjectManager::getInstance()->create(CustomAttributesExtractor::class);
        $entity = ObjectManager::getInstance()->create($class);
        $this->assertTrue($sut->isEavEntity($entity));
    }

    public static function nonEavEntityClassProvider(): array
    {
        return [
            [Block::class],
            [Order::class],
            [Store::class],
        ];
    }

    public static function eavEntityClassProvider(): array
    {
        return [
            [Product::class],
            [Customer::class],
            [Address::class],
        ];
    }
}
