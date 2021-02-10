<?php
/**
 * Xml
 * @copyright Copyright © 2021 CopeX GmbH. All rights reserved.
 * @author    andreas.pointner@copex.io
 */

namespace Hyva\Admin\Model\Export;

use Hyva\Admin\ViewModel\HyvaGrid\CellInterface;
use Magento\Framework\Api\Search\DocumentInterface;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\Convert\Excel;
use Magento\Framework\Filesystem;
use Magento\Framework\Convert\ExcelFactory;

class Xml extends AbstractExport
{

    protected $fileName = "export.xml";

    protected $directory;

    /**
     * @var Filesystem
     */
    private $filesystem;
    /**
     * @var ExcelFactory
     */
    private $excelFactory;

    public function __construct( Filesystem $filesystem, ExcelFactory $excelFactory)
    {
        $this->filesystem = $filesystem;
        $this->excelFactory = $excelFactory;
    }

    public function create()
    {
        $navigation = $this->getGrid()->getNavigation();
        $file = $this->getAbsoluteFileName();
        $this->directory = $this->filesystem->getDirectoryWrite($this->getRootDir());

        $navigation->getSearchCriteria()->

        /** @var SearchResultInterface $searchResult */
        $searchResult = $component->getContext()->getDataProvider()->getSearchResult();

        /** @var DocumentInterface[] $searchResultItems */
        $searchResultItems = $searchResult->getItems();

        /** @var Excel $excel */
        $excel = $this->excelFactory->create(
            [
                'iterator' => $searchResultIterator,
                'rowCallback'=> [$this, 'getRowData'],
            ]
        );

        $stream = $this->directory->openFile($file, 'w+');
        $stream->lock();
        $excel->setDataHeader($this->metadataProvider->getHeaders($component));
        $excel->write($stream, $this->getGrid()->getGridName());
        $stream->unlock();
        $stream->close();
        return;
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