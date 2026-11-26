<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Integration;

use Hyva\Admin\Api\HyvaGridArrayProviderInterface;

class TestingGridDataProvider implements HyvaGridArrayProviderInterface
{
    private static $testGridData = [];

    private static $loadCount = 0;

    public static function withArray(array $testGridData): string
    {
        self::$testGridData = $testGridData;
        self::$loadCount    = 0;
        return self::class;
    }

    public static function getLoadCount(): int
    {
        return self::$loadCount;
    }

    public function getHyvaGridData(): array
    {
        self::$loadCount++;
        $testGridData = self::$testGridData;
        return $testGridData;
    }

    public function reset(): void
    {
        self::$testGridData = [];
        self::$loadCount    = 0;
    }
}
