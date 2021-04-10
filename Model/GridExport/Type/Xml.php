<?php declare(strict_types=1);

namespace Hyva\Admin\Model\GridExport\Type;

use function array_map as map;

use Hyva\Admin\Model\GridExport\HyvaGridExportInterface;
use Hyva\Admin\ViewModel\HyvaGrid\CellInterface;
use Hyva\Admin\ViewModel\HyvaGrid\RowInterface;
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

    public function __construct(
        Filesystem $filesystem,
        ExcelFactory $excelFactory,
        HyvaGridExportInterface $grid,
        string $fileName = ''
    ) {
        parent::__construct($grid, $fileName ?: $this->defaultFileName);
        $this->filesystem   = $filesystem;
        $this->excelFactory = $excelFactory;
    }

    public function createFileToDownload(): void
    {
        $file      = $this->getFileName();
        $directory = $this->filesystem->getDirectoryWrite($this->getExportDir());

        /** @var Excel $excel */
        $excel = $this->excelFactory->create([
            'iterator'    => $this->iterateGrid(),
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
