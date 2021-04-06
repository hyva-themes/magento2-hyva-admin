<?php

namespace Hyva\Admin\Model\GridExport\Type;

use Hyva\Admin\ViewModel\HyvaGrid\CellInterface;
use Hyva\Admin\ViewModel\HyvaGrid\RowInterface;
use Hyva\Admin\ViewModel\HyvaGridInterface;
use Hyva\Admin\Model\GridExport\Source\GridSourceIteratorFactory;
use Magento\Framework\Convert\Excel;
use Magento\Framework\Filesystem;
use Magento\Framework\Convert\ExcelFactory;

class Xml extends AbstractType
{

    private string $fileName = "export/export.xml";

    private Filesystem $filesystem;

    private ExcelFactory $excelFactory;

    private GridSourceIteratorFactory $gridSourceIteratorFactory;

    public function __construct(
        Filesystem $filesystem,
        GridSourceIteratorFactory $gridSourceIteratorFactory,
        ExcelFactory $excelFactory,
        HyvaGridInterface $grid,
        string $fileName = ""
    ) {
        parent::__construct($grid, $fileName ?: $this->fileName);
        $this->filesystem = $filesystem;
        $this->excelFactory = $excelFactory;
        $this->gridSourceIteratorFactory = $gridSourceIteratorFactory;
    }

    public function createFileToDownload()
    {
        $file = $this->getFileName();
        $directory = $this->filesystem->getDirectoryWrite($this->getRootDir());
        $iterator = $this->gridSourceIteratorFactory->create(['grid' => $this->getGrid()]);

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