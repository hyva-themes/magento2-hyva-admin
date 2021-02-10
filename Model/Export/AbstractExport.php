<?php
/**
 * Csv
 * @copyright Copyright © 2021 CopeX GmbH. All rights reserved.
 * @author    andreas.pointner@copex.io
 */

namespace Hyva\Admin\Model\Export;

use Hyva\Admin\Model\ExportInterface;
use Hyva\Admin\ViewModel\HyvaGridInterface;
use Magento\Framework\App\Filesystem\DirectoryList;

abstract class AbstractExport implements ExportInterface
{
    /**
     * @var HyvaGridInterface
     */
    protected $grid;
    protected $fileName;
    protected $metaType = 'application/octet-stream';


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
        return DirectoryList::VAR_DIR . DIRECTORY_SEPARATOR . "export";
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

    public function getAbsoluteFileName() : string
    {
        return $this->getRootDir() . DIRECTORY_SEPARATOR . $this->getFileName();
    }
}