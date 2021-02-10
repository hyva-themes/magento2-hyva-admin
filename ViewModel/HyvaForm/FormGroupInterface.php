<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel\HyvaForm;

interface FormGroupInterface
{
    public function getId(): string;

    public function getLabel(): string;

    public function hasLabel(): bool;

    /**
     * @return FormFieldDefinitionInterface[]
     */
    public function getFields(): array;

    public function getHtml(): string;
}
