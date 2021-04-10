<?php declare(strict_types=1);

namespace Hyva\Admin\Model\GridExport\Type;

use function array_map as map;

use Hyva\Admin\ViewModel\HyvaGrid\CellInterface;
use Hyva\Admin\ViewModel\HyvaGrid\RowInterface;
use Hyva\Admin\ViewModel\HyvaGridInterface;
use Hyva\Admin\Model\GridExport\Source\GridSourceIteratorFactory;
use Magento\Framework\Convert\Excel;
use Magento\Framework\Filesystem;
use Magento\Framework\Convert\ExcelFactory;

class Xml extends AbstractExportType
{
    /**
     * @var string
     */
    private $defaultFileName = 'export/export.xml';

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var ExcelFactory
     */
    private $excelFactory;

    /**
     * @var GridSourceIteratorFactory
     */
    private $gridSourceIteratorFactory;

    public function __construct(
        Filesystem $filesystem,
        GridSourceIteratorFactory $gridSourceIteratorFactory,
        ExcelFactory $excelFactory,
        HyvaGridInterface $grid,
        string $fileName = ''
    ) {
        parent::__construct($grid, $fileName ?: $this->defaultFileName);
        $this->filesystem                = $filesystem;
        $this->excelFactory              = $excelFactory;
        $this->gridSourceIteratorFactory = $gridSourceIteratorFactory;
    }

    public function createFileToDownload(): void
    {
        $file      = $this->getFileName();
        $directory = $this->filesystem->getDirectoryWrite($this->getExportDir());
        $iterator  = $this->gridSourceIteratorFactory->create(['grid' => $this->getGrid()]);

        /** @var Excel $excel */
        $excel = $this->excelFactory->create([
            'iterator'    => $iterator,
            'rowCallback' => function (RowInterface $data): array {
                return $this->getRowData($data);
            },
        ]);

        $stream = $directory->openFile($file, 'w+');
        $stream->lock();
        $excel->setDataHeader($this->getHeaderData());
        $excel->write($stream, $this->getGrid()->getGridName());
        $stream->unlock();
        $stream->close();
    }

    private function getRowData(RowInterface $row): array
    {
        return map(function (CellInterface $column): string {
            return $column->getTextValue();
        }, $row->getCells());
    }

}
