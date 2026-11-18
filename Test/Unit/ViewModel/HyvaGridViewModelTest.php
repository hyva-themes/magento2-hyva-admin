<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Unit\ViewModel;

use Hyva\Admin\Model\HyvaGridDefinitionInterface;
use Hyva\Admin\Model\HyvaGridDefinitionInterfaceFactory;
use Hyva\Admin\Model\HyvaGridSourceFactory;
use Hyva\Admin\Model\HyvaGridSourceInterface;
use Hyva\Admin\Model\HyvaGridEventDispatcher;
use Hyva\Admin\ViewModel\HyvaGrid\GridActionInterfaceFactory;
use Hyva\Admin\ViewModel\HyvaGrid\CellInterface;
use Hyva\Admin\ViewModel\HyvaGrid\CellInterfaceFactory;
use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterface;
use Hyva\Admin\ViewModel\HyvaGrid\EntityDefinitionInterface;
use Hyva\Admin\ViewModel\HyvaGrid\EntityDefinitionInterfaceFactory;
use Hyva\Admin\ViewModel\HyvaGrid\MassActionInterfaceFactory;
use Hyva\Admin\ViewModel\HyvaGrid\NavigationInterface;
use Hyva\Admin\ViewModel\HyvaGrid\NavigationInterfaceFactory;
use Hyva\Admin\ViewModel\HyvaGrid\RowInterface;
use Hyva\Admin\ViewModel\HyvaGrid\RowInterfaceFactory;
use Hyva\Admin\ViewModel\HyvaGridInterface;
use Hyva\Admin\ViewModel\HyvaGridViewModel;
use Hyva\Admin\ViewModel\HyvaGrid\GridJsEventFactory;
use Magento\Framework\View\LayoutInterface;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

class HyvaGridViewModelTest extends TestCase
{
    /**
     * @param string $key
     * @return ColumnDefinitionInterface|Stub
     */
    private function createStubColumn(string $key, bool $isVisible = true): ColumnDefinitionInterface
    {
        $column = $this->createStub(ColumnDefinitionInterface::class);
        $column->method('getKey')->willReturn($key);
        $column->method('isVisible')->willReturn($isVisible);
        return $column;
    }

    /**
     * @return HyvaGridSourceFactory|Stub
     */
    private function createStubGridSourceFactory(): HyvaGridSourceFactory
    {
        $stubGridSource        = $this->createStub(HyvaGridSourceInterface::class);
        $stubGridSourceFactory = $this->createStub(HyvaGridSourceFactory::class);
        $stubGridSourceFactory->method('createFor')->willReturn($stubGridSource);

        return $stubGridSourceFactory;
    }

    private function createStubGridDefinitionFactory(): HyvaGridDefinitionInterfaceFactory
    {
        $stubGridDefinition        = $this->createStub(HyvaGridDefinitionInterface::class);
        $stubGridDefinitionFactory = $this->createStub(HyvaGridDefinitionInterfaceFactory::class);
        $stubGridDefinitionFactory->method('create')->willReturn($stubGridDefinition);

        return $stubGridDefinitionFactory;
    }

    /**
     * @return Stub|RowInterfaceFactory
     */
    private function createStubRowFactory(): Stub
    {
        return $this->createStub(RowInterfaceFactory::class);
    }

    /**
     * @return Stub|CellInterfaceFactory
     */
    private function createStubCellFactory(): Stub
    {
        return $this->createStub(CellInterfaceFactory::class);
    }

    /**
     * @return Stub|LayoutInterface
     */
    private function createStubLayout(): Stub
    {
        return $this->createStub(LayoutInterface::class);
    }

    /**
     * @return Stub|NavigationInterfaceFactory
     */
    private function createStubNavigationFactory(): Stub
    {
        $dummyNavigation = $this->createStub(NavigationInterface::class);
        $stubFactory     = $this->createStub(NavigationInterfaceFactory::class);
        $stubFactory->method('create')->willReturn($dummyNavigation);

        return $stubFactory;
    }

    /**
     * @return Stub|EntityDefinitionInterfaceFactory
     */
    private function createStubEntityDefinitionFactory(): Stub
    {
        return $this->createStub(EntityDefinitionInterfaceFactory::class);
    }

    /**
     * @return Stub|GridActionInterfaceFactory
     */
    private function createStubActionFactory(): Stub
    {
        return $this->createStub(GridActionInterfaceFactory::class);
    }

    /**
     * @return Stub|MassActionInterfaceFactory
     */
    private function createStubMassActionFactory(): Stub
    {
        return $this->createStub(MassActionInterfaceFactory::class);
    }

