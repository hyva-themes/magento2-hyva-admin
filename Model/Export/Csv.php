<?php
/**
 * Csv
 * @copyright Copyright © 2021 CopeX GmbH. All rights reserved.
 * @author    andreas.pointner@copex.io
 */

namespace Hyva\Admin\Model\Export;

use Hyva\Admin\ViewModel\HyvaGrid\CellInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;

class Csv extends AbstractExport
{

    protected $fileName = "export.csv";
    protected $directory;

    /**
     * @var Filesystem
     */
    private $filesystem;

    public function __construct( Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function create()
    {
        $navigation = $this->getGrid()->getNavigation();
        $pageCount = $navigation->getPageCount();
        $file = $this->getAbsoluteFileName();
        $this->directory = $this->filesystem->getDirectoryWrite($this->getRootDir());
        $stream = $this->directory->openFile($file, 'w+');
        $stream->lock();
        $addHeader = true;
        for ($i = 1; $i <= $pageCount; $i++) {
            $navigation->getSearchCriteria()->setCurrentPage($i);
            $rows = $this->getGrid()->getRows();
            foreach ($rows ?? [] as $row) {
                if ($addHeader) {
                    $stream->writeCsv(
                        array_map(function (CellInterface $cell) {
                            return $cell->getColumnDefinition()->getLabel();
                        }, $row->getCells())
                    );
                    $addHeader = false;
                }
                $stream->writeCsv(
                    array_map(function (CellInterface $cell) {
                        return $cell->getTextValue();
                    }, $row->getCells())
                );
            }
        }
        $stream->unlock();
        $stream->close();
    }

}