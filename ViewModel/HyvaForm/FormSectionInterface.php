<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel\HyvaForm;

interface FormSectionInterface
{
    public const DEFAULT_SECTION_ID = '';
    public const DEFAULT_SECTION_LABEL = 'Additional';

    public function getId(): string;

    /**
     * @return FormGroupInterface[]
     */
    public function getGroups(): array;

    public function hasOnlyDefaultGroup(): bool;

    public function getHtml(): string;

    public function getLabel(): string;
}
