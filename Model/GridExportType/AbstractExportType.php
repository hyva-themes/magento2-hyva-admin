<?php

namespace Hyva\Admin\Model\GridExportType;

use Hyva\Admin\Model\ExportInterface;
use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterface;
use Hyva\Admin\ViewModel\HyvaGridInterface;
use Magento\Framework\App\Filesystem\DirectoryList;

abstract class AbstractExportType implements ExportInterface
{
    private HyvaGridInterface $grid;
    private string $fileName;
    private string $contentType = 'application/octet-stream';

    public function __construct(HyvaGridInterface $grid, string $fileName) {
        $this->grid = $grid;
        if ($fileName) {
            $this->fileName = $fileName;
        }
    }

    public function getFileName() :string
    {
        return $this->fileName;
    }

    public function getContentType() : string
    {
        return $this->contentType;
    }

    public function getRootDir() :string
    {
        return DirectoryList::VAR_DIR;
    }

    public function setGrid(HyvaGridInterface $grid) : ExportInterface
    {
        $this->grid = $grid;
        return $this;
    }

    public function getGrid() : HyvaGridInterface
    {
       return $this->grid;
    }

    public function setFileName($fileName) : ExportInterface
    {
        if ($fileName) {
            $this->fileName = $fileName;
        }
        return $this;
    }

    protected function getHeaderData(){
        return array_map(function (ColumnDefinitionInterface $column) {
            return $column->getLabel();
        }, $this->getGrid()->getColumnDefinitions());
    }
}