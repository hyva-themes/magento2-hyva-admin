<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel\HyvaGrid;

interface GridExportInterface
{
    public function getId(): string;

    public function getLabel(): string;

    public function getFileName(): string;
}
