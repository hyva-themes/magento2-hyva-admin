<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel\HyvaGrid;

class ColumnDefinition implements ColumnDefinitionInterface
{
    private string $key;

    private ?string $label;

    private ?string $type;

    public function __construct(string $key, ?string $label = null, ?string $type = null)
    {
        $this->key   = $key;
        $this->label = $label;
        $this->type  = $type;
    }

    public function camelCaseToWords(string $camel): string
    {
        return preg_replace('/([A-Z]+)/', ' $1', $camel);
    }

    public function snakeCaseToWords(string $snake): string
    {
        return str_replace('_', ' ', $snake);
    }

    public function collapseWhitespace(string $in): string
    {
        return preg_replace('/ {2,}/', ' ', $in);
    }

    public function getLabel(): string
    {
        return $this->label
            ? $this->label
            : ucwords(trim($this->collapseWhitespace($this->snakeCaseToWords($this->camelCaseToWords($this->key)))));
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getType(): string
    {
        return $this->type ?? 'string';
    }

    public function toArray(): array
    {
        return [
            'key'   => $this->getKey(),
            'label' => $this->getLabel(),
            'type'  => $this->getType(),
        ];
    }
}
