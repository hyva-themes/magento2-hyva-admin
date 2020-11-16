<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Unit\ViewModel;

use Hyva\Admin\Model\HyvaGridDefinitionInterface;
use Hyva\Admin\Model\HyvaGridDefinitionInterfaceFactory;
use Hyva\Admin\Model\HyvaGridSourceFactory;
use Hyva\Admin\Model\HyvaGridSourceInterface;
use Hyva\Admin\ViewModel\HyvaGrid\CellInterface;
use Hyva\Admin\ViewModel\HyvaGrid\CellInterfaceFactory;
use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterface;
use Hyva\Admin\ViewModel\HyvaGrid\RowInterface;
use Hyva\Admin\ViewModel\HyvaGrid\RowInterfaceFactory;
use Hyva\Admin\ViewModel\HyvaGridInterface;
use Hyva\Admin\ViewModel\HyvaGridViewModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class HyvaGridViewModelTest extends TestCase
{
    /**
     * @param string $key
     * @return ColumnDefinitionInterface|MockObject
     */
    private function createStubColumn(string $key): ColumnDefinitionInterface
    {
        $column = $this->createMock(ColumnDefinitionInterface::class);
        $column->method('getKey')->willReturn($key);
        return $column;
    }

    /**
     * @return HyvaGridSourceFactory|MockObject
     */
    private function createStubGridSourceFactory(): HyvaGridSourceFactory
    {
        $stubGridSource        = $this->createMock(HyvaGridSourceInterface::class);
        $stubGridSourceFactory = $this->createMock(HyvaGridSourceFactory::class);
        $stubGridSourceFactory->method('createFor')->willReturn($stubGridSource);

        return $stubGridSourceFactory;
    }

    private function createStubGridDefinitionFactory(): HyvaGridDefinitionInterfaceFactory
    {
        $stubGridDefinition        = $this->createMock(HyvaGridDefinitionInterface::class);
        $stubGridDefinitionFactory = $this->createMock(HyvaGridDefinitionInterfaceFactory::class);
        $stubGridDefinitionFactory->method('create')->willReturn($stubGridDefinition);

        return $stubGridDefinitionFactory;
    }

    /**
     * @return MockObject|RowInterfaceFactory
     */
    private function createStubRowFactory(): MockObject
    {
        return $this->createMock(RowInterfaceFactory::class);
    }

    /**
     * @return MockObject|CellInterfaceFactory
     */
    private function createStubCellFactory(): MockObject
    {
        return $this->createMock(CellInterfaceFactory::class);
    }

    private function setColumnDefinition(HyvaGridSourceFactory $stubGridSourceFactory, array $columnDefinitions): void
    {
        /** @var MockObject $stubGridSource */
        $stubGridSource = $stubGridSourceFactory->createFor([]);
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
        /** @var MockObject $stubGridDefinition */
        $stubGridDefinition = $stubGridDefinitionFactory->create();
        $stubGridDefinition->method('getExcludedColumnKeys')->willReturn($excludedColumnKeys);
    }

    /**
     * @param HyvaGridDefinitionInterfaceFactory $stubGridDefinitionFactory
     * @param ColumnDefinitionInterface[] $includedColumns
     */
    private function setIncludedColumns(
        HyvaGridDefinitionInterfaceFactory $stubGridDefinitionFactory,
        array $includedColumns
    ): void {
        /** @var MockObject $stubGridDefinition */
        $stubGridDefinition = $stubGridDefinitionFactory->create();
        $stubGridDefinition->method('getIncludedColumns')->willReturn($includedColumns);
    }

    public function testImplementsHyvaGridInterface(): void
    {
        $stubGridSourceFactory     = $this->createStubGridSourceFactory();
        $stubGridDefinitionFactory = $this->createStubGridDefinitionFactory();
        $stubGridRowFactory        = $this->createStubRowFactory();
        $stubGridCellFactory       = $this->createStubCellFactory();

        $sut = new HyvaGridViewModel(
            'dummy-grid-name',
            $stubGridDefinitionFactory,
            $stubGridSourceFactory,
            $stubGridRowFactory,
            $stubGridCellFactory
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

        $stubGridSourceFactory     = $this->createStubGridSourceFactory();
        $stubGridDefinitionFactory = $this->createStubGridDefinitionFactory();
        $this->setColumnDefinition($stubGridSourceFactory, $columnDefinitions);
        $stubGridRowFactory  = $this->createStubRowFactory();
        $stubGridCellFactory = $this->createStubCellFactory();

        $sut = new HyvaGridViewModel(
            'test-grid-name',
            $stubGridDefinitionFactory,
            $stubGridSourceFactory,
            $stubGridRowFactory,
            $stubGridCellFactory);

        $this->assertSame(3, $sut->getColumnCount());
    }

    public function testReturnsArrayOfColumnDefinitions(): void
    {
        $columnFoo         = $this->createStubColumn('foo');
        $columnBar         = $this->createStubColumn('bar');
        $columnDefinitions = [$columnFoo, $columnBar];

        $stubGridSourceFactory     = $this->createStubGridSourceFactory();
        $stubGridDefinitionFactory = $this->createStubGridDefinitionFactory();
        $this->setColumnDefinition($stubGridSourceFactory, $columnDefinitions);
        $stubGridRowFactory  = $this->createStubRowFactory();
        $stubGridCellFactory = $this->createStubCellFactory();

        $sut = new HyvaGridViewModel(
            'dummy-grid-name',
            $stubGridDefinitionFactory,
            $stubGridSourceFactory,
            $stubGridRowFactory,
            $stubGridCellFactory
        );

        $this->assertSame(['foo' => $columnFoo, 'bar' => $columnBar], $sut->getColumnDefinitions());
    }

    public function testRemovesExcludedColumns(): void
    {
        $columnFoo = $this->createStubColumn('foo');
        $columnBar = $this->createStubColumn('bar');
        $columnBaz = $this->createStubColumn('baz');
        $columnQux = $this->createStubColumn('qux');

        $stubGridSourceFactory     = $this->createStubGridSourceFactory();
        $stubGridDefinitionFactory = $this->createStubGridDefinitionFactory();
        $this->setColumnDefinition($stubGridSourceFactory, [$columnFoo, $columnBar, $columnBaz, $columnQux]);
        $this->setExcludedColumnKeys($stubGridDefinitionFactory, ['bar', 'baz']);
        $stubGridRowFactory  = $this->createStubRowFactory();
        $stubGridCellFactory = $this->createStubCellFactory();

        $sut = new HyvaGridViewModel(
            'dummy-grid-name',
            $stubGridDefinitionFactory,
            $stubGridSourceFactory,
            $stubGridRowFactory,
            $stubGridCellFactory
        );

        $this->assertSame(['foo' => $columnFoo, 'qux' => $columnQux], $sut->getColumnDefinitions());
    }

    public function testBuildsRowsWithCells(): void
    {
        $columnBaz = $this->createStubColumn('baz');
        $columnQux = $this->createStubColumn('qux');

        $stubGridSourceFactory     = $this->createStubGridSourceFactory();
        /** @var MockObject $stubGridSource */
        $stubGridSource = $stubGridSourceFactory->createFor([]);
        $stubGridSource->method('getRecords')->willReturn([
            ['baz' => 111, 'qux' => 111],
            ['baz' => 222, 'qux' => 222],
            ['baz' => 333, 'qux' => 333],
        ]);

        $stubGridDefinitionFactory = $this->createStubGridDefinitionFactory();
        $this->setColumnDefinition($stubGridSourceFactory, [$columnBaz, $columnQux]);
        $stubGridRowFactory  = $this->createStubRowFactory();
        $stubGridRowFactory->method('create')->willReturnCallback(function ($cells) {
            return $this->createMock(RowInterface::class);
        });
        $stubGridCellFactory = $this->createStubCellFactory();
        $stubGridCellFactory->method('create')->willReturnCallback(function ($value) {
            return $this->createMock(CellInterface::class);
        });

        $sut = new HyvaGridViewModel(
            'dummy-grid-name',
            $stubGridDefinitionFactory,
            $stubGridSourceFactory,
            $stubGridRowFactory,
            $stubGridCellFactory
        );

        $rows = $sut->getRows();

        $this->assertCount(3, $rows);
        $this->assertContainsOnly(RowInterface::class, $rows);
    }

    public function testReturnsNavigation(): void
    {
        $this->markTestIncomplete('something about the source and total items and such');
    }
}
