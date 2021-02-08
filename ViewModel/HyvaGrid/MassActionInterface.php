<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel\HyvaGrid;

interface MassActionInterface
{
    public function getUrl(): string;

    public function getLabel(): string;

    public function requireConfirmation(): bool;

    public function getId(): string;
}
