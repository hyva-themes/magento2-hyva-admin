<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Functional\Model\Config;

use Hyva\Admin\Model\Config\GridConfigReader;
use Hyva\Admin\Model\Config\HyvaGridConfigReaderInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea adminhtml
 */
class GridConfigReaderTest extends TestCase
{
    public function testIsKnownToObjectManager(): void
    {
        $reader = ObjectManager::getInstance()->create(HyvaGridConfigReaderInterface::class);
        $this->assertInstanceOf(GridConfigReader::class, $reader);
    }
}
