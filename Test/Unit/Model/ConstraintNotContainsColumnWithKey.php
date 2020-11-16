<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Unit\Model;

class ConstraintNotContainsColumnWithKey extends ConstraintContainsColumn
{
    /**
     * @param string $key
     * @return bool
     */
    protected function matches($key): bool
    {
        return ! $this->hasColumnWithKey($key);
    }

    public function toString(): string
    {
        return 'is not contained in column list';
    }

    protected function failureDescription($other): string
    {
        return sprintf('column "%s" ', $other) . $this->toString();
    }

}
