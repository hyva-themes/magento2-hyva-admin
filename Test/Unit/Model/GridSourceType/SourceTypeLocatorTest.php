<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Unit\Model\GridSourceType;

use Hyva\Admin\Model\GridSourceType\ArrayProviderGridSourceType;
use Hyva\Admin\Model\GridSourceType\CollectionGridSourceType;
use Hyva\Admin\Model\GridSourceType\QueryGridSourceType;
use Hyva\Admin\Model\GridSourceType\RepositoryGridSourceType;
use Hyva\Admin\Model\GridSourceType\SourceTypeClassLocator;
use PHPUnit\Framework\TestCase;

class SourceTypeLocatorTest extends TestCase
{
    public function testThrowsExceptionOnUnknownType(): void
    {
        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage('Unknown HyvaGrid source type on grid "test-grid": "foo"');
        (new SourceTypeClassLocator())->getFor('test-grid', ['@type' => 'foo']);
    }
    public function testThrowsExceptionIfNoSourceIsConfigured(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No source type configuration found for grid "test-grid"');
        (new SourceTypeClassLocator())->getFor('test-grid', []);
    }

    /**
     * @dataProvider sourceTypeProvider
     */
    public function testDeterminesTypeByPresentConfig(array $config, string $expected): void
    {
        $this->assertSame($expected, (new SourceTypeClassLocator())->getFor('test-grid', $config));
    }

    public function sourceTypeProvider(): array
    {
        return [
            'repo'                  => [['repositoryListMethod' => 'FooRepositoryInterface'], RepositoryGridSourceType::class],
            'collection'            => [['collection' => 'BarCollection'], CollectionGridSourceType::class],
            'query'                 => [['query' => []], QueryGridSourceType::class],
            'array'                 => [
                ['arrayProvider' => 'HyvaGridArrayProviderInterface'],
                ArrayProviderGridSourceType::class,
            ],
            'type-takes-precedence' => [
                ['@type' => 'repository', 'collection' => 'FooCollection'],
                RepositoryGridSourceType::class,
            ],
        ];
    }

}
