<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel\HyvaForm;

interface FormButtonInterface
{
    public function getHtml(): string;

    public function getLabel(): string;
    public function getUrl(): ?string;
    public function getUseajax(): ?bool;
    public function getId(): ?string;
    public function getOnclick(): ?string;
}
