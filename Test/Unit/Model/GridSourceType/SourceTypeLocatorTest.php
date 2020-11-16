<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Unit\Model\GridSourceType;

use Hyva\Admin\Model\GridSourceType\ArrayProviderGridSourceType;
use Hyva\Admin\Model\GridSourceType\CollectionGridSourceType;
use Hyva\Admin\Model\GridSourceType\QueryGridSourceType;
use Hyva\Admin\Model\GridSourceType\RepositoryGridSourceType;
use Hyva\Admin\Model\GridSourceType\SourceTypeLocator;
use PHPUnit\Framework\TestCase;

class SourceTypeLocatorTest extends TestCase
{
    public function testThrowsExceptionOnUnknownType(): void
    {
        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage('Unknown HyvaGrid source type on grid "test-grid": "foo"');
        (new SourceTypeLocator())->getFor('test-grid', ['@type' => 'foo']);
    }

    /**
     * @dataProvider sourceTypeProvider
     */
    public function testDeterminesTypeByPresentConfig(array $config, string $expected): void
    {
        $this->assertSame($expected, (new SourceTypeLocator())->getFor('test-grid', $config));
    }

    public function sourceTypeProvider(): array
    {
        return [
            'repo'                  => [['repository' => 'FooRepositoryInterface'], RepositoryGridSourceType::class],
            'collection'            => [['collection' => 'BarCollection'], CollectionGridSourceType::class],
            'query'                 => [['query' => []], QueryGridSourceType::class],
            'array'                 => [
                ['array' => 'HyvaGridArrayProviderInterface'],
                ArrayProviderGridSourceType::class,
            ],
            'type-takes-precedence' => [
                ['@type' => 'repository', 'collection' => 'FooCollection'],
                RepositoryGridSourceType::class,
            ],
        ];
    }

}
