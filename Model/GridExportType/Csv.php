<?php

namespace Hyva\Admin\Model\GridExportType;

use Hyva\Admin\ViewModel\HyvaGrid\CellInterface;
use Hyva\Admin\ViewModel\HyvaGridInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;

class Csv extends AbstractExportType
{

    private string $fileName = "export/export.csv";

    private Filesystem $filesystem;

    private SourceIteratorFactory $sourceIteratorFactory;


    public function __construct( Filesystem $filesystem, SourceIteratorFactory $sourceIteratorFactory, HyvaGridInterface $grid, string $fileName = "")
    {
        parent::__construct($grid, $fileName ?: $this->fileName);
        $this->filesystem = $filesystem;
        $this->sourceIteratorFactory = $sourceIteratorFactory;
    }

    public function create()
    {
        $file = $this->getFileName();
        $directory = $this->filesystem->getDirectoryWrite($this->getRootDir());
        $stream = $directory->openFile($file, 'w+');
        $iterator = $this->sourceIteratorFactory->create(['grid' => $this->getGrid()]);
        $stream->lock();
        $addHeader = true;
        foreach($iterator as $row){
            if ($addHeader) {
                $stream->writeCsv($this->getHeaderData());
                $addHeader = false;
            }
            $stream->writeCsv(
                array_map(function (CellInterface $cell) {
                    return $cell->getTextValue();
                }, $row->getCells())
            );
        }
        $stream->unlock();
        $stream->close();
    }

}