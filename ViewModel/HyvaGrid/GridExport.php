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
    private $enabled;

    /**
     * @var string|null
     */
    private $template;

    /**
     * @var string|null
     */
    private $fileName;

    /**
     * @var string|null
     */
    private $sortOrder;

    public function __construct(
        string $type,
        ?string $label,
        ?string $fileName = null,
        ?string $enabled = null,
        ?string $template = null,
        ?string $sortOrder = null
    ) {
        $this->type      = $type;
        $this->label     = $label;
        $this->enabled   = $enabled;
        $this->template  = $template;
        $this->fileName  = $fileName;
        $this->sortOrder = $sortOrder;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getLabel(): string
    {
        return (string) $this->label;
    }

    public function getFileName(): string
    {
        return (string) $this->fileName;
    }

    public function getClass(): ?string
    {
        // refactor: add class prop
        return null;
    }
}
