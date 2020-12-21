<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Integration;

use Hyva\Admin\Api\HyvaGridArrayProviderInterface;

class TestingGridDataProvider implements HyvaGridArrayProviderInterface
{
    private static $testGridData = [];

    public static function withArray(array $testGridData): string
    {
        self::$testGridData = $testGridData;
        return self::class;
    }

    public function getHyvaGridData(): array
    {
        $testGridData = self::$testGridData;
        return $testGridData;
    }

    public function reset(): void
    {
        self::$testGridData = [];
    }
}
