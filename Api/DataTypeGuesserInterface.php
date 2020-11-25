<?php declare(strict_types=1);

namespace Hyva\Admin\Api;

interface DataTypeGuesserInterface
{
    public function valueToTypeCode($value): ?string;

    public function typeToTypeCode(string $type): ?string;
}
