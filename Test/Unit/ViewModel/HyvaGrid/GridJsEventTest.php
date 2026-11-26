<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Unit\ViewModel\HyvaGrid;

use Hyva\Admin\ViewModel\HyvaGrid\GridActionInterface;
use Hyva\Admin\ViewModel\HyvaGrid\GridJsEvent;
use Hyva\Admin\ViewModel\HyvaGrid\RowInterface;
use PHPUnit\Framework\TestCase;

class GridJsEventTest extends TestCase
{
    public function testEncodesParamsJsonForUseInHtml(): void
    {
        $value = "x' \"y\" &quot; <script>&amp;";

        $stubAction = $this->createStub(GridActionInterface::class);
        $stubAction->method('getId')->willReturn('edit');
        $stubAction->method('getParams')->willReturn(['sku' => $value]);

        $event = new GridJsEvent('click', 'test-grid', 'edit');
        $json  = $event->getParamsJson($stubAction, $this->createStub(RowInterface::class));

        foreach (["'", '&', '<', '>'] as $char) {
            $this->assertStringNotContainsString($char, $json);
        }
        $this->assertSame(10, substr_count($json, '"'), 'Only the JSON syntax may contain double quotes');
        $this->assertSame(['action' => 'edit', 'params' => ['sku' => $value]], json_decode($json, true));
    }
}
