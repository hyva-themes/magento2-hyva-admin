<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Integration\Model;

use Hyva\Admin\Model\HyvaGridDefinition;
use Hyva\Admin\Model\HyvaGridDefinitionInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea adminhtml
 */
class HyvaGridDefinitionTest extends TestCase
{
    public function testIsKnownToObjectManager(): void
    {
        $gridDefinition = ObjectManager::getInstance()->create(
            HyvaGridDefinitionInterface::class,
            ['gridName' => 'test']
        );
        $this->assertInstanceOf(HyvaGridDefinition::class, $gridDefinition);
    }
}