    /**
     * @return Stub|GridJsEventFactory
     */
    private function createStubGridJsEventsFactory(): Stub
    {
        return $this->createStub(GridJsEventFactory::class);
    }

    /**
     * @return Stub|HyvaGridEventDispatcher
     */
    private function createStubHyvaEventDispatcher(): Stub
    {
        $dispatcher = $this->createStub(HyvaGridEventDispatcher::class);
        $dispatcher->method('dispatch')->willReturnArgument(2);
        return $dispatcher;
    }

    private function setColumnDefinitionOnFactory(
        HyvaGridSourceFactory $stubGridSourceFactory,
        array $columnDefinitions
    ): void {
        /** @var Stub $stubGridSource */
        $stubGridSource = $stubGridSourceFactory->createFor($this->createStub(HyvaGridDefinitionInterface::class));
        $stubGridSource->method('extractColumnDefinitions')->willReturn($columnDefinitions);
    }

    /**
     * @param HyvaGridDefinitionInterfaceFactory $stubGridDefinitionFactory
     * @param string[] $excludedColumnKeys
     */
    private function setExcludedColumnKeys(
        HyvaGridDefinitionInterfaceFactory $stubGridDefinitionFactory,
        array $excludedColumnKeys
    ): void {
        /** @var Stub $stubGridDefinition */
        $stubGridDefinition = $stubGridDefinitionFactory->create();
        $stubGridDefinition->method('getExcludedColumnKeys')->willReturn($excludedColumnKeys);
    }

    public function testImplementsHyvaGridInterface(): void
    {
        $dummyGridSourceFactory       = $this->createStubGridSourceFactory();
        $dummyGridDefinitionFactory   = $this->createStubGridDefinitionFactory();
        $dummyGridRowFactory          = $this->createStubRowFactory();
        $dummyGridCellFactory         = $this->createStubCellFactory();
        $dummyNavigationFactory       = $this->createStubNavigationFactory();
        $dummyEntityDefinitionFactory = $this->createStubEntityDefinitionFactory();
        $dummyActionFactory           = $this->createStubActionFactory();
        $dummyMassActionFactory       = $this->createStubMassActionFactory();
        $dummyGridJsEventsFactory     = $this->createStubGridJsEventsFactory();
        $dummyHyvaEventDispatcher     = $this->createStubHyvaEventDispatcher();
        $dummyLayout                  = $this->createStubLayout();
        $sut                          = new HyvaGridViewModel(
            'dummy-grid-name',
            $dummyGridDefinitionFactory,
            $dummyGridSourceFactory,
            $dummyGridRowFactory,
            $dummyGridCellFactory,
            $dummyNavigationFactory,
            $dummyEntityDefinitionFactory,
            $dummyActionFactory,
            $dummyMassActionFactory,
            $dummyGridJsEventsFactory,
            $dummyHyvaEventDispatcher,
            $dummyLayout
        );

        $this->assertInstanceOf(HyvaGridInterface::class, $sut);
    }

    public function testReturnsNumberOfColumnsFromGridSource(): void
    {
        $columnDefinitions = [
            $this->createStubColumn('foo'),
            $this->createStubColumn('bar'),
            $this->createStubColumn('baz'),
        ];

        $stubGridSourceFactory      = $this->createStubGridSourceFactory();
        $dummyGridDefinitionFactory = $this->createStubGridDefinitionFactory();
        $this->setColumnDefinitionOnFactory($stubGridSourceFactory, $columnDefinitions);
        $dummyGridRowFactory          = $this->createStubRowFactory();
        $dummyGridCellFactory         = $this->createStubCellFactory();
        $dummyNavigationFactory       = $this->createStubNavigationFactory();
        $dummyEntityDefinitionFactory = $this->createStubEntityDefinitionFactory();
        $dummyActionFactory           = $this->createStubActionFactory();
        $dummyMassActionFactory       = $this->createStubMassActionFactory();
        $dummyGridJsEventsFactory     = $this->createStubGridJsEventsFactory();
        $dummyHyvaEventDispatcher     = $this->createStubHyvaEventDispatcher();
        $dummyLayout                  = $this->createStubLayout();

        $sut = new HyvaGridViewModel(
            'test-grid-name',
            $dummyGridDefinitionFactory,
            $stubGridSourceFactory,
            $dummyGridRowFactory,
            $dummyGridCellFactory,
            $dummyNavigationFactory,
            $dummyEntityDefinitionFactory,
            $dummyActionFactory,
            $dummyMassActionFactory,
            $dummyGridJsEventsFactory,
            $dummyHyvaEventDispatcher,
            $dummyLayout
        );

        $this->assertSame(3, $sut->getColumnCount());
    }

