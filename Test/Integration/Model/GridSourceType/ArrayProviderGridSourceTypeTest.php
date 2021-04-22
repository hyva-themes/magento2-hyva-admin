<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Integration\Model\GridSourceType;

use Hyva\Admin\Api\HyvaGridSourceProcessorInterface;
use Hyva\Admin\Model\DataType\ScalarAndNullDataType;
use Hyva\Admin\Model\DataType\TextDataType;
use Hyva\Admin\Model\GridSourceType\ArrayProviderGridSourceType;
use Hyva\Admin\Test\Integration\TestingGridDataProvider;
use Magento\Framework\Api\Filter;
use Magento\Framework\Api\Search\FilterGroup;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea adminhtml
 */
class ArrayProviderGridSourceTypeTest extends TestCase
{
    private function createArrayProviderGridSourceTypeWithArray(
        array $testGridData,
        array $processors = []
    ): ArrayProviderGridSourceType {
        $name                = 'test-grid';
        $sourceConfiguration = ['arrayProvider' => TestingGridDataProvider::withArray($testGridData)];

        $constructorArguments = [
            'gridName'            => $name,
            'sourceConfiguration' => $sourceConfiguration,
            'processors'          => $processors,
        ];
        return ObjectManager::getInstance()->create(ArrayProviderGridSourceType::class, $constructorArguments);
    }

    public function testReturnsTheColumnKeysOfTheFirstRow(): void
    {
        $sut = $this->createArrayProviderGridSourceTypeWithArray([
            ['aaa' => 111, 'bbb' => 222],
            ['aaa' => 111, 'bbb' => 222, 'ccc' => 333],
        ]);
        $this->assertSame(['aaa', 'bbb'], $sut->getColumnKeys());
    }

    public function testExtractsValueFromGivenArray(): void
    {
        $sut   = $this->createArrayProviderGridSourceTypeWithArray([]);
        $value = new \stdClass();
        $this->assertSame($value, $sut->extractValue(['foo' => $value], 'foo'));
        $this->assertSame($value, $sut->extractValue(['bar' => $value], 'bar'));
    }

    public function testReturnsNullWhenUnableToExtractValue(): void
    {
        $sut = $this->createArrayProviderGridSourceTypeWithArray([]);

        $this->assertNull($sut->extractValue([], 'bar'));
    }

    public function testExtractsBasicColumnDefinition(): void
    {
        $value = null;

        $key = 'x';
        $sut = $this->createArrayProviderGridSourceTypeWithArray([[$key => $value]]);

        $columnDefinition = $sut->getColumnDefinition($key);

        $this->assertSame($key, $columnDefinition->getKey());
        $this->assertSame(ScalarAndNullDataType::TYPE_SCALAR_NULL, $columnDefinition->getType());
    }

    public function testHandlesNumericColumnKeysGracefully(): void
    {
        $rowWithNumericKeys = ['aaa', 'bbb'];
        $sut                = $this->createArrayProviderGridSourceTypeWithArray([$rowWithNumericKeys]);
        $this->assertSame([0, 1], $sut->getColumnKeys());
        $this->assertSame(TextDataType::TYPE_TRUNCATED_TEXT, $sut->getColumnDefinition('0')->getType());
        $this->assertSame(TextDataType::TYPE_TRUNCATED_TEXT, $sut->getColumnDefinition('1')->getType());
    }

    public function testReturnsAndExtractsGridData(): void
    {
        $testGridData = [
            ['aaa' => 111, 'bbb' => 222],
            ['aaa' => 111, 'bbb' => 222, 'ccc' => 333],
        ];

        $sut              = $this->createArrayProviderGridSourceTypeWithArray($testGridData);
        $rawDataContainer = $sut->fetchData(new SearchCriteria());
        $actualData       = $sut->extractRecords($rawDataContainer);

        $this->assertSame($testGridData, $actualData);
    }

    public function testCountsWithFiltersApplied(): void
    {
        $testGridData   = [
            ['aaa' => 111, 'bbb' => 222, 'ccc' => 999],
            ['aaa' => 111, 'bbb' => 222, 'ccc' => 333],
        ];
        $filter         = new Filter(['field' => 'ccc', 'value' => 999, 'condition_type' => 'eq']);
        $filterGroup    = new FilterGroup(['filters' => [$filter]]);
        $searchCriteria = new SearchCriteria(['filter_groups' => [$filterGroup]]);

        $sut              = $this->createArrayProviderGridSourceTypeWithArray($testGridData);
        $rawDataContainer = $sut->fetchData($searchCriteria);
        $count            = $sut->extractTotalRowCount($rawDataContainer);
        $this->assertSame(1, $count);
    }

    public function testAppliesProcessors(): void
    {
        $testGridData = [
            ['aaa' => 111, 'bbb' => 222],
            ['aaa' => 111, 'bbb' => 222],
        ];

        $processor = new class() implements HyvaGridSourceProcessorInterface {
            public function beforeLoad($source, SearchCriteriaInterface $searchCriteria, string $gridName): void
            {
                $searchCriteria->setPageSize(1);
            }

            public function afterLoad($rawResult, SearchCriteriaInterface $searchCriteria, string $gridName)
            {
                $rawResult[0]['aaa'] = $rawResult[0]['aaa'] + 1;

                return $rawResult;
            }
        };

        $sut              = $this->createArrayProviderGridSourceTypeWithArray($testGridData, [$processor]);
        $rawDataContainer = $sut->fetchData(new SearchCriteria());
        $actualData       = $sut->extractRecords($rawDataContainer);

        $this->assertSame([['aaa' => 112, 'bbb' => 222]], $actualData);
    }
}
