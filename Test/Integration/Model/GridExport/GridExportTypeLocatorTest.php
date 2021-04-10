<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Integration\Model\GridExport;

use function array_filter as filter;

use Hyva\Admin\Model\GridExport\GridExportTypeLocator;
use Hyva\Admin\Model\GridExport\Type\Csv;
use Hyva\Admin\Model\GridSourceType\ArrayProviderGridSourceType;
use Hyva\Admin\Model\HyvaGridSourceInterface;
use Hyva\Admin\Test\Integration\TestingGridDataProvider;
use Hyva\Admin\ViewModel\HyvaGrid\GridExportInterface;
use Hyva\Admin\ViewModel\HyvaGrid\Navigation;
use Hyva\Admin\ViewModel\HyvaGrid\NavigationInterface;
use Hyva\Admin\ViewModel\HyvaGridInterface;
use Magento\Framework\App\RequestInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GridExportTypeLocatorTest extends TestCase
{
    const TEST_GRID = 'test-grid';

    private function stubParams(MockObject $stubRequest, array $params): void
    {
        $stubRequest->method('getParam')->willReturnMap([[self::TEST_GRID, null, $params]]);
    }

    private function createArrayGridSource(array $gridData): HyvaGridSourceInterface
    {
        $gridSourceConfig = ['arrayProvider' => TestingGridDataProvider::withArray($gridData)];
        $sourceTypeArgs   = ['gridName' => self::TEST_GRID, 'sourceConfiguration' => $gridSourceConfig];
        $arraySourceType  = ObjectManager::getInstance()->create(ArrayProviderGridSourceType::class, $sourceTypeArgs);
        $gridSourceArgs   = ['gridSourceType' => $arraySourceType, 'gridName' => self::TEST_GRID];

        return ObjectManager::getInstance()->create(HyvaGridSourceInterface::class, $gridSourceArgs);
    }

    private function createNavigation(
        array $gridData,
        array $navigationConfig,
        RequestInterface $request = null
    ): Navigation {
        $hyvaGridSource = $this->createArrayGridSource($gridData);
        return ObjectManager::getInstance()->create(NavigationInterface::class, filter([
            'gridName'          => self::TEST_GRID,
            'gridSource'        => $hyvaGridSource,
            'navigationConfig'  => $navigationConfig,
            'columnDefinitions' => $hyvaGridSource->extractColumnDefinitions([], [], false),
            'request'           => $request,
        ], function ($v): bool {
            return isset($v);
        }));
    }

    public function testThroesExceptionIfExportIsNotConfiguredOnGrid(): void
    {
        $stubNavigation = $this->createMock(NavigationInterface::class);
        $stubGrid       = $this->createMock(HyvaGridInterface::class);
        $stubGrid->method('getNavigation')->willReturn($stubNavigation);
        $stubGrid->method('getGridName')->willReturn('stub-grid');

        /** @var GridExportTypeLocator $sut */
        $sut = ObjectManager::getInstance()->create(GridExportTypeLocator::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Export type "foo" not configured for Hyvä grid "stub-grid"');

        $sut->getExportType($stubGrid, 'foo');
    }

    public function testThrowsExceptionIfGridTypeIsUnknownAndNoClassIsSet(): void
    {
        $stubNavigation = $this->createMock(NavigationInterface::class);
        $stubNavigation->method('getExports')->willReturn(['foo' => $this->createMock(GridExportInterface::class)]);
        $stubGrid = $this->createMock(HyvaGridInterface::class);
        $stubGrid->method('getNavigation')->willReturn($stubNavigation);
        $stubGrid->method('getGridName')->willReturn('stub-grid');

        /** @var GridExportTypeLocator $sut */
        $sut = ObjectManager::getInstance()->create(GridExportTypeLocator::class);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Hyva_Admin Grid Export type "foo" is unknown');

        $sut->getExportType($stubGrid, 'foo');
    }

    public function testReturnsCustomExportClassIfConfigured()
    {
        $stubExport = $this->createMock(GridExportInterface::class);
        $stubExport->method('getClass')->willReturn(Csv::class);
        $stubNavigation = $this->createMock(NavigationInterface::class);
        $stubNavigation->method('getExports')->willReturn(['foo' => $stubExport]);
        $stubGrid = $this->createMock(HyvaGridInterface::class);
        $stubGrid->method('getNavigation')->willReturn($stubNavigation);
        $stubGrid->method('getGridName')->willReturn('stub-grid');

        /** @var GridExportTypeLocator $sut */
        $sut = ObjectManager::getInstance()->create(GridExportTypeLocator::class);

        $exportType = $sut->getExportType($stubGrid, 'foo');
        $this->assertInstanceOf(Csv::class, $exportType);
    }
}
