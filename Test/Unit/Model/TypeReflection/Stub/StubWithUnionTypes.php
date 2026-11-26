<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Unit\Model\TypeReflection\Stub;

/**
 * Union types require PHP 8.0. Only reference this class from tests that are skipped on older PHP versions.
 */
class StubWithUnionTypes
{
    public function getIntOrString(): int|string
    {
        return 1;
    }

    public function getObjectOrFalse(): \ArrayObject|false
    {
        return false;
    }

    public function getArrayOrNull(): array|null
    {
        return null;
    }

    public function setIntOrString(int|string $arg): void
    {
    }
}
