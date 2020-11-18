<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel\HyvaGrid;

use Magento\Eav\Model\Entity\Attribute\Source\SourceInterface;
use Magento\Framework\ObjectManagerInterface;

class ColumnDefinition implements ColumnDefinitionInterface
{
    private ObjectManagerInterface $objectManager;

    private string $key;

    private ?string $label;

    private ?string $type;

    private ?string $renderer;

    private ?string $source;

    private ?array $options;

    public function __construct(
        ObjectManagerInterface $objectManager,
        string $key,
        ?string $label = null,
        ?string $type = null,
        ?string $renderer = null,
        ?string $source = null,
        ?array $options = null
    ) {
        $this->key           = $key;
        $this->label         = $label;
        $this->type          = $type;
        $this->renderer      = $renderer;
        $this->source        = $source;
        $this->options       = $options;
        $this->objectManager = $objectManager;
    }

    private function camelCaseToWords(string $camel): string
    {
        return preg_replace('/([A-Z]+)/', ' $1', $camel);
    }

    private function snakeCaseToWords(string $snake): string
    {
        return str_replace('_', ' ', $snake);
    }

    private function collapseWhitespace(string $in): string
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
        return $this->type ?? 'unknown';
    }

    public function toArray(): array
    {
        return [
            'key'      => $this->getKey(),
            'label'    => $this->getLabel(),
            'type'     => $this->getType(),
            'renderer' => $this->getRenderer(),
            'source'   => $this->source,
            'options'  => $this->options,
        ];
    }

    public function getRenderer(): ?string
    {
        return $this->renderer;
    }

    public function getOptionArray(): array
    {
        return $this->options ?? ($this->hasSourceModel() ? $this->createSourceModel()->toOptionArray() : []);
    }

    private function createSourceModel(): ?SourceInterface
    {
        return $this->hasSourceModel() ? $this->objectManager->get($this->source) : null;
    }

    private function hasSourceModel(): bool
    {
        return isset($this->source) && $this->source;
    }
}