    public function testReturnsArrayOfColumnDefinitions(): void
    {
        $columnFoo         = $this->createStubColumn('foo');
        $columnBar         = $this->createStubColumn('bar');
        $columnDefinitions = [$columnFoo, $columnBar];

        $stubGridSourceFactory      = $this->createStubGridSourceFactory();
        $dummyGridDefinitionFactory = $this->createStubGridDefinitionFactory();
        $this->setColumnDefinitionOnFactory($stubGridSourceFactory, $columnDefinitions);
        $dummyGridRowFactory          = $this->createStubRowFactory();
        $dummyGridCellFactory         = $this->createStubCellFactory();
        $dummyNavigationFactory       = $this->createStubNavigationFactory();
        $dummyEntityDefinitionFactory = $this->createStubEntityDefinitionFactory();
        $dummyActionFactory           = $this->createStubActionFactory();
        $dummyMassActionFactory       = $this->createStubMassActionFactory();
        $dummyGridJsEventsFactory     = $this->createStubGridJsEventsFactory();
        $dummyHyvaEventDispatcher     = $this->createStubHyvaEventDispatcher();
        $dummyLayout                  = $this->createStubLayout();

        $sut = new HyvaGridViewModel(
            'dummy-grid-name',
            $dummyGridDefinitionFactory,
            $stubGridSourceFactory,
            $dummyGridRowFactory,
            $dummyGridCellFactory,
            $dummyNavigationFactory,
            $dummyEntityDefinitionFactory,
            $dummyActionFactory,
            $dummyMassActionFactory,
            $dummyGridJsEventsFactory,
            $dummyHyvaEventDispatcher,
            $dummyLayout
        );

        $this->assertSame(['foo' => $columnFoo, 'bar' => $columnBar], $sut->getColumnDefinitions());
    }

    public function testRemovesExcludedColumns(): void
    {
        $columnFoo = $this->createStubColumn('foo');
        $columnBar = $this->createStubColumn('bar', false);
        $columnBaz = $this->createStubColumn('baz', false);
        $columnQux = $this->createStubColumn('qux');

        $stubGridSourceFactory     = $this->createStubGridSourceFactory();
        $stubGridDefinitionFactory = $this->createStubGridDefinitionFactory();
        $this->setColumnDefinitionOnFactory($stubGridSourceFactory, [$columnFoo, $columnBar, $columnBaz, $columnQux]);
        $this->setExcludedColumnKeys($stubGridDefinitionFactory, ['bar', 'baz']);
        $dummyGridRowFactory          = $this->createStubRowFactory();
        $dummyGridCellFactory         = $this->createStubCellFactory();
        $dummyNavigationFactory       = $this->createStubNavigationFactory();
        $dummyEntityDefinitionFactory = $this->createStubEntityDefinitionFactory();
        $dummyActionFactory           = $this->createStubActionFactory();
        $dummyMassActionFactory       = $this->createStubMassActionFactory();
        $dummyGridJsEventsFactory     = $this->createStubGridJsEventsFactory();
        $dummyHyvaEventDispatcher     = $this->createStubHyvaEventDispatcher();
        $dummyLayout                  = $this->createStubLayout();

        $sut = new HyvaGridViewModel(
            'dummy-grid-name',
            $stubGridDefinitionFactory,
            $stubGridSourceFactory,
            $dummyGridRowFactory,
            $dummyGridCellFactory,
            $dummyNavigationFactory,
            $dummyEntityDefinitionFactory,
            $dummyActionFactory,
            $dummyMassActionFactory,
            $dummyGridJsEventsFactory,
            $dummyHyvaEventDispatcher,
            $dummyLayout
        );

        $this->assertSame(['foo' => $columnFoo, 'qux' => $columnQux], $sut->getColumnDefinitions());
    }

