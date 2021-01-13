<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel\HyvaGrid;

interface GridButtonInterface
{
    public function getHtml(): string;

    public function getId(): string;

    public function getLabel(): string;

    public function getUrl(): string;

    public function getOnClick(): ?string;
}
