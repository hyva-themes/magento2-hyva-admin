<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel\HyvaGrid;

class MassAction implements MassActionInterface
{
    /**
     * @var string
     */
    private $url;

    /**
     * @var string
     */
    private $label;

    /**
     * @var bool
     */
    private $requireConfirmation;

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
