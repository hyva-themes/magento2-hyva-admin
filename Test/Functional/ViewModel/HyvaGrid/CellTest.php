<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Functional\ViewModel\HyvaGrid;

use Hyva\Admin\ViewModel\HyvaGrid\Cell;
use Hyva\Admin\ViewModel\HyvaGrid\CellInterface;
use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

use function array_filter as filter;

/**
 * @magentoAppArea adminhtml
 */
class CellTest extends TestCase
{
    private function createColumnDefinition(?string $type = null): ColumnDefinitionInterface
    {
        $arguments = filter(['key' => 'test', 'type' => $type]);
        return ObjectManager::getInstance()->create(ColumnDefinitionInterface::class, $arguments);
    }

    private function createCellWithValue($value, string $type = null): Cell
    {
        $arguments = ['value' => $value, 'columnDefinition' => $this->createColumnDefinition($type)];
        return ObjectManager::getInstance()->create(CellInterface::class, $arguments);
    }

    public function testIsKnownToTheObjectManager(): void
    {
        $this->assertInstanceOf(Cell::class, $this->createCellWithValue(null));
    }

    public function testRendersString(): void
    {
        $value = 'A String';
        $cell  = $this->createCellWithValue($value);

        $this->assertSame($value, $cell->getHtml());
    }

    public function testRendersInt(): void
    {
        $value = 123;
        $cell  = $this->createCellWithValue($value);

        $this->assertSame("$value", $cell->getHtml());
    }

    public function testThrowsExceptionIfUnknownTypeIsNotStringable(): void
    {
        $thisValueCanNotBeCastToString = new class() {
        };
        $cell  = $this->createCellWithValue($thisValueCanNotBeCastToString);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to cast a value of column "test" with type "unknown"');

        $cell->getHtml();
    }
}
