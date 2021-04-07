<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Integration\ViewModel\HyvaGrid;

use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterface;
use Hyva\Admin\ViewModel\HyvaGrid\FilterOptionInterface;
use Hyva\Admin\ViewModel\HyvaGrid\GridFilter;
use Hyva\Admin\ViewModel\HyvaGrid\GridFilterInterface;
use Magento\Framework\App\RequestInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

use function array_map as map;
use function array_merge as merge;

/**
 * @magentoAppArea adminhtml
 */
class GridFilterTest extends TestCase
{
    const TEST_GRID = 'test-grid';

    private function createFilter(array $filterArguments, array $columnArguments = []): GridFilter
    {
        $columnArgs = merge(['key' => 'test'], $columnArguments);
        $dummyCol   = ObjectManager::getInstance()->create(ColumnDefinitionInterface::class, $columnArgs);
        $filterArgs = merge([
            'filterFormId' => 'filter-form',
            'gridName' => self::TEST_GRID,
            'columnDefinition' => $dummyCol,
        ], $filterArguments);
        return ObjectManager::getInstance()->create(GridFilterInterface::class, $filterArgs);
    }

    private function stubParams(MockObject $stubRequest, array $params): void
    {
        $stubRequest->method('getParam')->willReturnMap([[self::TEST_GRID, null, $params]]);
    }

    public function testIsKnownToTheObjectManager(): void
    {
        $this->assertInstanceOf(GridFilter::class, $this->createFilter([]));
    }

    public function testIsNotDisabledByDefault(): void
    {
        $this->assertFalse($this->createFilter([])->isDisabled());
    }

    public function isNotDisabledWhenEnabledTrueIsSpecified(): void
    {
        $this->assertFalse($this->createFilter(['enabled' => 'true'])->isDisabled());
    }

    public function isEnabledWhenSpecified(): void
    {
        $this->assertTrue($this->createFilter(['enabled' => 'false'])->isDisabled());
    }

    public function testHasFilterTypeText(): void
    {
        $sut = $this->createFilter([]);
        $this->assertStringContainsString('<input type="text"', $sut->getHtml());
    }

    public function testHasFilterTypeSelect(): void
    {
        $sut = $this->createFilter(['options' => [['label' => 'x', 'values' => ['y']]]]);
        $this->assertStringContainsString('<select', $sut->getHtml());
    }

    public function testHasBooleanFilterType(): void
    {
        $sut = $this->createFilter([], ['type' => 'bool']);
        $this->assertStringContainsString('<option value="0">No', $sut->getHtml());
    }

    public function testHasDateRangeFilterType(): void
    {
        $sut = $this->createFilter([], ['type' => 'datetime']);
        $this->assertMatchesRegularExpression('#From:.+<input type="date" form="filter-form"#s', $sut->getHtml());
    }

    public function testHasValueRangeFilterType(): void
    {
        $sut = $this->createFilter([], ['type' => 'int']);
        $this->assertMatchesRegularExpression('#From:.+<input type="text" form="filter-form"#s', $sut->getHtml());
    }

    public function testUsesGridNameToQualifyFilterFieldNames(): void
    {
        $sut = $this->createFilter(['gridName' => 'test-grid'], ['key' => 'foo']);
        $this->assertSame('test-grid[_filter][foo]', $sut->getInputName());
    }

    public function testAddsAspectsToFilterFieldNames(): void
    {
        $sut = $this->createFilter(['gridName' => 'test-grid'], ['key' => 'foo']);
        $this->assertSame('test-grid[_filter][foo][from]', $sut->getInputName('from'));
        $this->assertSame('test-grid[_filter][foo][to]', $sut->getInputName('to'));
    }

    public function testReturnsNullOptionsIfNoneAreSpecified(): void
    {
        $sut = $this->createFilter([]);
        $this->assertNull($sut->getOptions());
    }

    public function testUsesFilterOptionsIfSpecified(): void
    {
        // options for filters have multiple values for one option
        $options              = [
            ['label' => 'Option One', 'values' => ['x', 'y', 'z']],
            ['label' => 'Option Two', 'values' => ['a']],
        ];
        $sut                  = $this->createFilter(['options' => $options]);
        $resultOptionsAsArray = map(function (FilterOptionInterface $option): array {
            return ['label' => $option->getLabel(), 'values' => $option->getValues()];
        }, $sut->getOptions());
        $this->assertSame($options, $resultOptionsAsArray);
    }

    public function testUsesColumnOptionsForSelect(): void
    {
        // options for column definitions can have only a single value
        $columnOptions        = [
            ['label' => 'Option One', 'value' => 'x'],
            ['label' => 'Option Two', 'value' => 'a'],
        ];
        $sut                  = $this->createFilter([], ['options' => $columnOptions]);
        $resultOptionsAsArray = map(function (FilterOptionInterface $option): array {
            return ['label' => $option->getLabel(), 'value' => $option->getValues()[0]];
        }, $sut->getOptions());
        $this->assertSame($columnOptions, $resultOptionsAsArray);
    }

    public function testAllowsColumnOptionWithZeroValueForSelect(): void
    {
        $columnOptions        = [
            ['label' => 'Option One', 'value' => '1'],
            ['label' => 'Option Two', 'value' => '0'],
        ];
        $sut                  = $this->createFilter([], ['options' => $columnOptions]);

        $resultOptionsAsArray = map(function (FilterOptionInterface $option): array {
            return ['label' => $option->getLabel(), 'value' => $option->getValues()[0]];
        }, $sut->getOptions());
        $this->assertSame($columnOptions, $resultOptionsAsArray);
    }

    public function testReturnsFilterValueNullIfNotInRequest(): void
    {
        $sut = $this->createFilter([]);
        $this->assertNull($sut->getValue());
        $this->assertNull($sut->getValue('foo'));
    }

    public function testReturnsFilterValueFromRequest(): void
    {
        $key         = 'foo';
        $value       = 'bar';
        $stubRequest = $this->createMock(RequestInterface::class);
        $this->stubParams($stubRequest, ['_filter' => [$key => $value]]);

        $sut = $this->createFilter(['request' => $stubRequest], ['key' => $key]);

        $this->assertSame($value, $sut->getValue());
    }

    public function testReturnsFilterAspectValueFromRequest(): void
    {
        $key         = 'foo';
        $value       = 'bar';
        $aspect      = 'buz';
        $stubRequest = $this->createMock(RequestInterface::class);
        $this->stubParams($stubRequest, ['_filter' => [$key => [$aspect => $value]]]);

        $sut = $this->createFilter(['request' => $stubRequest], ['key' => $key]);

        $this->assertSame([$aspect => $value], $sut->getValue());
        $this->assertSame($value, $sut->getValue($aspect));
    }

    public function testSetsFilterTemplateIfSpecified(): void
    {
        $sut = $this->createFilter(['template' => 'Hyva_Admin::testing/filter-test.phtml']);
        $this->assertStringContainsString('This is a template assigned to a filter, used in a test.', $sut->getHtml());
    }

    public function testUsesCustomFilterTypeIfSpecified(): void
    {
        $sut = $this->createFilter(['filterType' => StubTestFilterType::class]);

        $this->assertStringContainsString(StubTestFilterType::STUB_OUTPUT, $sut->getHtml());
    }
}
