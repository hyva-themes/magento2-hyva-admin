<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Integration\Model\TypeReflection;

use Magento\Framework\Exception\NoSuchEntityException;
use PHPUnit\Framework\TestCase;

class TypeMethodTestClass
{
    public function returnEmptyArray(): array
    {
        return [];
    }

    public function returnZero(): int
    {
        return 0;
    }

    public function returnOne(): int
    {
        return 1;
    }

    public function throwNoSuchEntityException(): void
    {
        throw new NoSuchEntityException();
    }

    public function expectsSecondArgToBeZero($foo, $bar): bool
    {
        if ($bar !== 0) {
            TestCase::fail('Expected $bar to be zero, got "' . $bar . '"');
        }
        return true;
    }

    public function expectsFirstArgToBeNull($foo, $bar): bool
    {
        if (!is_null($foo)) {
            TestCase::fail('Expected $foo to be null, got "' . $foo . '"');
        }
        return true;
    }

    public function expectsFirstArgToBeDefault($foo = 'default', $bar = 0): bool
    {
        if ($foo !== 'default') {
            TestCase::fail('Expected $foo to be "default", got "' . $foo . '"');
        }
        return true;
    }
}
