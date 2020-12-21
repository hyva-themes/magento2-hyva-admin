<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Unit\Model\TypeReflection;

use Hyva\Admin\Model\TypeReflection\NamespaceMap;
use PHPUnit\Framework\TestCase;

class NamespaceMapTest extends TestCase
{
    public function testReturnsArray(): void
    {
        $map = NamespaceMap::forFile(__FILE__);

        $this->assertSame(self::class, $map->qualify('NamespaceMapTest'));
        $this->assertSame(__NAMESPACE__ . '\Foo', $map->qualify('Foo'));
        $this->assertSame(TestCase::class, $map->qualify('TestCase'));
        $this->assertSame(TestCase::class . '\Foo', $map->qualify('TestCase\Foo'));
    }
}
