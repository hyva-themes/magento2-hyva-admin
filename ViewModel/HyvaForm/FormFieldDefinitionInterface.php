<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel\HyvaForm;

interface FormFieldDefinitionInterface
{
    public function getName(): string;

    public function getLabel(): string;

    public function getHtml(): string;

    public function getContentHtml(): string;

    /**
     * @return mixed
     */
    public function getValue();

    /**
     * @return array[]
     */
    public function getOptions(): array;

    public function getGroupId(): string;

    public function getInputType(): string;

    public function isDisabled(): bool;

    public function getFormName(): string;

    public function getSortOrder(): ?int;

    public function toArray(): array;

    public function merge(FormFieldDefinitionInterface $field): FormFieldDefinitionInterface;

    public function getPattern(): ?string;

    public function isRequired(): bool;

    public function getMinlength(): ?int;

    public function getMaxlength(): ?int;

    public function getMin(): ?string;

    public function getMax(): ?string;

    public function getStep(): ?int;

    public function isHidden(): ?bool;
}
