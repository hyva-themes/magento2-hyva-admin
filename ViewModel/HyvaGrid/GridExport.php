<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel\HyvaGrid;

class GridExport implements GridExportInterface
{
    /**
     * @var string
     */
    private $id;

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
        string $id,
        ?string $label,
        ?string $fileName = null,
        ?string $enabled = null,
        ?string $template = null,
        ?string $sortOrder = null
    ) {
        $this->id        = $id;
        $this->label     = $label;
        $this->enabled   = $enabled;
        $this->template  = $template;
        $this->fileName  = $fileName;
        $this->sortOrder = $sortOrder;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getLabel(): string
    {
        return (string) $this->label;
    }

    public function getFileName(): string
    {
        return (string) $this->fileName;
    }
}
