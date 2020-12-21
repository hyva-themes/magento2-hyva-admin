<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Integration\ViewModel\HyvaGrid;

use Hyva\Admin\ViewModel\HyvaGrid\CellInterfaceFactory;
use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinition;
use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterfaceFactory;
use Hyva\Admin\ViewModel\HyvaGrid\Row;
use Hyva\Admin\ViewModel\HyvaGrid\RowInterface;
use Hyva\Admin\ViewModel\HyvaGrid\RowInterfaceFactory;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea adminhtml
 */
class RowTest extends TestCase
{
    public function testIsKnownToObjectManager(): void
    {
        $row = ObjectManager::getInstance()->create(RowInterface::class, ['cells' => []]);
        $this->assertInstanceOf(Row::class, $row);
    }

    public function testReturnsArrayWithCells(): void
    {
        $columnDefinitionFactory = ObjectManager::getInstance()->create(ColumnDefinitionInterfaceFactory::class);
        $cellFactory             = ObjectManager::getInstance()->create(CellInterfaceFactory::class);
        $rowFactory              = ObjectManager::getInstance()->create(RowInterfaceFactory::class);

        /** @var ColumnDefinition $columnDefinition */
        $columnDefinition = $columnDefinitionFactory->create(['key' => 'foo', 'isVisible' => true]);
        $cells            = [$cellFactory->create(['value' => true, 'columnDefinition' => $columnDefinition])];
        /** @var RowInterface $row */
        $row = $rowFactory->create(['cells' => $cells]);

        $actualCells = $row->getCells();
        $this->assertCount(1, $actualCells);
        $this->assertSame($cells, $actualCells);
    }
}
