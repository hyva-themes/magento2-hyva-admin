<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Integration\Model\GridExport\Type;

use Hyva\Admin\Model\GridExport\Type\AbstractExportType;
use Hyva\Admin\Model\GridExport\Type\Xlsx;
use Hyva\Admin\Model\GridExport\Type\Xml;
use function array_combine as zip;
use function array_keys as keys;
use function array_map as map;

use Hyva\Admin\Model\GridExport\Type\Csv;
use Hyva\Admin\ViewModel\HyvaGrid\CellInterface;
use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterface;
use Hyva\Admin\ViewModel\HyvaGrid\RowInterface;
use Hyva\Admin\ViewModel\HyvaGridViewModel;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Filesystem;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Hyva\Admin\Model\GridExport\Type\AbstractExportType
 * @covers \Hyva\Admin\Model\GridExport\Type\Csv
 * @covers \Hyva\Admin\Model\GridExport\Type\Xlsx
 * @covers \Hyva\Admin\Model\GridExport\Type\Xml
 */
class ExportTypesTest extends TestCase
{
    private $testFilesToRemove = [];

    /**
     * @after
     */
    public function cleanUpTestExports(): void
    {
        map(function (string $fileName) {
            if (file_exists($fileName)) {
                unlink($fileName);
            }
        }, $this->testFilesToRemove);
    }

    private function createColumnsFromGrid(array $gridData): array
    {
        $firstRow   = $gridData[0];
        $columnKeys = keys($firstRow);

        return $this->createColumnsForKeys($columnKeys);
    }

    private function createColumnsForKeys(array $columnKeys): array
    {
        return zip($columnKeys, map([$this, 'createColumn'], $columnKeys));
    }

    private function createColumn(string $key): ColumnDefinitionInterface
    {
        return ObjectManager::getInstance()->create(ColumnDefinitionInterface::class, [
            'key'       => $key,
            'isVisible' => true,
        ]);
    }

    private function createRows(array $columns, array $rows): array
    {
        return map(function (array $row) use ($columns): RowInterface {
            return $this->createRow($columns, $row);
        }, $rows);
    }

    private function createRow(array $columns, array $row): RowInterface
    {
        $cells = map(function (string $key, $value) use ($columns): CellInterface {
            return $this->createCell($columns[$key], $value);
        }, keys($row), $row);

        return ObjectManager::getInstance()->create(RowInterface::class, ['cells' => $cells]);
    }

    private function createCell(ColumnDefinitionInterface $column, $value): CellInterface
    {
        return ObjectManager::getInstance()->create(CellInterface::class, [
            'value'            => $value,
            'columnDefinition' => $column,
        ]);
    }

    private function getExportFilePath(AbstractExportType $export): string
    {
        $fileSystem = ObjectManager::getInstance()->create(Filesystem::class);
        return $fileSystem->getDirectoryRead($export->getExportDir())->getAbsolutePath($export->getFileName());
    }

    private function createStubGrid(array $gridData, array $columns = null): HyvaGridViewModel
    {
        $columns  = $columns ?? $this->createColumnsFromGrid($gridData);
        $stubGrid = $this->createMock(HyvaGridViewModel::class);
        $stubGrid->method('getColumnDefinitions')->willReturn($columns);
        $stubGrid->method('getSearchCriteria')->willReturn(new SearchCriteria());
        $stubGrid->method('getTotalRowsCount')->willReturn(count($gridData));
        $stubGrid->method('getRowsForSearchCriteria')->willReturn($this->createRows($columns, $gridData));

        return $stubGrid;
    }

    public function testExportsCsv(): void
    {
        $gridData = [
            ['foo' => 'a', 'bar' => 'b'],
            ['foo' => 'c', 'bar' => 'd'],
        ];

        $csvExport = ObjectManager::getInstance()->create(Csv::class, ['grid' => $this->createStubGrid($gridData)]);

        $csvExport->createFileToDownload();

        $file = $this->getExportFilePath($csvExport);
        $this->assertFileExists($file);
        $exportData = explode(PHP_EOL, trim(file_get_contents($file)));

        $this->assertSame('Foo,Bar', $exportData[0] ?? '');
        $this->assertSame('a,b', $exportData[1] ?? '');
        $this->assertSame('c,d', $exportData[2] ?? '');

        $this->testFilesToRemove[] = $file;
    }

