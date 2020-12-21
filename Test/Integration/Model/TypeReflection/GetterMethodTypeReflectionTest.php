<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Integration\Model\TypeReflection;

use Hyva\Admin\Model\TypeReflection\GetterMethodsExtractor;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\ObjectManager;
use PHPUnit\Framework\TestCase;

class GetterMethodTypeReflectionTest extends TestCase
{
    public function testExcludesAbstractModelGetterMethods(): void
    {
        /** @var GetterMethodsExtractor $sut */
        $sut = ObjectManager::getInstance()->create(GetterMethodsExtractor::class);
        $this->assertNotContains('resource', $sut->fromTypeAsFieldNames(Product::class));
    }
}
