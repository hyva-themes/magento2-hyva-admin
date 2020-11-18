<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel\HyvaGrid;

class MassAction implements MassActionInterface
{

    private string $url;

    private string $label;

    private bool $requireConfirmation;

    public function __construct(string $url, string $label, bool $requireConfirmation = false)
    {
        $this->url                 = $url;
        $this->label               = $label;
        $this->requireConfirmation = $requireConfirmation;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function requireConfirmation(): bool
    {
        return $this->requireConfirmation;
    }
}
