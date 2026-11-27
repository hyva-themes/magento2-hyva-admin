<?php declare(strict_types=1);

namespace Hyva\Admin\Model\GridExport\Type;

use function array_map as map;

use Hyva\Admin\Model\GridExport\HyvaGridExportInterface;
use Hyva\Admin\ViewModel\HyvaGrid\CellInterface;
use Magento\Framework\Filesystem;

class Csv extends AbstractExportType
{
    /**
     * Spreadsheet apps evaluate CSV cells that start with one of these characters as formulas.
     */
    private const FORMULA_TRIGGER_CHARS = ['=', '+', '-', '@', "\t", "\r"];

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
        $stream->writeCsv(map([$this, 'neutralizeFormula'], $this->getHeaderData()));
        foreach ($this->iterateGrid() as $row) {
            $stream->writeCsv(map(function (CellInterface $cell): string {
                return $this->neutralizeFormula($cell->getTextValue());
            }, $row->getCells()));
        }
        $stream->unlock();
        $stream->close();
    }

    /**
     * Prefix values that a spreadsheet app would evaluate as a formula with a single quote.
     *
     * Numbers like -5 or +1.5 are not formulas, so they are written unchanged.
     */
    private function neutralizeFormula(string $value): string
    {
        if ($value === '' || !in_array($value[0], self::FORMULA_TRIGGER_CHARS, true)) {
            return $value;
        }
        if (($value[0] === '-' || $value[0] === '+') && is_numeric($value)) {
            return $value;
        }

        return "'" . $value;
    }

}
