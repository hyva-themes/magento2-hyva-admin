<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel\HyvaGrid;

interface ColumnDefinitionInterface
{
    public function getLabel(): string;

    public function getKey(): string;

    public function getType(): ?string;

    public function getRenderAsUnsecureHtml(): bool;

    public function getTemplate(): ?string;

    public function toArray(): array;

    public function getOptionArray(): array;
}
