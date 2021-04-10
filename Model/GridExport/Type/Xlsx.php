<?php declare(strict_types=1);

/*
 * Thanks to https://gist.github.com/kasparsd/ade34dd94a80b97fb9ec59391a0c620f
 */

namespace Hyva\Admin\Model\GridExport\Type;

use function array_keys as keys;
use function array_map as map;
use function array_search as search;
use function array_sum as sum;
use function array_values as values;

use Hyva\Admin\Model\GridExport\HyvaGridExportInterface;
use Hyva\Admin\ViewModel\HyvaGrid\CellInterface;
use Magento\Framework\Filesystem;

class Xlsx extends AbstractExportType
{
    /**
     * @var string
     */
    private $defaultFileName = 'export/export.xlsx';

    /**
     * @var array
     */
    private $sharedStrings = [];

    /**
     * @var Filesystem
     */
    private $fileSystem;

    public function __construct(
        Filesystem $filesystem,
        HyvaGridExportInterface $grid,
        string $fileName = ''
    ) {
        $this->validateZipExtensionInstalled();

        parent::__construct($grid, $fileName ?: $this->defaultFileName);
        $this->fileSystem = $filesystem;
    }

    public function createFileToDownload(): void
    {
        $filename = $this->getFileName();
        $read     = $this->fileSystem->getDirectoryRead($this->getExportDir());
        $rootPath = $read->getAbsolutePath($filename);
        $zip      = new \ZipArchive();
        if ($zip->open($rootPath, \ZipArchive::CREATE)) {
            $zip->addEmptyDir('docProps');
            $zip->addFromString('docProps/app.xml', $this->getAppXml());
            $zip->addFromString('docProps/core.xml', $this->getCoreXml());
            $zip->addEmptyDir('_rels');
            $zip->addFromString('_rels/.rels', $this->getRelsXml());
            $zip->addEmptyDir('xl/worksheets');
            $zip->addFromString('xl/worksheets/sheet1.xml', $this->getSheet());
            $zip->addFromString('xl/workbook.xml', $this->getWorkbookXml());
            $zip->addFromString('xl/sharedStrings.xml', $this->getSharedStringsXml());
            $zip->addEmptyDir('xl/_rels');
            $zip->addFromString('xl/_rels/workbook.xml.rels', self::getWorkbookRelsXml());
            $zip->addFromString('[Content_Types].xml', $this->getContentTypesXml());
            $zip->close();
        }
    }

    private function getWorkbookXml(): string
    {
        // refactor: heredoc?
        return sprintf(
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
			<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
			<sheets>
				<sheet name="Data" sheetId="1" r:id="rId1" />
			</sheets>
			</workbook>'
        );
    }

    private function getContentTypesXml(): string
    {
        // refactor: heredoc?
        return sprintf(
            '<?xml version="1.0" encoding="UTF-8"?>
			<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
				<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
				<Default Extension="xml" ContentType="application/xml"/>
				<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
				<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
				<Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>
				<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>
				<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>
			</Types>'
        );
    }

    private function getSharedStringsXml(): string
    {
        $sharedStrings = map(function (string $string) {
            return sprintf(
                '<si><t>%s</t></si>',
                filter_var($string, FILTER_SANITIZE_SPECIAL_CHARS)
            );
        }, keys($this->sharedStrings));

        return sprintf(
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
			<sst count="%d" uniqueCount="%d" xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
				%s
			</sst>',
            sum($this->sharedStrings),
            count($this->sharedStrings),
            implode("\n", $sharedStrings)
        );
    }

    private function getWorkbookRelsXml(): string
    {
        // refactor: heredoc?
        return sprintf(
            '<?xml version="1.0" encoding="UTF-8"?>
			<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
				<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
				<Relationship Id="rId4" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>
			</Relationships>'
        );
    }

    private function getAppXml(): string
    {
        // refactor: heredoc?
        return sprintf(
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
			<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">
				<Application>Microsoft Excel</Application>
			</Properties>'
        );
    }

    private function getCoreXml(): string
    {
        return sprintf(
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
			<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
				<dcterms:created xsi:type="dcterms:W3CDTF">%s</dcterms:created>
				<dc:creator>Preseto</dc:creator>
			</cp:coreProperties>',
            date('Y-m-d\TH:i:s.00\Z')
        );
    }

    private function getRelsXml(): string
    {
        // refactor: heredoc?
        return sprintf(
            '<?xml version="1.0" encoding="UTF-8"?>
			<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
				<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
				<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>
				<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>
			</Relationships>'
        );
    }

    private function getSheet(): string
    {
        $rows[] = $this->buildRow(values($this->getHeaderData()), 0);
        foreach ($this->iterateGrid() as $rowNumber => $row) {
            $row = values(map(function (CellInterface $column): string {
                return $column->getTextValue();
            }, $row->getCells()));

            $rows[] = $this->buildRow($row, $rowNumber + 1);
        }

        return sprintf(
            '<?xml version="1.0" encoding="utf-8" standalone="yes"?>
			<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
				<sheetData>
					%s
				</sheetData>
			</worksheet>',
            implode("\n", $rows)
        );
    }

    public function getSharedStringNo(string $string): int
    {
        static $stringPos = [];

        $this->sharedStrings[$string] = ($this->sharedStrings[$string] ?? 0) + 1;

        if (!isset($stringPos[$string])) {
            $stringPos[$string] = search($string, keys($this->sharedStrings));
        }

        return $stringPos[$string];
    }

    public function getCellName(int $rowNumber, int $columnNumber): string
    {
        for ($n = $columnNumber, $r = ''; $n >= 0; $n = intval($n / 26) - 1) {
            $r = chr($n % 26 + 0x41) . $r;
        }
        return $r . ($rowNumber + 1);
    }

    private function buildRow(array $row, int $rowNumber): string
    {
        $cells = [];
        foreach ($row as $colNumber => $fieldValue) {
            $fieldType        = 's';
            $fieldValueNumber = $this->getSharedStringNo($fieldValue);
            $cells[]          = sprintf(
                '<c r="%s" t="%s"><v>%d</v></c>',
                $this->getCellName($rowNumber, $colNumber),
                $fieldType,
                $fieldValueNumber
            );
        }
        return sprintf(
            '<row r="%s">
					%s
				</row>',
            $rowNumber + 1,
            implode("\n", $cells)
        );
    }

    private function validateZipExtensionInstalled(): void
    {
        if (!class_exists(\ZipArchive::class)) {
            throw new \RuntimeException(sprintf('Unable to use Xlsx export type because the required PHP extension ext-zip is not installed.'));
        }
    }
}
