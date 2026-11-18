<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Unit\Model\TypeReflection\Stub;

/**
 * Intersection types require PHP 8.1. Only reference this class from tests that are skipped on older PHP versions.
 */
class StubWithIntersectionTypes
{
    public function getCountableIterator(): \Countable&\Iterator
    {
        return new \ArrayIterator([]);
    }
}