    public function testExportsXml(): void
    {
        if (!function_exists('simplexml_load_string') || !function_exists('json_encode')) {
            $this->markTestSkipped('PHP Extensions ext-simplexml and/or ext-json not installed');
        }

        $gridData = [
            ['foo' => 'a', 'bar' => 'b'],
            ['foo' => 'c', 'bar' => 'd'],
        ];

        $xmlExport = ObjectManager::getInstance()->create(Xml::class, ['grid' => $this->createStubGrid($gridData)]);

        $xmlExport->createFileToDownload();

        $file = $this->getExportFilePath($xmlExport);
        $this->assertFileExists($file);
        $data = json_decode(json_encode(simplexml_load_string(trim(file_get_contents($file)))));

        $this->assertSame('Foo', $data->Worksheet->Table->Row[0]->Cell[0]->Data);
        $this->assertSame('Bar', $data->Worksheet->Table->Row[0]->Cell[1]->Data);

        $this->assertSame('a', $data->Worksheet->Table->Row[1]->Cell[0]->Data);
        $this->assertSame('b', $data->Worksheet->Table->Row[1]->Cell[1]->Data);

        $this->assertSame('c', $data->Worksheet->Table->Row[2]->Cell[0]->Data);
        $this->assertSame('d', $data->Worksheet->Table->Row[2]->Cell[1]->Data);

        $this->testFilesToRemove[] = $file;
    }

    public function testExportsXlsx(): void
    {
        if (
            !class_exists(\ZipArchive::class) ||
            !function_exists('simplexml_load_string') ||
            !function_exists('json_encode')
        ) {
            $this->markTestSkipped('PHP Extensions ext-zip, ext-simplexml and/or ext-json not installed');
        }

        $gridData = [
            ['foo' => 'a', 'bar' => 'b'],
            ['foo' => 'c', 'bar' => 'd'],
        ];

        $xlsxExport = ObjectManager::getInstance()->create(Xlsx::class, ['grid' => $this->createStubGrid($gridData)]);

        $xlsxExport->createFileToDownload();

        $file = $this->getExportFilePath($xlsxExport);
        $this->assertFileExists($file);

        $this->testFilesToRemove[] = $file;
    }

    public function testIteratesOverAllPagesDuringExport(): void
    {
        $exportPageSize = 200;

        $gridData = map(function (int $v): array {
            return ['val' => $v];
        }, range(1, (int) (2.5 * $exportPageSize))); // 2.5 pages

        $csvExport = ObjectManager::getInstance()->create(Csv::class, ['grid' => $this->createStubGrid($gridData)]);

        $csvExport->createFileToDownload();

        $file = $this->getExportFilePath($csvExport);
        $this->assertFileExists($file);
        $exportData = explode(PHP_EOL, trim(file_get_contents($file)));

        $this->assertCount(count($gridData) + 1, $exportData); // export rows plus header row
        $this->assertSame('Val', $exportData[0] ?? ''); // first row
        $this->assertSame((string) end($gridData)['val'], end($exportData)); // last row

        $this->testFilesToRemove[] = $file;
    }

    public function testExportsOnlyHeadersForEmptyGrid(): void
    {
        $gridData  = [];
        $columns   = $this->createColumnsForKeys(['aaa', 'bbb', 'ccc']);
        $grid      = $this->createStubGrid($gridData, $columns);
        $csvExport = ObjectManager::getInstance()->create(Csv::class, ['grid' => $grid]);

        $csvExport->createFileToDownload();

        $file = $this->getExportFilePath($csvExport);
        $this->assertFileExists($file);
        $exportData = explode(PHP_EOL, trim(file_get_contents($file)));

        $this->assertCount(1, $exportData);
        $this->assertSame('Aaa,Bbb,Ccc', $exportData[0] ?? '');

        $this->testFilesToRemove[] = $file;
    }
}
