<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Functional\ViewModel;

use Hyva\Admin\Model\HyvaGridDefinitionInterfaceFactory;
use Hyva\Admin\ViewModel\HyvaGridInterface;
use Hyva\Admin\ViewModel\HyvaGridViewModel;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea adminhtml
 */
class HyvaGridViewModelTest extends TestCase
{
    private function makeFactoryForGridDefinition(array $testingGridDefinition): HyvaGridDefinitionInterfaceFactory
    {
        return TestingGridDefinition::makeFactory($testingGridDefinition);
    }

    public function testIsKnownToObjectManager(): void
    {
        $grid = ObjectManager::getInstance()->create(HyvaGridInterface::class, ['gridName' => 'test-name']);
        $this->assertInstanceOf(HyvaGridViewModel::class, $grid);
    }

    public function testReturnsColumnDefinitions(): void
    {
        $testGridDefinition = [
            'source' => ['@type' => 'repository']];

        $grid    = ObjectManager::getInstance()->create(HyvaGridViewModel::class, [
            'gridName'              => 'test-name',
            'gridDefinitionFactory' => $this->makeFactoryForGridDefinition($testGridDefinition),
        ]);
        $columns = $grid->getColumnDefinitions();
        $this->markTestIncomplete('work in progress');
    }
}
