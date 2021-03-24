<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel\HyvaForm;

class FormGroup implements FormGroupInterface
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var array
     */
    private $fields;

    /**
     * @var string|null
     */
    private $label;

    /**
     * @var string
     */
    private $sectionId;

    /**
     * @var int
     */
    private $sortOrder;

    public function __construct(string $id, array $fields, string $sectionId, int $sortOrder, ?string $label = null)
    {
        $this->id        = $id;
        $this->fields    = $fields;
        $this->label     = $label;
        $this->sectionId = $sectionId;
        $this->sortOrder = $sortOrder;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function hasLabel(): bool
    {
        // Todo: add logic to determine
        return isset($this->label);
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    public function getHtml(): string
    {

    }

    public function getSectionId(): string
    {
        return $this->sectionId;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }
}
