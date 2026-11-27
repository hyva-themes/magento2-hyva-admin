<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Integration;

use Hyva\Admin\Model\Config\GridConfigReader;
use Hyva\Admin\Model\Config\GridDefinitionConfigFiles;
use Hyva\Admin\Model\Config\HyvaGridDirs;
use Hyva\Admin\Model\GridAuthorization;
use Magento\TestFramework\ObjectManager;

/**
 * Makes the grid XML files in _files/hyva-grid available to the grid config reader.
 */
class TestingGridConfig
{
    public const GRID = 'endpoint-test-grid';

    public const ACL_GRID = 'endpoint-acl-test-grid';

    public const ACL_RESOURCE = 'Magento_Customer::manage';

    public static function install(): void
    {
        $objectManager = self::getObjectManager();

        $configFiles = new class($objectManager->get(HyvaGridDirs::class)) extends GridDefinitionConfigFiles {
            public function getConfigDefinitionFiles(string $configFileName): array
            {
                $file = __DIR__ . '/_files/hyva-grid/' . str_replace('/', '', $configFileName) . '.xml';
                return file_exists($file) ? [$file] : [];
            }
        };
        $reader = $objectManager->create(GridConfigReader::class, ['gridDefinitionConfigFiles' => $configFiles]);

        $objectManager->addSharedInstance($reader, GridConfigReader::class);
        $objectManager->removeSharedInstance(GridAuthorization::class);
    }

    public static function uninstall(): void
    {
        $objectManager = self::getObjectManager();
        $objectManager->removeSharedInstance(GridConfigReader::class);
        $objectManager->removeSharedInstance(GridAuthorization::class);
    }

    private static function getObjectManager(): ObjectManager
    {
        /** @var ObjectManager $objectManager */
        $objectManager = ObjectManager::getInstance();
        return $objectManager;
    }
}
