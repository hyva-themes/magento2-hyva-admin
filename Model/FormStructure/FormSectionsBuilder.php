<?php declare(strict_types=1);

namespace Hyva\Admin\Model\FormStructure;

use function array_column as pick;
use function array_combine as zip;
use function array_keys as keys;
use function array_map as map;
use function array_merge as merge;
use function array_reduce as reduce;

use Hyva\Admin\ViewModel\HyvaForm\FormGroupInterface;
use Hyva\Admin\ViewModel\HyvaForm\FormSectionInterface;
use Hyva\Admin\ViewModel\HyvaForm\FormSectionInterfaceFactory;

class FormSectionsBuilder
{
    /**
     * @var FormSectionInterfaceFactory
     */
    private $formSectionFactory;

    public function __construct(FormSectionInterfaceFactory $formSectionFactory)
    {
        $this->formSectionFactory = $formSectionFactory;
    }

    public function buildSections(string $formName, array $sectionConfig, array $groups): array
    {
        $sectionIdToGroupsMap = $this->buildSectionIdToGroupsMap($groups);

        $configWithGroups = $this->addGroups($sectionIdToGroupsMap, $sectionConfig);

        $sortedConfigMap = $this->sortConfigMap($configWithGroups, keys($sectionConfig));

        return zip(keys($sortedConfigMap), $this->buildSectionInstances($formName, $sortedConfigMap));
    }

    private function buildSectionIdToGroupsMap(array $groups): array
    {
        return reduce($groups, function (array $map, FormGroupInterface $group): array {
            $map[$group->getSectionId()][$group->getId()] = $group;
            return $map;
        }, []);
    }

    private function buildSectionInstances(string $formName, array $sectionIdToSectionConfigMap): array
    {
        return map(function (string $sectionId) use ($sectionIdToSectionConfigMap, $formName): FormSectionInterface {
            return $this->formSectionFactory->create(merge([
                'id'       => $sectionId,
                'label'    => null,
                'formName' => $formName,
            ], $sectionIdToSectionConfigMap[$sectionId]));
        }, keys($sectionIdToSectionConfigMap));
    }

    /**
     * @param array $sectionIdToGroupsMap
     * @param array $sectionConfig
     * @return mixed
     */
    private function addGroups(array $sectionIdToGroupsMap, array $sectionConfig)
    {
        $sectionIds = keys($sectionIdToGroupsMap);
        return zip($sectionIds, map(function (string $sectionId) use ($sectionIdToGroupsMap, $sectionConfig): array {
            $config           = $this->extractSectionConfig($sectionId, $sectionConfig);
            $config['groups'] = $sectionIdToGroupsMap[$sectionId];
            return $config;
        }, $sectionIds));
    }

    private function extractSectionConfig(string $sectionId, array $sectionConfig): array
    {
        $originInfo = ['declaredInConfig' => isset($sectionConfig[$sectionId])];
        return merge($sectionConfig[$sectionId] ?? [], $originInfo);
    }

    private function sortConfigMap(array $sectionIdToConfigMap, array $sectionIdOrderFromConfig): array
    {
        $sortOrders   = pick($sectionIdToConfigMap, 'sortOrder');
        $maxSortOrder = max($sortOrders ?: [0]);

        $sectionsConfigsWithSortOrder = $this->addSortOrderToConfigs(
            $sectionIdToConfigMap,
            $sectionIdOrderFromConfig,
            $maxSortOrder
        );

        uasort($sectionsConfigsWithSortOrder, function (array $s1, array $s2): int {
            return $s1['sortOrder'] <=> $s2['sortOrder'];
        });

        return $sectionsConfigsWithSortOrder;
    }

    private function addSortOrderToConfigs($sectionIdToConfigMap, $sectionIdOrderFromConfig, $maxSortOrder): array
    {
        // Add missing sortOrder to sections in the order they are defined in the configuration.
        $sectionsFromConfigWithSortOrder = reduce(
            $sectionIdOrderFromConfig,
            function (array $sectionIdToConfigMap, string $sectionId) use (&$maxSortOrder): array {
                if (isset($sectionIdToConfigMap[$sectionId]) && !isset($sectionIdToConfigMap[$sectionId]['sortOrder'])) {
                    $sectionIdToConfigMap[$sectionId]['sortOrder'] = ++$maxSortOrder;
                }
                return $sectionIdToConfigMap;
            },
            $sectionIdToConfigMap
        );

        // Add sort order to any sections that where not defined in the config so they are sorted last.
        // Note: at the time or writing this only applies to the default section ''.
        $allSectionsWithSortOrder = map(function (array $sectionConfig) use (&$maxSortOrder): array {
            $sectionConfig['sortOrder'] = (int) ($sectionConfig['sortOrder'] ?? ++$maxSortOrder);
            return $sectionConfig;
        }, $sectionsFromConfigWithSortOrder);

        return $allSectionsWithSortOrder;
    }
}
