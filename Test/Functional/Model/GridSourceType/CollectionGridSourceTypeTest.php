<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Functional\Model\GridSourceType;

use Hyva\Admin\Model\GridSourceType\CollectionGridSourceType;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea adminhtml
 */
class CollectionGridSourceTypeTest extends TestCase
{
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

        // extension attribute based keys
        $this->assertContains('website_ids', $keys);
        $this->assertContains('bundle_product_options', $keys);
        $this->assertContains('configurable_product_options', $keys);
        $this->assertContains('configurable_product_links', $keys);

        // custom attribute based keys (a.k.a. EAV attributes)
        $this->assertContains('activity', $keys);
        $this->assertContains('gender', $keys);
    }
}
