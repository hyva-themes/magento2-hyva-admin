<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Functional\ViewModel\HyvaGrid;

use Hyva\Admin\ViewModel\HyvaGrid\Cell;
use Hyva\Admin\ViewModel\HyvaGrid\CellInterface;
use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

use function array_filter as filter;
use function array_merge as merge;

/**
 * @magentoAppArea adminhtml
 */
class CellTest extends TestCase
{
    private function createColumnDefinition(?string $type = null, array $args = []): ColumnDefinitionInterface
    {
        $arguments = filter(merge($args, ['key' => 'test', 'type' => $type]));
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

    public function testRendersTemplate(): void
    {
        $value            = ['currency' => 'EUR', 'amount' => 10.10];
        $template         = 'Hyva_Admin::testing/currency-cell-test.phtml';
        $columnDefinition = $this->createColumnDefinition('array', ['template' => $template]);
        $arguments        = ['value' => $value, 'columnDefinition' => $columnDefinition];

        /** @var Cell $cell */
        $cell = ObjectManager::getInstance()->create(CellInterface::class, $arguments);
        $this->assertSame('<div>Test: EUR::10.10</div>', trim($cell->getHtml(), PHP_EOL));
    }
}
