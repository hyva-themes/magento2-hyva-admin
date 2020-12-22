<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Integration\Model\DataType;

use Hyva\Admin\Model\DataType\DataTypeFacade;
use Magento\Catalog\Model\Product\Type\AbstractType as AbstractProductType;
use Magento\Catalog\Model\Product\Type\Simple;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea adminhtml
 */
class DataTypeFacadeTest extends TestCase
{
    public function testKnowsMagentoProductType(): void
    {
        /** @var DataTypeFacade $sut */
        $sut = ObjectManager::getInstance()->create(DataTypeFacade::class);
        $this->assertSame('magento_product_type', $sut->typeToTypeCode(Simple::class));
        $this->assertSame('magento_product_type', $sut->typeToTypeCode(AbstractProductType::class));
    }
}
