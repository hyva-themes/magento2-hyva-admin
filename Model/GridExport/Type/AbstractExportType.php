<?php declare(strict_types=1);

namespace Hyva\Admin\Model\GridExport\Type;

use function array_map as map;

use Hyva\Admin\Model\GridExport\ExportTypeInterface;
use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterface;
use Hyva\Admin\ViewModel\HyvaGridInterface;
use Magento\Framework\App\Filesystem\DirectoryList;

abstract class AbstractExportType implements ExportTypeInterface
{
    /**
     * @var HyvaGridInterface
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

    public function __construct(HyvaGridInterface $grid, string $fileName)
    {
        $this->grid = $grid;
        if ($fileName) {
            $this->fileName = $fileName;
        }
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function getContentType(): string
    {
        return $this->contentType;
    }

    public function getRootDir(): string
    {
        return DirectoryList::VAR_DIR;
    }

    public function getGrid(): HyvaGridInterface
    {
        return $this->grid;
    }

    protected function getHeaderData(): array
    {
        return map(function (ColumnDefinitionInterface $column): string {
            return $column->getLabel();
        }, $this->getGrid()->getColumnDefinitions());
    }

    abstract public function createFileToDownload(): void;
}
