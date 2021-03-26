<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel\HyvaForm;

interface FormGroupInterface
{
    public const DEFAULT_GROUP_ID = '';

    public const DEFAULT_GROUP_NAME = 'Additional';

    public function getId(): string;

    public function getLabel(): string;

    /**
     * @return FormFieldDefinitionInterface[]
     */
    public function getFields(): array;

    public function getHtml(): string;

    public function getSectionId(): string;

    public function getSortOrder(): int;
}
