<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Functional\Model\GridSourceType;

use Hyva\Admin\Model\DataType\LongTextDataType;
use Hyva\Admin\Model\DataType\ScalarAndNullDataType;
use Hyva\Admin\Model\GridSourceType\ArrayProviderGridSourceType;
use Hyva\Admin\Test\Functional\TestingGridDataProvider;
use Magento\Framework\Api\SearchCriteria;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea adminhtml
 */
class ArrayProviderGridSourceTypeTest extends TestCase
{
    private function createArrayProviderGridSourceTypeWithArray(array $testGridData): ArrayProviderGridSourceType
    {
        $name                = 'test-grid';
        $sourceConfiguration = ['arrayProvider' => TestingGridDataProvider::withArray($testGridData)];

        $constructorArguments = ['gridName' => $name, 'sourceConfiguration' => $sourceConfiguration];
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

    public function testThrowsExceptionWhenUnableToExtractValue(): void
    {
        $sut = $this->createArrayProviderGridSourceTypeWithArray([]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No column value "bar"');

        $sut->extractValue([], 'bar');
    }

    public function testExtractsBasicColumnDefinition(): void
    {
        $value = 123;

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
        $this->assertSame(LongTextDataType::TYPE_LONG_TEXT, $sut->getColumnDefinition('0')->getType());
        $this->assertSame(LongTextDataType::TYPE_LONG_TEXT, $sut->getColumnDefinition('1')->getType());
    }

    public function testReturnsAndExtractsGridData(): void
    {
        $testGridData = [
            ['aaa' => 111, 'bbb' => 222],
            ['aaa' => 111, 'bbb' => 222, 'ccc' => 333],
        ];

        $sut = $this->createArrayProviderGridSourceTypeWithArray($testGridData);
        $rawDataContainer = $sut->fetchData(new SearchCriteria());
        $actualData = $sut->extractRecords($rawDataContainer);

        $this->assertSame($testGridData, $actualData);
    }
}
