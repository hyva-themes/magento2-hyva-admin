<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Functional\ViewModel\HyvaGrid;

use Hyva\Admin\Model\GridSourceType\ArrayProviderGridSourceType;
use Hyva\Admin\Model\HyvaGridSourceInterface;
use Hyva\Admin\Test\Functional\TestingGridDataProvider;
use Hyva\Admin\ViewModel\HyvaGrid\Navigation;
use Hyva\Admin\ViewModel\HyvaGrid\NavigationInterface;
use Magento\Backend\Model\UrlInterface as BackendUrlBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

use function array_filter as filter;

/**
 * @magentoAppArea adminhtml
 */
class NavigationTest extends TestCase
{
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

    public function testReturnsRequestedPageNumber(): void
    {
        $stubRequest = $this->createMock(RequestInterface::class);
        $stubRequest->method('getParam')->willReturnMap([['p', null, 3]]);

        $gridData         = [['id' => 'a'], ['id' => 'b'], ['id' => 'c']];
        $navigationConfig = ['pager' => ['defaultPageSize' => 1]];
        $sut              = $this->createNavigation($gridData, $navigationConfig, $stubRequest);

        $this->assertSame(3, $sut->getCurrentPageNumber());
    }

    public function testReturnsMaxPageNumberIfRequestIsLarger(): void
    {
        $stubRequest = $this->createMock(RequestInterface::class);
        $stubRequest->method('getParam')->willReturnMap([['p', null, 3]]);

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
        $stubRequest->method('getParam')->willReturnMap([['p', null, 2]]);

        $gridData         = [['id' => 'a'], ['id' => 'b']];
        $navigationConfig = ['pager' => ['defaultPageSize' => 1]];
        $sut              = $this->createNavigation($gridData, $navigationConfig, $stubRequest);

        $this->assertTrue($sut->hasPreviousPage());
    }

    public function testHasNoPreviousPageIfCurrentPageIsOne(): void
    {
        $stubRequest = $this->createMock(RequestInterface::class);
        $stubRequest->method('getParam')->willReturnMap([['p', null, 2]]);

        $gridData         = [['id' => 'a']];
        $navigationConfig = [];
        $sut              = $this->createNavigation($gridData, $navigationConfig, $stubRequest);

        $this->assertFalse($sut->hasPreviousPage());
    }

    public function testHasNextPageIfNotOnLastPage(): void
    {
        $stubRequest = $this->createMock(RequestInterface::class);
        $stubRequest->method('getParam')->willReturnMap([['p', null, 1]]);

        $gridData         = [['id' => 'a'], ['id' => 'b']];
        $navigationConfig = ['pager' => ['defaultPageSize' => 1]];
        $sut              = $this->createNavigation($gridData, $navigationConfig, $stubRequest);

        $this->assertTrue($sut->hasNextPage());
    }

    public function testHasNoNextPageOnLastPage(): void
    {
        $stubRequest = $this->createMock(RequestInterface::class);
        $stubRequest->method('getParam')->willReturnMap([['p', null, 1]]);

        $gridData         = [['id' => 'a'], ['id' => 'b']];
        $navigationConfig = ['pager' => ['defaultPageSize' => 2]];
        $sut              = $this->createNavigation($gridData, $navigationConfig, $stubRequest);

        $this->assertFalse($sut->hasNextPage());
    }

    public function testReturnsCurrentUrlAsPreviousPageUrlOnFirstPage(): void
    {
        $stubRequest = $this->createMock(RequestInterface::class);
        $stubRequest->method('getParam')->willReturnMap([['p', null, 1]]);

        $gridData         = [['id' => 'a'], ['id' => 'b']];
        $navigationConfig = ['pager' => ['defaultPageSize' => 1]];
        $sut              = $this->createNavigation($gridData, $navigationConfig, $stubRequest);

        $expected = $this->getUrlBuilder()->getUrl('*/*/*', ['_current' => true, 'p' => 1]);
        $this->assertSame($expected, $sut->getPreviousPageUrl());
    }

    public function testReturnsPreviousPageUrl(): void
    {
        $stubRequest = $this->createMock(RequestInterface::class);
        $stubRequest->method('getParam')->willReturnMap([['p', null, 2]]);

        $gridData         = [['id' => 'a'], ['id' => 'b']];
        $navigationConfig = ['pager' => ['defaultPageSize' => 1]];
        $sut              = $this->createNavigation($gridData, $navigationConfig, $stubRequest);

        $expected = $this->getUrlBuilder()->getUrl('*/*/*', ['_current' => true, 'p' => 1]);
        $this->assertSame($expected, $sut->getPreviousPageUrl());
    }

    public function testReturnsPreviousPageUrlIfRequestedPageIsBeyondMaxPage(): void
    {
        $stubRequest = $this->createMock(RequestInterface::class);
        $stubRequest->method('getParam')->willReturnMap([['p', null, 4]]);

        $gridData         = [['id' => 'a'], ['id' => 'b'], ['id' => 'c']];
        $navigationConfig = ['pager' => ['defaultPageSize' => 1]];
        $sut              = $this->createNavigation($gridData, $navigationConfig, $stubRequest);

        $expected = $this->getUrlBuilder()->getUrl('*/*/*', ['_current' => true, 'p' => 2]);
        $this->assertSame($expected, $sut->getPreviousPageUrl());
    }

