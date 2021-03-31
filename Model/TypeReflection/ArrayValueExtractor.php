<?php declare(strict_types=1);

namespace Hyva\Admin\Model\TypeReflection;

use function array_keys as keys;

class ArrayValueExtractor
{
    public function forArray(array $array): array
    {
        return keys($array);
    }

    /**
     * @param array $array
     * @param string $code
     * @return mixed
     */
    public function getValue(array $array, string $code)
    {
        return $array[$code];
    }
}
