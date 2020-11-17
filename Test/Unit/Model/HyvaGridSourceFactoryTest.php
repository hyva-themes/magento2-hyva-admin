<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Unit\Model;

use Hyva\Admin\Model\GridSourceType\SourceTypeLocator;
use Hyva\Admin\Model\HyvaGridDefinitionInterface;
use Hyva\Admin\Model\HyvaGridSourceFactory;
use Magento\Framework\ObjectManagerInterface;
use PHPUnit\Framework\TestCase;

class HyvaGridSourceFactoryTest extends TestCase
{
    public function testThrowsExceptionIfSourceConfigIsEmpty(): void
    {
        $dummyObjectManager = $this->createMock(ObjectManagerInterface::class);
        $dummySourceTypeLocator = $this->createMock(SourceTypeLocator::class);
        $stubGridDefinition = $this->createMock(HyvaGridDefinitionInterface::class);
        $stubGridDefinition->method('getSourceConfig')->willReturn([]);
        $stubGridDefinition->method('getName')->willReturn('test-grid');
        $sut = new HyvaGridSourceFactory($dummyObjectManager, $dummySourceTypeLocator);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No grid source configuration found for grid "test-grid"');

        $sut->createFor($stubGridDefinition);
    }
}
