<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel\HyvaGrid;

class FilterOption implements FilterOptionInterface
{
    private string $label;

    private array $values;

    public function __construct(string $label, array $values)
    {
        $this->label = $label;
        $this->values = $values;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getValues(): array
    {
        return $this->values;
    }

    public function getValueId(): string
    {
        return md5($this->getLabel());
    }
}
