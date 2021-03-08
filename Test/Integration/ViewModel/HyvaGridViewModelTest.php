<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Integration\ViewModel;

use Hyva\Admin\Model\HyvaGridDefinitionInterfaceFactory;
use Hyva\Admin\Test\Integration\TestingGridDataProvider;
use Hyva\Admin\Test\Integration\TestingGridDefinition;
use Hyva\Admin\ViewModel\HyvaGridInterface;
use Hyva\Admin\ViewModel\HyvaGridViewModel;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea adminhtml
 */
class HyvaGridViewModelTest extends TestCase
{
    private function makeFactoryForGridDefinition(array $gridDefinition): HyvaGridDefinitionInterfaceFactory
    {
        return TestingGridDefinition::makeFactory('test-grid', $gridDefinition);
    }

    public function testIsKnownToObjectManager(): void
    {
        $grid = ObjectManager::getInstance()->create(HyvaGridInterface::class, ['gridName' => 'test-name']);
        $this->assertInstanceOf(HyvaGridViewModel::class, $grid);
    }

    public function testReturnsColumnDefinitions(): void
    {
        $testGridDefinition = [
            'source' => [
                'arrayProvider' => TestingGridDataProvider::withArray([
                    ['foo' => 'This is a big foo', 'bar' => 'this is a tiny bar', 'baz' => 123],
                    ['foo' => 'Another foo', 'bar' => 'Another bar', 'baz' => 2222],
                ]),
            ],
        ];

        $grid    = ObjectManager::getInstance()->create(HyvaGridViewModel::class, [
            'gridName'              => 'test-name',
            'gridDefinitionFactory' => $this->makeFactoryForGridDefinition($testGridDefinition),
        ]);
        $columns = $grid->getColumnDefinitions();
        $this->assertCount(3, $columns);
    }

    public function testDoesNotChokeOnEmptyColumnList(): void
    {
        $testGridDefinition = [
            'source' => [
                'arrayProvider' => TestingGridDataProvider::withArray([]),
            ],
        ];

        $grid    = ObjectManager::getInstance()->create(HyvaGridViewModel::class, [
            'gridName'              => 'test-name',
            'gridDefinitionFactory' => $this->makeFactoryForGridDefinition($testGridDefinition),
        ]);
        $this->assertNull($grid->getMassActionIdColumn());
    }
}
