<?php

namespace Hyva\Admin\Model\GridExportType;

use Hyva\Admin\ViewModel\HyvaGrid\CellInterface;
use Hyva\Admin\ViewModel\HyvaGrid\RowInterface;
use Hyva\Admin\ViewModel\HyvaGridInterface;
use Magento\Framework\Convert\Excel;
use Magento\Framework\Filesystem;
use Magento\Framework\Convert\ExcelFactory;
use Magento\Framework\Filesystem\Directory\WriteInterface;

class Xml extends AbstractExportType
{

    private string $fileName = "export/export.xlsx";

    private Filesystem $filesystem;

    private ExcelFactory $excelFactory;

    private SourceIteratorFactory $sourceIteratorFactory;

    public function __construct(
        Filesystem $filesystem,
        SourceIteratorFactory $sourceIteratorFactory,
        ExcelFactory $excelFactory,
        HyvaGridInterface $grid,
        string $fileName = ""
    ) {
        parent::__construct($grid, $fileName ?: $this->fileName);
        $this->filesystem = $filesystem;
        $this->excelFactory = $excelFactory;
        $this->sourceIteratorFactory = $sourceIteratorFactory;
    }

    public function create()
    {
        $file = $this->getFileName();
        $directory = $this->filesystem->getDirectoryWrite($this->getRootDir());
        $iterator = $this->sourceIteratorFactory->create(['grid' => $this->getGrid()]);

        /** @var Excel $excel */
        $excel = $this->excelFactory->create(
            [
                'iterator' => $iterator,
                'rowCallback' => function($data){ return $this->getRowData($data); },
            ]
        );

        $stream = $directory->openFile($file, 'w+');
        $stream->lock();
        $excel->setDataHeader($this->getHeaderData());
        $excel->write($stream, $this->getGrid()->getGridName());
        $stream->unlock();
        $stream->close();
    }

    private function getRowData(RowInterface $row): array
    {
        return array_map(function (CellInterface $column) {
            return $column->getTextValue();
        }, $row->getCells());
    }

}