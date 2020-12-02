<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel\HyvaGrid;

interface FilterOptionInterface
{
    public function getLabel(): string;

    public function getValues(): array;

    public function getValueId(): string;
}
