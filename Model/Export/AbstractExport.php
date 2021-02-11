<?php

namespace Hyva\Admin\Model\Export;

use Hyva\Admin\Model\ExportInterface;
use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterface;
use Hyva\Admin\ViewModel\HyvaGridInterface;
use Magento\Framework\App\Filesystem\DirectoryList;

abstract class AbstractExport implements ExportInterface
{
    protected HyvaGridInterface $grid;
    protected string $fileName;
    protected string $metaType = 'application/octet-stream';


    public function getFileName() :string
    {
        return $this->fileName;
    }

    public function getMetaType() : string
    {
        return $this->metaType;
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

    public function setMetaType($metaType) : ExportInterface
    {
        $this->metaType = $metaType;
       return $this;
    }

    protected function getHeaderData(){
        return array_map(function (ColumnDefinitionInterface $column) {
            return $column->getLabel();
        }, $this->getGrid()->getColumnDefinitions());
    }
}