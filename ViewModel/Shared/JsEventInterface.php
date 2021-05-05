<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel\Shared;

use Hyva\Admin\ViewModel\HyvaGrid\GridActionInterface;
use Hyva\Admin\ViewModel\HyvaGrid\RowInterface;

interface JsEventInterface
{
    public function getEventName(): string;

    public function getOnTrigger(): string;

    public function getParamsJson(GridActionInterface $action, RowInterface $row): string;
}
