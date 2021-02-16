<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel\HyvaForm;

interface FormSectionInterface
{
    public function getId(): string;

    /**
     * @return FormGroupInterface[]
     */
    public function getGroups(): array;

    public function getHtml(): string;

    public function getLabel(): ?string;
}
