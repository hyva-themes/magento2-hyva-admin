<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Unit\Model;

use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterface;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;

class ConstraintContainsColumnTest extends TestCase
{
    public function testReportsExpectedAndActualPropertiesForMismatchedColumn(): void
    {
        $expected = $this->createStub(ColumnDefinitionInterface::class);
        $expected->method('getKey')->willReturn('name');
        $expected->method('toArray')->willReturn(['key' => 'name', 'label' => 'Expected label']);
        $actual = $this->createStub(ColumnDefinitionInterface::class);
        $actual->method('getKey')->willReturn('name');
        $actual->method('toArray')->willReturn(['key' => 'name', 'label' => 'Actual label']);

        try {
            (new ConstraintContainsColumn([$actual]))->evaluate($expected);
            self::fail('Expected a column mismatch.');
        } catch (ExpectationFailedException $exception) {
            self::assertStringContainsString('Expected label', $exception->getMessage());
            self::assertStringContainsString('Actual label', $exception->getMessage());
        }
    }

    public function testReportsMissingColumn(): void
    {
        $expected = $this->createStub(ColumnDefinitionInterface::class);
        $expected->method('getKey')->willReturn('missing');

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('column "missing" exists');

        (new ConstraintContainsColumn([]))->evaluate($expected);
    }
}