    public function testBuildsRowsWithCells(): void
    {
        $columnBaz = $this->createStubColumn('baz');
        $columnQux = $this->createStubColumn('qux');

        $stubGridSourceFactory = $this->createStubGridSourceFactory();
        /** @var Stub $stubGridSource */
        $stubGridSource = $stubGridSourceFactory->createFor($this->createStub(HyvaGridDefinitionInterface::class));
        $stubGridSource->method('getRecords')->willReturn([
            ['baz' => 111, 'qux' => 111],
            ['baz' => 222, 'qux' => 222],
            ['baz' => 333, 'qux' => 333],
        ]);

        $dummyGridDefinitionFactory = $this->createStubGridDefinitionFactory();
        $this->setColumnDefinitionOnFactory($stubGridSourceFactory, [$columnBaz, $columnQux]);
        $stubGridRowFactory = $this->createStubRowFactory();
        $stubGridRowFactory->method('create')->willReturnCallback(function ($cells) {
            return $this->createStub(RowInterface::class);
        });
        $stubGridCellFactory = $this->createStubCellFactory();
        $stubGridCellFactory->method('create')->willReturnCallback(function ($value) {
            return $this->createStub(CellInterface::class);
        });
        $stubNavigationFactory        = $this->createStubNavigationFactory();
        $dummyEntityDefinitionFactory = $this->createStubEntityDefinitionFactory();
        $dummyActionFactory           = $this->createStubActionFactory();
        $dummyMassActionFactory       = $this->createStubMassActionFactory();
        $dummyHyvaEventDispatcher     = $this->createStubHyvaEventDispatcher();
        $dummyGridJsEventsFactory     = $this->createStubGridJsEventsFactory();
        $dummyLayout                  = $this->createStubLayout();

        $sut = new HyvaGridViewModel(
            'dummy-grid-name',
            $dummyGridDefinitionFactory,
            $stubGridSourceFactory,
            $stubGridRowFactory,
            $stubGridCellFactory,
            $stubNavigationFactory,
            $dummyEntityDefinitionFactory,
            $dummyActionFactory,
            $dummyMassActionFactory,
            $dummyGridJsEventsFactory,
            $dummyHyvaEventDispatcher,
            $dummyLayout
        );

        $rows = $sut->getRows();

        $this->assertCount(3, $rows);
        $this->assertContainsOnlyInstancesOf(RowInterface::class, $rows);
    }

    public function testReturnsNavigation(): void
    {
        $dummyGridSourceFactory     = $this->createStubGridSourceFactory();
        $dummyGridDefinitionFactory = $this->createStubGridDefinitionFactory();
        $dummyGridRowFactory        = $this->createStubRowFactory();
        $dummyGridCellFactory       = $this->createStubCellFactory();
        $stubNavigationFactory      = $this->createStubNavigationFactory();
        $stubNavigationFactory->method('create')->willReturn($this->createStub(NavigationInterface::class));
        $dummyEntityDefinitionFactory = $this->createStubEntityDefinitionFactory();
        $dummyActionFactory           = $this->createStubActionFactory();
        $dummyMassActionFactory       = $this->createStubMassActionFactory();
        $dummyGridJsEventsFactory     = $this->createStubGridJsEventsFactory();
        $dummyHyvaEventDispatcher     = $this->createStubHyvaEventDispatcher();
        $dummyLayout                  = $this->createStubLayout();

        $sut = new HyvaGridViewModel(
            'dummy-grid-name',
            $dummyGridDefinitionFactory,
            $dummyGridSourceFactory,
            $dummyGridRowFactory,
            $dummyGridCellFactory,
            $stubNavigationFactory,
            $dummyEntityDefinitionFactory,
            $dummyActionFactory,
            $dummyMassActionFactory,
            $dummyGridJsEventsFactory,
            $dummyHyvaEventDispatcher,
            $dummyLayout
        );

        $this->assertInstanceOf(NavigationInterface::class, $sut->getNavigation());
    }

    public function testReturnsEntityDefinition(): void
    {
        $dummyGridSourceFactory      = $this->createStubGridSourceFactory();
        $dummyGridDefinitionFactory  = $this->createStubGridDefinitionFactory();
        $dummyGridRowFactory         = $this->createStubRowFactory();
        $dummyGridCellFactory        = $this->createStubCellFactory();
        $dummyNavigationFactory      = $this->createStubNavigationFactory();
        $stubEntityDefinitionFactory = $this->createStubEntityDefinitionFactory();
        $stubEntityDefinitionFactory->method('create')->willReturn($this->createStub(EntityDefinitionInterface::class));
        $dummyActionFactory       = $this->createStubActionFactory();
        $dummyMassActionFactory   = $this->createStubMassActionFactory();
        $dummyGridJsEventsFactory = $this->createStubGridJsEventsFactory();
        $dummyHyvaEventDispatcher = $this->createStubHyvaEventDispatcher();
        $dummyLayout              = $this->createStubLayout();

        $sut = new HyvaGridViewModel(
            'dummy-grid-name',
            $dummyGridDefinitionFactory,
            $dummyGridSourceFactory,
            $dummyGridRowFactory,
            $dummyGridCellFactory,
            $dummyNavigationFactory,
            $stubEntityDefinitionFactory,
            $dummyActionFactory,
            $dummyMassActionFactory,
            $dummyGridJsEventsFactory,
            $dummyHyvaEventDispatcher,
            $dummyLayout
        );

        $this->assertInstanceOf(EntityDefinitionInterface::class, $sut->getEntityDefinition());
    }
}
