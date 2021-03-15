<?php declare(strict_types=1);

namespace Hyva\Admin\Model\TypeReflection;

use function array_keys as keys;

class ArrayValueExtractor
{
    public function forArray(array $array): array
    {
        return keys($array);
    }
}
