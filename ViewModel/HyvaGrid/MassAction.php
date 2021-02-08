<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel\HyvaGrid;

class MassAction implements MassActionInterface
{

    private string $id;

    private string $url;

    private string $label;

    private bool $requireConfirmation;

    public function __construct(string $id, string $url, string $label, bool $requireConfirmation = false)
    {
        $this->id = $id;
        $this->url                 = $url;
        $this->label               = $label;
        $this->requireConfirmation = $requireConfirmation;
    }

    public function getId(): string
    {
        return $this->id;
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
