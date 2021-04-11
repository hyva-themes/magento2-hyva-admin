<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel\HyvaGrid;

class GridExport implements GridExportInterface
{
    /**
     * @var string
     */
    private $type;

    /**
     * @var string|null
     */
    private $label;

    /**
     * @var string|null
     */
    private $fileName;

    /**
     * @var string|null
     */
    private $sortOrder;

    /**
     * @var string|null
     */
    private $class;

    public function __construct(
        string $type,
        ?string $label = null,
        ?string $fileName = null,
        ?string $class = null,
        ?string $sortOrder = null
    ) {
        $this->type      = $type;
        $this->label     = $label;
        $this->fileName  = $fileName;
        $this->sortOrder = $sortOrder;
        $this->class     = $class;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getLabel(): string
    {
        return $this->label ?? (string) __('Export as %1', mb_strtoupper($this->getType()));
    }

    public function getFileName(): ?string
    {
        return $this->fileName;
    }

    public function getClass(): ?string
    {
        return $this->class;
    }
}
