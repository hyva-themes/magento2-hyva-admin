<?php declare(strict_types=1);

namespace Hyva\Admin\Api;

interface DataTypeValueToStringConverterInterface
{
    public const UNLIMITED_RECURSION = -1;

    public function toString($value): ?string;

    public function toHtmlRecursive($value, $maxRecursionDepth = self::UNLIMITED_RECURSION): ?string;
}
