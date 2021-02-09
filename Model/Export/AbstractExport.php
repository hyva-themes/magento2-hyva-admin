<?php
/**
 * Csv
 * @copyright Copyright © 2021 CopeX GmbH. All rights reserved.
 * @author    andreas.pointner@copex.io
 */

namespace Hyva\Admin\Model\Export;

use Hyva\Admin\Model\ExportInterface;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\App\Filesystem\DirectoryList;

abstract class AbstractExport implements ExportInterface
{
    /**
     * @var SearchCriteria
     */
    protected $searchCriteria;
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
        return DirectoryList::VAR_DIR;
    }

    public function setSearchCriteria(SearchCriteria $searchCriteria) : ExportInterface
    {
        $this->searchCriteria = $searchCriteria;
        return $this;
    }

    public function getSearchCriteria() : SearchCriteria
    {
       return $this->searchCriteria;
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
}