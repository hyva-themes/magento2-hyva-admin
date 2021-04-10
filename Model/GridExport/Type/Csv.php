<?php declare(strict_types=1);

namespace Hyva\Admin\Model\GridExport\Type;

use function array_map as map;

use Hyva\Admin\Model\GridExport\Source\GridSourceIteratorFactory;
use Hyva\Admin\ViewModel\HyvaGrid\CellInterface;
use Hyva\Admin\ViewModel\HyvaGridInterface;
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

    /**
     * @var GridSourceIteratorFactory
     */
    private $gridSourceIteratorFactory;

    public function __construct(
        Filesystem $filesystem,
        GridSourceIteratorFactory $gridSourceIteratorFactory,
        HyvaGridInterface $grid,
        string $fileName = ''
    ) {
        parent::__construct($grid, $fileName ?: $this->defaultFileName);
        $this->filesystem                = $filesystem;
        $this->gridSourceIteratorFactory = $gridSourceIteratorFactory;
    }

    public function createFileToDownload(): void
    {
        $file      = $this->getFileName();
        $directory = $this->filesystem->getDirectoryWrite($this->getExportDir());
        $stream    = $directory->openFile($file, 'w+');
        $iterator  = $this->gridSourceIteratorFactory->create(['grid' => $this->getGrid()]);
        $stream->lock();
        $stream->writeCsv($this->getHeaderData());
        foreach ($iterator as $row) {
            $stream->writeCsv(map(function (CellInterface $cell): string {
                return $cell->getTextValue();
            }, $row->getCells()));
        }
        $stream->unlock();
        $stream->close();
    }

}
