<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel\HyvaForm;

interface FormButtonInterface
{
    public function getHtml(): string;

    public function getLabel(): string;
}
