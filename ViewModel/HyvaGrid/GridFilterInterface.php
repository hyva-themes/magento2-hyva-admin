<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel\HyvaGrid;

interface GridFilterInterface
{
    public function getColumnDefinition(): ColumnDefinitionInterface;

    public function getHtml(): string;

    public function isEnabled(): bool;

    public function getInputType(): string;

    /**
     * @return array[]|null
     */
    public function getOptions(): ?array;

    public function getInputName(string $aspect = null): string;

    public function getValue(): ?string;
}
