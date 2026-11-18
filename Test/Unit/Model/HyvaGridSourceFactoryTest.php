<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Unit\Model;

use Hyva\Admin\Model\GridSourceType\SourceTypeClassLocator;
use Hyva\Admin\Model\HyvaGridDefinitionInterface;
use Hyva\Admin\Model\HyvaGridSourceFactory;
use Magento\Framework\ObjectManagerInterface;
use PHPUnit\Framework\TestCase;

class HyvaGridSourceFactoryTest extends TestCase
{
    public function testThrowsExceptionIfSourceConfigIsEmpty(): void
    {
        $dummyObjectManager = $this->createStub(ObjectManagerInterface::class);
        $dummySourceTypeLocator = $this->createStub(SourceTypeClassLocator::class);
        $dummyProcessorBuilder = $this->createStub(\Hyva\Admin\Model\GridSource\GridSourceProcessorBuilder::class);
        $stubGridDefinition = $this->createStub(HyvaGridDefinitionInterface::class);
        $stubGridDefinition->method('getSourceConfig')->willReturn([]);
        $stubGridDefinition->method('getName')->willReturn('test-grid');
        $sut = new HyvaGridSourceFactory($dummyObjectManager, $dummySourceTypeLocator, $dummyProcessorBuilder);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No grid source configuration found for grid "test-grid"');

        $sut->createFor($stubGridDefinition);
    }
}
