<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Integration\ViewModel;

use Hyva\Admin\Model\HyvaGridDefinitionInterfaceFactory;
use Hyva\Admin\Test\Integration\TestingGridDataProvider;
use Hyva\Admin\Test\Integration\TestingGridDefinition;
use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterface;
use Hyva\Admin\ViewModel\HyvaGridInterface;
use Hyva\Admin\ViewModel\HyvaGridViewModel;
use Magento\Framework\App\Area;
use Magento\Framework\App\State as AppState;
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

    public function testRendersIncludedColumnsForEmptyArrayProvider(): void
    {
        $includedColumns    = [
            'log_id'     => $this->createColumnDefinition('log_id'),
            'admin_name' => $this->createColumnDefinition('admin_name'),
        ];
        $testGridDefinition = [
            'source'  => [
                'arrayProvider' => TestingGridDataProvider::withArray([]),
            ],
            'columns' => [
                'include' => $includedColumns,
            ],
        ];

        $grid = ObjectManager::getInstance()->create(HyvaGridViewModel::class, [
            'gridName'              => 'test-name',
            'gridDefinitionFactory' => $this->makeFactoryForGridDefinition($testGridDefinition),
        ]);

        $this->assertSame(['log_id', 'admin_name'], array_keys($grid->getColumnDefinitions()));
        $this->assertSame([], $grid->getRows());
    }

    public function testThrowsForUnknownIncludedColumnIfArrayProviderHasData(): void
    {
        $testGridDefinition = [
            'source'  => [
                'arrayProvider' => TestingGridDataProvider::withArray([
                    ['log_id' => 1, 'admin_name' => 'bob'],
                ]),
            ],
            'columns' => [
                'include' => [
                    'log_id'    => $this->createColumnDefinition('log_id'),
                    'admin_nme' => $this->createColumnDefinition('admin_nme'),
                ],
            ],
        ];

        $grid = ObjectManager::getInstance()->create(HyvaGridViewModel::class, [
            'gridName'              => 'test-name',
            'gridDefinitionFactory' => $this->makeFactoryForGridDefinition($testGridDefinition),
        ]);

        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage('Column(s) not found on source: admin_nme');
        $grid->getColumnDefinitions();
    }

    public function testRendersExportLinksOnlyInTheAdminArea(): void
    {
        $testGridDefinition = [
            'source'     => [
                'arrayProvider' => TestingGridDataProvider::withArray([['id' => 1]]),
            ],
            'navigation' => [
                'exports' => [
                    ['type' => 'csv', 'label' => 'Export as CSV'],
                ],
            ],
        ];
        $grid               = ObjectManager::getInstance()->create(HyvaGridViewModel::class, [
            'gridName'              => 'test-name',
            'gridDefinitionFactory' => $this->makeFactoryForGridDefinition($testGridDefinition),
        ]);

        $this->assertStringContainsString('Export as CSV', $grid->getExportsHtml());

        $storefrontExportsHtml = ObjectManager::getInstance()->get(AppState::class)->emulateAreaCode(
            Area::AREA_FRONTEND,
            function () use ($grid): string {
                return $grid->getExportsHtml();
            }
        );
        $this->assertSame('', $storefrontExportsHtml);
        $this->assertNotNull($grid->getExport('csv'));
    }

    private function createColumnDefinition(string $key): ColumnDefinitionInterface
    {
        return ObjectManager::getInstance()->create(ColumnDefinitionInterface::class, ['key' => $key]);
    }
}
