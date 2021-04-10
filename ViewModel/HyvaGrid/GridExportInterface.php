<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel\HyvaGrid;

interface GridExportInterface
{
    public function getType(): string;

    public function getLabel(): string;

    public function getFileName(): ?string;

    public function getClass(): ?string;
}
