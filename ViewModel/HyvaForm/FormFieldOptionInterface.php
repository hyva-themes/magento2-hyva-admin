<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel\HyvaForm;

interface FormFieldOptionInterface
{
    public function getValue(): string;

    public function getLabel(): string;
}
