<?php

namespace Hyva\Admin\Model\GridExport\Type;

use Hyva\Admin\Model\GridExport\Source\GridSourceIteratorFactory;
use Hyva\Admin\ViewModel\HyvaGrid\CellInterface;
use Hyva\Admin\ViewModel\HyvaGridInterface;
use Magento\Framework\Filesystem;

class Csv extends AbstractType
{

    private string $fileName = "export/export.csv";

    private Filesystem $filesystem;

    private GridSourceIteratorFactory $gridSourceIteratorFactory;


    public function __construct( Filesystem $filesystem, GridSourceIteratorFactory $gridSourceIteratorFactory, HyvaGridInterface $grid, string $fileName = "")
    {
        parent::__construct($grid, $fileName ?: $this->fileName);
        $this->filesystem = $filesystem;
        $this->gridSourceIteratorFactory = $gridSourceIteratorFactory;
    }

    public function createFileToDownload()
    {
        $file = $this->getFileName();
        $directory = $this->filesystem->getDirectoryWrite($this->getRootDir());
        $stream = $directory->openFile($file, 'w+');
        $iterator = $this->gridSourceIteratorFactory->create(['grid' => $this->getGrid()]);
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