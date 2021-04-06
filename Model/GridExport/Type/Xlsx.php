<?php
/**
 *
 * @thanks to https://gist.github.com/kasparsd/ade34dd94a80b97fb9ec59391a0c620f
 */
namespace Hyva\Admin\Model\GridExport\Type;

use Hyva\Admin\ViewModel\HyvaGrid\CellInterface;
use Hyva\Admin\ViewModel\HyvaGridInterface;
use Magento\Framework\Filesystem;

class Xlsx extends AbstractType
{
    /**
     * @var string
     */
    private $fileName = "export/export.xslx";

    private $sharedStrings = [];
    /**
     * @var Filesystem
     */
    private $fileSystem;
    /**
     * @var SourceIteratorFactory
     */
    private $sourceIteratorFactory;

    public function __construct(
        Filesystem $filesystem,
        SourceIteratorFactory $sourceIteratorFactory,
        HyvaGridInterface $grid,
        string $fileName = ""
    ) {
        parent::__construct($grid, $fileName);
        $this->fileSystem = $filesystem;
        $this->sourceIteratorFactory = $sourceIteratorFactory;
    }

    public function createFileToDownload()
    {
        $filename = $this->getFileName();
        $read = $this->fileSystem->getDirectoryRead($this->getRootDir());
        $rootPath = $read->getAbsolutePath($filename);
        $zip = new \ZipArchive();
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

    private function getWorkbookXml()
    {
        return sprintf(
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
			<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
			<sheets>
				<sheet name="Data" sheetId="1" r:id="rId1" />
			</sheets>
			</workbook>'
        );
    }

    private function getContentTypesXml()
    {
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

    private function getSharedStringsXml()
    {
        $sharedStrings = [];

        foreach ($this->sharedStrings as $string => $string_count) {
            $sharedStrings[] = sprintf(
                '<si><t>%s</t></si>',
                filter_var($string, FILTER_SANITIZE_SPECIAL_CHARS)
            );
        }

        return sprintf(
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
			<sst count="%d" uniqueCount="%d" xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
				%s
			</sst>',
            array_sum($this->sharedStrings),
            count($this->sharedStrings),
            implode("\n", $sharedStrings)
        );
    }

    private function getWorkbookRelsXml()
    {
        return sprintf(
            '<?xml version="1.0" encoding="UTF-8"?>
			<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
				<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
				<Relationship Id="rId4" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>
			</Relationships>'
        );
    }

    private function getAppXml()
    {
        return sprintf(
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
			<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">
				<Application>Microsoft Excel</Application>
			</Properties>'
        );
    }

    private function getCoreXml()
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

    private function getRelsXml()
    {
        return sprintf(
            '<?xml version="1.0" encoding="UTF-8"?>
			<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
				<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
				<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>
				<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>
			</Relationships>'
        );
    }

    private function getSheet()
    {
        $iterator = $this->sourceIteratorFactory->create(['grid' => $this->getGrid()]);
        $rows [] = $this->addRow(array_values($this->getHeaderData()), 0);
        foreach ($iterator as $rowNumber => $row) {
            $row = array_values(array_map(function (CellInterface $column) {
                return $column->getTextValue();
            }, $row->getCells()));
            $rows []= $this->addRow($row, $rowNumber+1);
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

    function getSharedStringNo($string)
    {
        static $stringPos = [];

        if (isset($this->shared_strings[$string])) {
            $this->sharedStrings[$string] += 1;
        } else {
            $this->sharedStrings[$string] = 1;
        }

        if (!isset($stringPos[$string])) {
            $stringPos[$string] = array_search($string, array_keys($this->sharedStrings));
        }

        return $stringPos[$string];
    }

    function getCellName($RowNumber, $ColumnNumber)
    {
        $n = $ColumnNumber;
        for ($r = ''; $n >= 0; $n = intval($n / 26) - 1) {
            $r = chr($n % 26 + 0x41) . $r;
        }
        return $r . ($RowNumber + 1);
    }

    /**
     * @param $row
     * @param                                             $rowNumber
     * @param array                                       $rows
     * @return string
     */
    private function addRow($row, $rowNumber)
    {
        $cells = [];
        foreach ($row as $colNumber => $fieldValue) {
            $fieldType = 's';
            $fieldValueNumber = $this->getSharedStringNo($fieldValue);
            $cells[] = sprintf(
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
}