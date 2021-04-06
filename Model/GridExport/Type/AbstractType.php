<?php

namespace Hyva\Admin\Model\GridExport\Type;

use Hyva\Admin\Model\GridExport\TypeInterface;
use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterface;
use Hyva\Admin\ViewModel\HyvaGridInterface;
use Magento\Framework\App\Filesystem\DirectoryList;

abstract class AbstractType implements TypeInterface
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

    public function getGrid() : HyvaGridInterface
    {
       return $this->grid;
    }

    protected function getHeaderData(){
        return array_map(function (ColumnDefinitionInterface $column) {
            return $column->getLabel();
        }, $this->getGrid()->getColumnDefinitions());
    }

    abstract public function createFileToDownload();
}