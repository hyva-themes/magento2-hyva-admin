<?php declare(strict_types=1);

namespace Hyva\Admin\Model\GridSourceType;

use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class RepositoryGridSourceTypeTest extends TestCase
{
    public function testExtractsColumnKeys(): void
    {
        $repoGetListMethod  = 'Magento\Catalog\Api\ProductRepositoryInterface::getList';
        $args = [
            'gridName'            => 'test',
            'sourceConfiguration' => ['repositoryListMethod' => $repoGetListMethod],
        ];
        /** @var RepositoryGridSourceType $sut */
        $sut  = ObjectManager::getInstance()->create(RepositoryGridSourceType::class, $args);
        $keys = $sut->getColumnKeys();
        $this->assertNotEmpty($keys);
        $this->assertContains('id', $keys);
        $this->assertContains('sku', $keys);
        $this->assertContains('name', $keys);
    }
}