    public function testReturnsCurrentUrlAsNextPageUrlOnLastPage(): void
    {
        $stubRequest = $this->createMock(RequestInterface::class);
        $stubRequest->method('getParam')->willReturnMap([['p', null, 2]]);

        $gridData         = [['id' => 'a'], ['id' => 'b']];
        $navigationConfig = ['pager' => ['defaultPageSize' => 1]];
        $sut              = $this->createNavigation($gridData, $navigationConfig, $stubRequest);

        $expected = $this->getUrlBuilder()->getUrl('*/*/*', ['_current' => true, 'p' => 2]);
        $this->assertSame($expected, $sut->getNextPageUrl());
    }

    public function testReturnsNextPageUrl(): void
    {
        $stubRequest = $this->createMock(RequestInterface::class);
        $stubRequest->method('getParam')->willReturnMap([['p', null, 2]]);

        $gridData         = [['id' => 'a'], ['id' => 'b'], ['id' => 'c']];
        $navigationConfig = ['pager' => ['defaultPageSize' => 1]];
        $sut              = $this->createNavigation($gridData, $navigationConfig, $stubRequest);

        $expected = $this->getUrlBuilder()->getUrl('*/*/*', ['_current' => true, 'p' => 3]);
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
        $stubRequest->method('getParam')->willReturnMap([['pageSize', null, 50]]);

        $gridData         = [['id' => 'a'], ['id' => 'b'], ['id' => 'c']];
        $navigationConfig = ['pager' => ['pageSizes' => '20, 50, 100']];
        $sut              = $this->createNavigation($gridData, $navigationConfig, $stubRequest);

        $this->assertSame(50, $sut->getPageSize());
    }

    public function testReturnsDefaultPageSizeRequestedPageSizeIsNotValid(): void
    {
        $stubRequest = $this->createMock(RequestInterface::class);
        $stubRequest->method('getParam')->willReturnMap([['pageSize', null, 30]]);

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
        $stubRequest->method('getParam')->willReturnMap([['pageSize', null, $pageSize]]);

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
        $stubRequest->method('getParam')->willReturnMap([['p', null, $currentPage], ['pageSize', null, 1]]);

        $gridData         = [['id' => 'a'], ['id' => 'b'], ['id' => 'c']];
        $navigationConfig = ['pager' => ['pageSizes' => '1, 20, 50, 100']];
        $sut              = $this->createNavigation($gridData, $navigationConfig, $stubRequest);

        $searchCriteria = $sut->getSearchCriteria();
        $this->assertSame($currentPage, $searchCriteria->getCurrentPage());
    }

    public function testSetsSortOrderSearchCriteria(): void
    {
        $stubRequest = $this->createMock(RequestInterface::class);
        $stubRequest->method('getParam')->willReturnMap([['sortBy', null, 'name'], ['sortDirection', null, 'asc']]);

        $gridData         = [['id' => 'a', 'name' => 'a'], ['id' => 'b', 'name' => 'b'], ['id' => 'c', 'name' => 'c']];
        $navigationConfig = [];
        $sut              = $this->createNavigation($gridData, $navigationConfig, $stubRequest);

        $searchCriteria = $sut->getSearchCriteria();
        $this->assertSame('name', $searchCriteria->getSortOrders()[0]->getField());
        $this->assertSame('ASC', $searchCriteria->getSortOrders()[0]->getDirection());
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
        $stubRequest->method('getParam')->willReturnMap([['sortBy', null, $sortCol]]);
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
        $stubRequest->method('getParam')->willReturnMap([['sortBy', null, 'col_invalid']]);
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
        $stubRequest = $this->createMock(RequestInterface::class);
        $stubRequest->method('getParam')->willReturnMap([['sortDirection', null, 'asc']]);
        $sut = $this->createNavigation($gridData, $navigationConfig, $stubRequest);

        $this->assertSame('asc', $sut->getSortDirection());
    }

    public function testReturnsSortByColumnUrl(): void
    {
        $stubRequest = $this->createMock(RequestInterface::class);
        $stubRequest->method('getParam')->willReturnMap([
            ['p', null, 2],
            ['sortBy', null, 'foo'],
            ['sortDirection', null, 'desc'],
        ]);

        $gridData         = [['id' => 'a'], ['id' => 'b']];
        $navigationConfig = [];
        $sut              = $this->createNavigation($gridData, $navigationConfig, $stubRequest);

        $expected = $this->getUrlBuilder()->getUrl('*/*/*', [
            '_current' => true,
            'p' => 1,
            'sortBy' => 'id',
            'sortDirection' => 'desc'
        ]);
        $this->assertSame($expected, $sut->getSortByUrl('id', 'desc'));
    }
}
