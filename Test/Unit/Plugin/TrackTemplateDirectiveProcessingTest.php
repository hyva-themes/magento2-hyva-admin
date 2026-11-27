<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Unit\Plugin;

use Hyva\Admin\Model\TemplateDirectiveState;
use Hyva\Admin\Plugin\TrackTemplateDirectiveProcessing;
use Magento\Framework\Filter\Template as TemplateFilter;
use PHPUnit\Framework\TestCase;

class TrackTemplateDirectiveProcessingTest extends TestCase
{
    public function testTracksDirectiveProcessingWhileFiltering(): void
    {
        $state  = new TemplateDirectiveState();
        $plugin = new TrackTemplateDirectiveProcessing($state);

        $result = $plugin->aroundFilter($this->createStub(TemplateFilter::class), function ($value) use ($state) {
            $this->assertTrue($state->isProcessingDirectives());
            return "filtered $value";
        }, 'value');

        $this->assertSame('filtered value', $result);
        $this->assertFalse($state->isProcessingDirectives());
    }

    public function testTracksNestedDirectiveProcessing(): void
    {
        $state  = new TemplateDirectiveState();
        $plugin = new TrackTemplateDirectiveProcessing($state);
        $filter = $this->createStub(TemplateFilter::class);

        $plugin->aroundFilter($filter, function () use ($plugin, $filter, $state) {
            $plugin->aroundFilter($filter, function () {
                return '';
            }, 'inner');
            $this->assertTrue($state->isProcessingDirectives());
            return '';
        }, 'outer');

        $this->assertFalse($state->isProcessingDirectives());
    }

    public function testStopsTrackingIfFilteringFails(): void
    {
        $state  = new TemplateDirectiveState();
        $plugin = new TrackTemplateDirectiveProcessing($state);

        try {
            $plugin->aroundFilter($this->createStub(TemplateFilter::class), function () {
                throw new \RuntimeException('test');
            }, 'value');
            $this->fail('Expected exception');
        } catch (\RuntimeException $exception) {
            $this->assertSame('test', $exception->getMessage());
        }

        $this->assertFalse($state->isProcessingDirectives());
    }
}
