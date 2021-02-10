<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel\HyvaGrid;

interface ColumnDefinitionInterface
{
    public function getLabel(): string;

    public function getKey(): string;

    public function getType(): ?string;

    public function getRenderAsUnsecureHtml(): bool;

    public function getRendererBlockName(): ?string;

    public function getTemplate(): ?string;

    public function getOptionArray(): array;

    public function getSortOrder(): int;

    public function isSortable(): bool;

    public function isVisible(): bool;

    public function isInitiallyHidden(): bool;

    public function toArray(): array;

    /**
     * @param array|ColumnDefinitionInterface $definition
     * @return ColumnDefinitionInterface
     */
    public function merge($definition): ColumnDefinitionInterface;
}
