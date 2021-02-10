<?php
/**
 * Xml
 * @copyright Copyright © 2021 CopeX GmbH. All rights reserved.
 * @author    andreas.pointner@copex.io
 */

namespace Hyva\Admin\Model\Export;

use Hyva\Admin\ViewModel\HyvaGrid\CellInterface;
use Hyva\Admin\ViewModel\HyvaGrid\RowInterface;
use Magento\Framework\Convert\Excel;
use Magento\Framework\Filesystem;
use Magento\Framework\Convert\ExcelFactory;

class Xml extends AbstractExport
{

    protected $fileName = "export/export.xlsx";

    protected $directory;

    /**
     * @var Filesystem
     */
    private $filesystem;
    /**
     * @var ExcelFactory
     */
    private $excelFactory;
    /**
     * @var SourceIteratorFactory
     */
    private $sourceIteratorFactory;

    public function __construct(
        Filesystem $filesystem,
        SourceIteratorFactory $sourceIteratorFactory,
        ExcelFactory $excelFactory
    ) {
        $this->filesystem = $filesystem;
        $this->excelFactory = $excelFactory;
        $this->sourceIteratorFactory = $sourceIteratorFactory;
    }

    public function create()
    {
        $file = $this->getFileName();
        $this->directory = $this->filesystem->getDirectoryWrite($this->getRootDir());
        $iterator = $this->sourceIteratorFactory->create(['grid' => $this->getGrid()]);

        /** @var Excel $excel */
        $excel = $this->excelFactory->create(
            [
                'iterator' => $iterator,
                'rowCallback' => [$this, 'getRowData'],
            ]
        );

        $stream = $this->directory->openFile($file, 'w+');
        $stream->lock();
        $excel->setDataHeader($this->getHeaderData());
        $excel->write($stream, $this->getGrid()->getGridName());
        $stream->unlock();
        $stream->close();
    }

    public function getRowData(RowInterface $row): array
    {
        return array_map(function (CellInterface $column) {
            return $column->getTextValue();
        }, $row->getCells());
    }

}