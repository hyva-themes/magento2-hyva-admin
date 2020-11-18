<?php declare(strict_types=1);

namespace Hyva\Admin\Api;

interface DataTypeGuesserInterface
{
    public function typeOf($value): ?string;
}
