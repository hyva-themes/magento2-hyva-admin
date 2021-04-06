<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Integration\Model\TypeReflection;

use Hyva\Admin\Model\TypeReflection\DbSelectColumnExtractor;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DataObject;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class DbSelectColumnExtractorTest extends TestCase
{
    public function testReturnsTableColumnsForWildcardSelect(): void
    {
        /** @var DbSelectColumnExtractor $sut */
        $sut = ObjectManager::getInstance()->create(DbSelectColumnExtractor::class);
        /** @var ResourceConnection $resourceConnection */
        $resourceConnection = ObjectManager::getInstance()->get(ResourceConnection::class);
        $select             = $resourceConnection->getConnection()->select();

        $select->from(['main_table' => 'catalog_product_entity'], '*');

        $columns = $sut->getSelectColumns($select);
        $this->assertContains('entity_id', $columns);
        $this->assertContains('attribute_set_id', $columns);
        $this->assertContains('sku', $columns);
    }

    public function testIncludesJoinedFieldsAsColumns(): void
    {
        /** @var DbSelectColumnExtractor $sut */
        $sut = ObjectManager::getInstance()->create(DbSelectColumnExtractor::class);
        /** @var ResourceConnection $resourceConnection */
        $resourceConnection = ObjectManager::getInstance()->get(ResourceConnection::class);
        $select             = $resourceConnection->getConnection()->select();

        $select->from(['main_table' => 'catalog_product_entity'], '*');
        $select->joinLeft(['cpei' => 'catalog_product_entity_int'], 'cpei.entity_id = main_table.entity_id', '*');

        $columns = $sut->getSelectColumns($select);
        $this->assertContains('entity_id', $columns);
        $this->assertContains('attribute_set_id', $columns);
        $this->assertContains('sku', $columns);
        $this->assertContains('store_id', $columns);
        $this->assertContains('value', $columns);
    }

    public function testIncludesOnlySpecifiedColumns(): void
    {
        /** @var DbSelectColumnExtractor $sut */
        $sut = ObjectManager::getInstance()->create(DbSelectColumnExtractor::class);
        /** @var ResourceConnection $resourceConnection */
        $resourceConnection = ObjectManager::getInstance()->get(ResourceConnection::class);
        $select             = $resourceConnection->getConnection()->select();

        $select->from('catalog_product_entity', ['sku', 'entity_id']);

        $this->assertSame(['sku', 'entity_id'], $sut->getSelectColumns($select));
    }

    public function testReturnsTheColumnnTypes(): void
    {
        /** @var DbSelectColumnExtractor $sut */
        $sut = ObjectManager::getInstance()->create(DbSelectColumnExtractor::class);
        /** @var ResourceConnection $resourceConnection */
        $resourceConnection = ObjectManager::getInstance()->get(ResourceConnection::class);
        $select             = $resourceConnection->getConnection()->select();

        $select->from(['main_table' => 'catalog_product_entity'], 'sku');
        $select->joinLeft(
            ['cpei' => 'catalog_product_entity_int'],
            'cpei.entity_id = main_table.entity_id',
            ['the_val' => 'value']
        );

        $columns = $sut->getSelectColumns($select);
        $this->assertSame(['sku', 'the_val'], $columns);

        $this->assertSame('varchar', $sut->getColumnType($select, 'sku'));
        $this->assertSame('int', $sut->getColumnType($select, 'the_val'));
    }

    public function testReturnsGivenKey(): void
    {
        /** @var DbSelectColumnExtractor $sut */
        $sut = ObjectManager::getInstance()->create(DbSelectColumnExtractor::class);

        $stub = new DataObject(['foo' => 'bar']);

        $this->assertSame('bar', $sut->extractColumnValue('foo', $stub));
    }
}
