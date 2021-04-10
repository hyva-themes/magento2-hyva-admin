<?php declare(strict_types=1);

namespace Hyva\Admin\Model\GridExport\Type;

use function array_map as map;

use Hyva\Admin\Model\GridExport\HyvaGridExportInterface;
use Hyva\Admin\ViewModel\HyvaGrid\CellInterface;
use Magento\Framework\Filesystem;

class Csv extends AbstractExportType
{
    /**
     * @var string
     */
    private $defaultFileName = 'export/export.csv';

    /**
     * @var Filesystem
     */
    private $filesystem;

    public function __construct(
        Filesystem $filesystem,
        HyvaGridExportInterface $grid,
        string $fileName = ''
    ) {
        parent::__construct($grid, $fileName ?: $this->defaultFileName);
        $this->filesystem = $filesystem;
    }

    public function createFileToDownload(): void
    {
        $file      = $this->getFileName();
        $directory = $this->filesystem->getDirectoryWrite($this->getExportDir());
        $stream    = $directory->openFile($file, 'w+');
        $stream->lock();
        $stream->writeCsv($this->getHeaderData());
        foreach ($this->iterateGrid() as $row) {
            $stream->writeCsv(map(function (CellInterface $cell): string {
                return $cell->getTextValue();
            }, $row->getCells()));
        }
        $stream->unlock();
        $stream->close();
    }

}
