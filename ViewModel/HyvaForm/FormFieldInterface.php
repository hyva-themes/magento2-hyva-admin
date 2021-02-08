<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel\HyvaForm;

interface FormFieldInterface
{
    public function getId(): string;

    public function getFormId(): string;

    public function getHtml(): string;

    /**
     * @return FormFieldOptionInterface[]
     */
    public function getOptions(): array;
}
