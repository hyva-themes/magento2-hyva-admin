<?php declare(strict_types=1);

namespace Hyva\Admin\Model\GridExport\Type;

use Hyva\Admin\Model\GridExport\HyvaGridExportInterface;
use function array_map as map;

use Hyva\Admin\Model\GridExport\ExportTypeInterface;
use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterface;
use Hyva\Admin\ViewModel\HyvaGridInterface;
use Magento\Framework\App\Filesystem\DirectoryList;

abstract class AbstractExportType implements ExportTypeInterface
{
    /**
     * @var HyvaGridExportInterface
     */
    private $grid;

    /**
     * @var string
     */
    private $fileName;

    /**
     * @var string
     */
    private $contentType = 'application/octet-stream';

    public function __construct(HyvaGridExportInterface $grid, string $fileName)
    {
        $this->grid     = $grid;
        $this->fileName = $fileName;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function getContentType(): string
    {
        return $this->contentType;
    }

    public function getExportDir(): string
    {
        return DirectoryList::VAR_DIR;
    }

    protected function getGrid(): HyvaGridExportInterface
    {
        return $this->grid;
    }

    protected function getHeaderData(): array
    {
        return map(function (ColumnDefinitionInterface $column): string {
            return $column->getLabel();
        }, $this->grid->getColumnDefinitions());
    }

    protected function iterateGrid(): \Iterator
    {
        $searchCriteria = $this->grid->getSearchCriteria();
        $searchCriteria->setPageSize(200);
        $searchCriteria->setCurrentPage(1);
        $current = 0;
        do {
            foreach ($this->grid->getRowsForSearchCriteria($searchCriteria) as $row) {
                yield $current++ => $row;
            }
            $searchCriteria->setCurrentPage($searchCriteria->getCurrentPage() + 1);
        } while ($current < $this->grid->getTotalRowsCount());
    }

    abstract public function createFileToDownload(): void;
}
