<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Functional\ViewModel\HyvaGrid;

use Hyva\Admin\Model\GridSourceType\ArrayProviderGridSourceType;
use Hyva\Admin\Model\HyvaGridSourceInterface;
use Hyva\Admin\Test\Functional\TestingGridDataProvider;
use Hyva\Admin\ViewModel\HyvaGrid\GridFilterInterface;
use Hyva\Admin\ViewModel\HyvaGrid\Navigation;
use Hyva\Admin\ViewModel\HyvaGrid\NavigationInterface;
use Magento\Backend\Model\UrlInterface as BackendUrlBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

use function array_filter as filter;

/**
 * @magentoAppArea adminhtml
 */
class NavigationTest extends TestCase
{
    const TEST_GRID = 'test-grid';

    private function createArrayGridSource(array $gridData): HyvaGridSourceInterface
    {
        $gridSourceConfig = ['arrayProvider' => TestingGridDataProvider::withArray($gridData)];
        $sourceTypeArgs   = ['gridName' => 'testing-grid', 'sourceConfiguration' => $gridSourceConfig];
        $arraySourceType  = ObjectManager::getInstance()->create(ArrayProviderGridSourceType::class, $sourceTypeArgs);
        $gridSourceArgs   = ['gridSourceType' => $arraySourceType];

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

    private function getUrlBuilder(): BackendUrlBuilder
    {
        return ObjectManager::getInstance()->get(BackendUrlBuilder::class);
    }

    public function testIsKnownToObjectManager(): void
    {
        $gridData         = [];
        $navigationConfig = [];
        $sut              = $this->createNavigation($gridData, $navigationConfig);
        $this->assertInstanceOf(Navigation::class, $sut);
    }

    public function testDefaultsToFirstPage(): void
    {
        $gridData         = [];
        $navigationConfig = [];
        $sut              = $this->createNavigation($gridData, $navigationConfig);
        $this->assertSame(1, $sut->getCurrentPageNumber());
    }

    private function stubParams(MockObject $stubRequest, array $params): void
    {
        $stubRequest->method('getParam')->willReturnMap([[self::TEST_GRID, null, $params]]);
    }

    public function testReturnsRequestedPageNumber(): void
    {
        $stubRequest = $this->createMock(RequestInterface::class);
        $this->stubParams($stubRequest, ['p' => 3]);

        $gridData         = [['id' => 'a'], ['id' => 'b'], ['id' => 'c']];
        $navigationConfig = ['pager' => ['defaultPageSize' => 1]];
        $sut              = $this->createNavigation($gridData, $navigationConfig, $stubRequest);

        $this->assertSame(3, $sut->getCurrentPageNumber());
    }

    public function testReturnsMaxPageNumberIfRequestIsLarger(): void
    {
        $stubRequest = $this->createMock(RequestInterface::class);
        $this->stubParams($stubRequest, ['p' => 3]);

        $gridData         = [['id' => 'a'], ['id' => 'b']];
        $navigationConfig = ['pager' => ['defaultPageSize' => 1]];
        $sut              = $this->createNavigation($gridData, $navigationConfig, $stubRequest);

        $this->assertSame(2, $sut->getCurrentPageNumber());
    }

    public function testReturnsMaxPageNumber(): void
    {
        $gridData         = [['id' => 'a'], ['id' => 'b']];
        $navigationConfig = [];
        $sut              = $this->createNavigation($gridData, $navigationConfig);

        $this->assertSame(2, $sut->getTotalRowsCount());
    }

    public function testHasPreviousPageIfCurrentPageLargerOne(): void
    {
        $stubRequest = $this->createMock(RequestInterface::class);
        $this->stubParams($stubRequest, ['p' => 2]);

        $gridData         = [['id' => 'a'], ['id' => 'b']];
        $navigationConfig = ['pager' => ['defaultPageSize' => 1]];
        $sut              = $this->createNavigation($gridData, $navigationConfig, $stubRequest);

        $this->assertTrue($sut->hasPreviousPage());
    }

    public function testHasNoPreviousPageIfCurrentPageIsOne(): void
    {
        $stubRequest = $this->createMock(RequestInterface::class);
        $this->stubParams($stubRequest, ['p' => 2]);

        $gridData         = [['id' => 'a']];
        $navigationConfig = [];
        $sut              = $this->createNavigation($gridData, $navigationConfig, $stubRequest);

        $this->assertFalse($sut->hasPreviousPage());
    }

    public function testHasNextPageIfNotOnLastPage(): void
    {
        $stubRequest = $this->createMock(RequestInterface::class);
        $this->stubParams($stubRequest, ['p' => 1]);

        $gridData         = [['id' => 'a'], ['id' => 'b']];
        $navigationConfig = ['pager' => ['defaultPageSize' => 1]];
        $sut              = $this->createNavigation($gridData, $navigationConfig, $stubRequest);

        $this->assertTrue($sut->hasNextPage());
    }

    public function testHasNoNextPageOnLastPage(): void
    {
        $stubRequest = $this->createMock(RequestInterface::class);
        $this->stubParams($stubRequest, ['p' => 1]);

        $gridData         = [['id' => 'a'], ['id' => 'b']];
        $navigationConfig = ['pager' => ['defaultPageSize' => 2]];
        $sut              = $this->createNavigation($gridData, $navigationConfig, $stubRequest);

        $this->assertFalse($sut->hasNextPage());
    }

    public function testReturnsCurrentUrlAsPreviousPageUrlOnFirstPage(): void
    {
        $stubRequest = $this->createMock(RequestInterface::class);
        $this->stubParams($stubRequest, ['p' => 1]);

        $gridData         = [['id' => 'a'], ['id' => 'b']];
        $navigationConfig = ['pager' => ['defaultPageSize' => 1]];
        $sut              = $this->createNavigation($gridData, $navigationConfig, $stubRequest);

        $expected = $this->getUrlBuilder()
                         ->getUrl('*/*/*', ['_current' => true, '_query' => [self::TEST_GRID . '[p]' => 1]]);
        $this->assertSame($expected, $sut->getPreviousPageUrl());
    }

    public function testReturnsPreviousPageUrl(): void
    {
        $stubRequest = $this->createMock(RequestInterface::class);
        $this->stubParams($stubRequest, ['p' => 2]);

        $gridData         = [['id' => 'a'], ['id' => 'b']];
        $navigationConfig = ['pager' => ['defaultPageSize' => 1]];
        $sut              = $this->createNavigation($gridData, $navigationConfig, $stubRequest);

        $expected = $this->getUrlBuilder()
                         ->getUrl('*/*/*', ['_current' => true, '_query' => [self::TEST_GRID . '[p]' => 1]]);
        $this->assertSame($expected, $sut->getPreviousPageUrl());
    }

    public function testReturnsPreviousPageUrlIfRequestedPageIsBeyondMaxPage(): void
    {
        $stubRequest = $this->createMock(RequestInterface::class);
        $this->stubParams($stubRequest, ['p' => 4]);

        $gridData         = [['id' => 'a'], ['id' => 'b'], ['id' => 'c']];
        $navigationConfig = ['pager' => ['defaultPageSize' => 1]];
        $sut              = $this->createNavigation($gridData, $navigationConfig, $stubRequest);

        $expected = $this->getUrlBuilder()
                         ->getUrl('*/*/*', ['_current' => true, '_query' => [self::TEST_GRID . '[p]' => 2]]);
        $this->assertSame($expected, $sut->getPreviousPageUrl());
    }

    public function testReturnsCurrentUrlAsNextPageUrlOnLastPage(): void
    {
        $stubRequest = $this->createMock(RequestInterface::class);
        $this->stubParams($stubRequest, ['p' => 2]);

        $gridData         = [['id' => 'a'], ['id' => 'b']];
        $navigationConfig = ['pager' => ['defaultPageSize' => 1]];
        $sut              = $this->createNavigation($gridData, $navigationConfig, $stubRequest);

        $expected = $this->getUrlBuilder()
                         ->getUrl('*/*/*', ['_current' => true, '_query' => [self::TEST_GRID . '[p]' => 2]]);
        $this->assertSame($expected, $sut->getNextPageUrl());
    }

    public function testReturnsNextPageUrl(): void
    {
        $stubRequest = $this->createMock(RequestInterface::class);
        $this->stubParams($stubRequest, ['p' => 2]);

        $gridData         = [['id' => 'a'], ['id' => 'b'], ['id' => 'c']];
        $navigationConfig = ['pager' => ['defaultPageSize' => 1]];
        $sut              = $this->createNavigation($gridData, $navigationConfig, $stubRequest);

        $expected = $this->getUrlBuilder()
                         ->getUrl('*/*/*', ['_current' => true, '_query' => [self::TEST_GRID . '[p]' => 3]]);
        $this->assertSame($expected, $sut->getNextPageUrl());
    }

    public function testReturnsDefaultPageSizes(): void
    {
        $gridData         = [];
        $navigationConfig = [];
        $sut              = $this->createNavigation($gridData, $navigationConfig);
        $this->assertSame([10, 20, 50], $sut->getPageSizes());
    }

    public function testReturnsConfiguredDefaultPageSizes(): void
    {
        $gridData         = [];
        $navigationConfig = ['pager' => ['pageSizes' => '100, 200']];
        $sut              = $this->createNavigation($gridData, $navigationConfig);
        $this->assertSame([100, 200], $sut->getPageSizes());
    }

    public function testDefaultPageSize(): void
    {
        $gridData         = [];
        $navigationConfig = [];
        $sut              = $this->createNavigation($gridData, $navigationConfig);
        $this->assertSame(20, $sut->getPageSize());
    }

    public function testReturnsRequestedPageSizeIfValid(): void
    {
        $stubRequest = $this->createMock(RequestInterface::class);
        $this->stubParams($stubRequest, ['pageSize' => 50]);

        $gridData         = [['id' => 'a'], ['id' => 'b'], ['id' => 'c']];
        $navigationConfig = ['pager' => ['pageSizes' => '20, 50, 100']];
        $sut              = $this->createNavigation($gridData, $navigationConfig, $stubRequest);

        $this->assertSame(50, $sut->getPageSize());
    }

    public function testReturnsDefaultPageSizeRequestedPageSizeIsNotValid(): void
    {
        $stubRequest = $this->createMock(RequestInterface::class);
        $this->stubParams($stubRequest, ['pageSize' => 30]);

        $gridData         = [['id' => 'a'], ['id' => 'b'], ['id' => 'c']];
        $navigationConfig = ['pager' => ['pageSizes' => '20, 50, 100', 'defaultPageSize' => 20]];
        $sut              = $this->createNavigation($gridData, $navigationConfig, $stubRequest);

        $this->assertSame(20, $sut->getPageSize());
    }

    public function testReturnsConfiguredDefaultPageSize(): void
    {
        $gridData         = [];
        $navigationConfig = ['pager' => ['defaultPageSize' => 5]];
        $sut              = $this->createNavigation($gridData, $navigationConfig);
        $this->assertSame(5, $sut->getPageSize());
    }

    public function testSetsPageSizeOnSearchCriteria(): void
    {
        $stubRequest = $this->createMock(RequestInterface::class);
        $pageSize    = 20;
        $this->stubParams($stubRequest, ['pageSize' => $pageSize]);

        $gridData         = [['id' => 'a'], ['id' => 'b'], ['id' => 'c']];
        $navigationConfig = ['pager' => ['pageSizes' => '20, 50, 100']];
        $sut              = $this->createNavigation($gridData, $navigationConfig, $stubRequest);

        $searchCriteria = $sut->getSearchCriteria();
        $this->assertSame($pageSize, $searchCriteria->getPageSize());
    }

    public function testSetsCurrentPageOnSearchCriteria(): void
    {
        $stubRequest = $this->createMock(RequestInterface::class);
        $currentPage = 2;
        $this->stubParams($stubRequest, ['p' => $currentPage, 'pageSize' => 1]);

        $gridData         = [['id' => 'a'], ['id' => 'b'], ['id' => 'c']];
        $navigationConfig = ['pager' => ['pageSizes' => '1, 20, 50, 100']];
        $sut              = $this->createNavigation($gridData, $navigationConfig, $stubRequest);

        $searchCriteria = $sut->getSearchCriteria();
        $this->assertSame($currentPage, $searchCriteria->getCurrentPage());
    }

    public function testSetsSortOrderSearchCriteria(): void
    {
        $stubRequest = $this->createMock(RequestInterface::class);
        $this->stubParams($stubRequest, ['sortBy' => 'name', 'sortDirection' => 'asc']);

        $gridData         = [['id' => 'a', 'name' => 'a'], ['id' => 'b', 'name' => 'b'], ['id' => 'c', 'name' => 'c']];
        $navigationConfig = [];
        $sut              = $this->createNavigation($gridData, $navigationConfig, $stubRequest);

        $searchCriteria = $sut->getSearchCriteria();
        $this->assertSame('name', $searchCriteria->getSortOrders()[0]->getField());
        $this->assertSame('ASC', $searchCriteria->getSortOrders()[0]->getDirection());
    }

    public function testAddsBooleanFiltersToSearchCriteria(): void
    {
        $stubRequest = $this->createMock(RequestInterface::class);
        $this->stubParams($stubRequest, ['_filter' => ['id' => '1']]);

        $gridData         = [['id' => true, 'name' => 'a'], ['id' => false, 'name' => 'b'], ['id' => true, 'name' => 'c']];
        $navigationConfig = ['filters' => [['key' => 'id']]];
        $sut              = $this->createNavigation($gridData, $navigationConfig, $stubRequest);

        $searchCriteria = $sut->getSearchCriteria();
        $filterGroups   = $searchCriteria->getFilterGroups();
        $this->assertCount(1, $filterGroups);
        $this->assertSame('id', $filterGroups[0]->getFilters()[0]->getField());
        $this->assertSame(1, $filterGroups[0]->getFilters()[0]->getValue());
        $this->assertSame('eq', $filterGroups[0]->getFilters()[0]->getConditionType());
    }

    public function testAddsDateRangeFiltersToSearchCriteria(): void
    {
        $stubRequest = $this->createMock(RequestInterface::class);
        $this->stubParams($stubRequest, ['_filter' => ['id' => ['from' => 'a', 'to' => 'b']]]);

        $gridData         = [['id' => '2020-12-10 10:10:00', 'name' => 'a']];
        $navigationConfig = ['filters' => [['key' => 'id']]];
        $sut              = $this->createNavigation($gridData, $navigationConfig, $stubRequest);

        $searchCriteria = $sut->getSearchCriteria();
        $filterGroups   = $searchCriteria->getFilterGroups();
        $this->assertCount(2, $filterGroups);
        $this->assertSame('id', $filterGroups[0]->getFilters()[0]->getField());
        $this->assertSame('a', $filterGroups[0]->getFilters()[0]->getValue());
        $this->assertSame('from', $filterGroups[0]->getFilters()[0]->getConditionType());

        $this->assertSame('id', $filterGroups[1]->getFilters()[0]->getField());
        $this->assertSame('b', $filterGroups[1]->getFilters()[0]->getValue());
        $this->assertSame('to', $filterGroups[1]->getFilters()[0]->getConditionType());
    }

    public function testAddsSelectFiltersToSearchCriteria(): void
    {
        $optionValues = ['16', '17', '18'];
        $options      = [
            ['label' => 'reddish', 'values' => $optionValues],
            ['label' => 'blueish', 'values' => ['12']],
            ['label' => 'rose', 'values' => ['100']],
        ];
        $stubRequest  = $this->createMock(RequestInterface::class);
        $this->stubParams($stubRequest, ['_filter' => ['id' => md5(json_encode($optionValues))]]);

        $gridData         = [['id' => 'a', 'name' => 'a'], ['id' => 'b', 'name' => 'b'], ['id' => 'c', 'name' => 'c']];
        $navigationConfig = ['filters' => [['key' => 'id', 'options' => $options]]];
        $sut              = $this->createNavigation($gridData, $navigationConfig, $stubRequest);

        $searchCriteria = $sut->getSearchCriteria();
        $filterGroups   = $searchCriteria->getFilterGroups();
        $this->assertCount(1, $filterGroups);
        $this->assertSame('id', $filterGroups[0]->getFilters()[0]->getField());
        $this->assertSame($optionValues, $filterGroups[0]->getFilters()[0]->getValue());
        $this->assertSame('finset', $filterGroups[0]->getFilters()[0]->getConditionType());
    }

    public function testAddsTextFiltersToSearchCriteria(): void
    {
        $stubRequest = $this->createMock(RequestInterface::class);
        $this->stubParams($stubRequest, ['_filter' => ['id' => 'xxx']]);

        $gridData         = [['id' => 'a', 'name' => 'a'], ['id' => 'b', 'name' => 'b'], ['id' => 'c', 'name' => 'c']];
        $navigationConfig = ['filters' => [['key' => 'id', 'input' => 'text']]];
        $sut              = $this->createNavigation($gridData, $navigationConfig, $stubRequest);

        $searchCriteria = $sut->getSearchCriteria();
        $filters        = $searchCriteria->getFilterGroups();
        $this->assertCount(1, $filters);
        $this->assertSame('id', $filters[0]->getFilters()[0]->getField());
        $this->assertSame('%xxx%', $filters[0]->getFilters()[0]->getValue());
        $this->assertSame('like', $filters[0]->getFilters()[0]->getConditionType());
    }

    public function testAddsValueRangeFiltersToSearchCriteria(): void
    {
        $stubRequest = $this->createMock(RequestInterface::class);
        $this->stubParams($stubRequest, ['_filter' => ['id' => ['from' => 2, 'to' => 3]]]);

        $gridData         = [['id' => 1, 'name' => 'a'], ['id' => 2, 'name' => 'b'], ['id' => 3, 'name' => 'c']];
        $navigationConfig = ['filters' => [['key' => 'id']]];
        $sut              = $this->createNavigation($gridData, $navigationConfig, $stubRequest);

        $searchCriteria = $sut->getSearchCriteria();
        $filterGroups   = $searchCriteria->getFilterGroups();
        $this->assertCount(2, $filterGroups);
        $this->assertSame('id', $filterGroups[0]->getFilters()[0]->getField());
        $this->assertSame(2, $filterGroups[0]->getFilters()[0]->getValue());
        $this->assertSame('gteq', $filterGroups[0]->getFilters()[0]->getConditionType());

        $this->assertSame('id', $filterGroups[1]->getFilters()[0]->getField());
        $this->assertSame(3, $filterGroups[1]->getFilters()[0]->getValue());
        $this->assertSame('lteq', $filterGroups[1]->getFilters()[0]->getConditionType());
    }

    public function testReturnsFirstColumnAsDefaultSortByColumn(): void
    {
        $gridData         = [
            ['col_a' => 'xx', 'col_b' => 'yy'],
            ['col_a' => 'xx', 'col_b' => 'yy'],
        ];
        $navigationConfig = [];
        $sut              = $this->createNavigation($gridData, $navigationConfig);

        $this->assertSame('col_a', $sut->getSortByColumn());
    }

    public function testReturnsConfiguredDefaultSortByColumnIfPresent(): void
    {
        $gridData         = [
            ['col_a' => 'xx', 'col_b' => 'yy'],
            ['col_a' => 'xx', 'col_b' => 'yy'],
        ];
        $navigationConfig = ['sorting' => ['defaultSortByColumn' => 'col_b']];
        $sut              = $this->createNavigation($gridData, $navigationConfig);

        $this->assertSame('col_b', $sut->getSortByColumn());
    }

    public function testReturnsRequestedSortByColumnIfPresent(): void
    {
        $gridData         = [
            ['col_a' => 'xx', 'col_b' => 'yy', 'col_c' => 'zz'],
            ['col_a' => 'xx', 'col_b' => 'yy', 'col_c' => 'zz'],
        ];
        $navigationConfig = ['sorting' => ['defaultSortByColumn' => 'col_b']];

        $stubRequest = $this->createMock(RequestInterface::class);
        $sortCol     = 'col_c';
        $this->stubParams($stubRequest, ['sortBy' => $sortCol]);
        $sut = $this->createNavigation($gridData, $navigationConfig, $stubRequest);

        $this->assertSame('col_c', $sut->getSortByColumn());
    }

    public function testReturnsDefaultSortByForInvalidRequestedSortColumn(): void
    {
        $gridData         = [
            ['col_a' => 'xx', 'col_b' => 'yy', 'col_c' => 'zz'],
            ['col_a' => 'xx', 'col_b' => 'yy', 'col_c' => 'zz'],
        ];
        $navigationConfig = ['sorting' => ['defaultSortByColumn' => 'col_b']];

        $stubRequest = $this->createMock(RequestInterface::class);
        $this->stubParams($stubRequest, ['sortBy' => 'col_invalid']);
        $sut = $this->createNavigation($gridData, $navigationConfig, $stubRequest);

        $this->assertSame('col_b', $sut->getSortByColumn());
    }

    public function testReturnsDefaultAscendingSortDirection(): void
    {
        $gridData         = [
            ['col_a' => 'xx', 'col_b' => 'yy'],
            ['col_a' => 'xx', 'col_b' => 'yy'],
        ];
        $navigationConfig = [];
        $sut              = $this->createNavigation($gridData, $navigationConfig);

        $this->assertSame('asc', $sut->getSortDirection());
    }

    public function testReturnsConfiguredDefaultSortDirection(): void
    {
        $gridData         = [
            ['col_a' => 'xx', 'col_b' => 'yy'],
            ['col_a' => 'xx', 'col_b' => 'yy'],
        ];
        $navigationConfig = ['sorting' => ['defaultSortDirection' => 'desc']];
        $sut              = $this->createNavigation($gridData, $navigationConfig);

        $this->assertSame('desc', $sut->getSortDirection());
    }

    public function testReturnsRequestedSortDirection(): void
    {
        $gridData         = [
            ['col_a' => 'xx', 'col_b' => 'yy'],
            ['col_a' => 'xx', 'col_b' => 'yy'],
        ];
        $navigationConfig = ['sorting' => ['defaultSortDirection' => 'desc']];
        $stubRequest      = $this->createMock(RequestInterface::class);
        $this->stubParams($stubRequest, ['sortDirection' => 'asc']);
        $sut = $this->createNavigation($gridData, $navigationConfig, $stubRequest);

        $this->assertSame('asc', $sut->getSortDirection());
    }

    public function testReturnsSortByColumnUrl(): void
    {
        $stubRequest = $this->createMock(RequestInterface::class);
        $this->stubParams($stubRequest, ['p' => 2, 'sortBy' => 'foo', 'sortDirection' => 'desc']);

        $gridData         = [['id' => 'a'], ['id' => 'b']];
        $navigationConfig = [];
        $sut              = $this->createNavigation($gridData, $navigationConfig, $stubRequest);

        $expected = $this->getUrlBuilder()
                         ->getUrl('*/*/*', [
                             '_current' => true,
                             '_query'   => [
                                 self::TEST_GRID . '[p]'             => 1,
                                 self::TEST_GRID . '[sortBy]'        => 'id',
                                 self::TEST_GRID . '[sortDirection]' => 'desc',
                             ],
                         ]);

        $this->assertSame($expected, $sut->getSortByUrl('id', 'desc'));
    }

    public function testReturnsNoGridFilterIfNotConfiguredForAColumn(): void
    {
        $gridData         = [
            ['col_a' => 'xx', 'col_b' => 'yy'],
            ['col_a' => 'xx', 'col_b' => 'yy'],
        ];
        $navigationConfig = [];
        $sut              = $this->createNavigation($gridData, $navigationConfig);

        $this->assertNull($sut->getFilter('col_a'));
    }

    public function testReturnsGridFilterIfConfigured(): void
    {
        $gridData         = [
            ['col_a' => 'xx', 'col_b' => 'yy'],
            ['col_a' => 'xx', 'col_b' => 'yy'],
        ];
        $navigationConfig = ['filters' => [['key' => 'col_a']]];
        $sut              = $this->createNavigation($gridData, $navigationConfig);

        $this->assertInstanceOf(GridFilterInterface::class, $sut->getFilter('col_a'));
    }
}
