<?php declare(strict_types=1);

namespace Hyva\Admin\Model\FormStructure;

use Hyva\Admin\ViewModel\HyvaForm\FormGroupInterface;
use Hyva\Admin\ViewModel\HyvaForm\FormSectionInterface;
use Hyva\Admin\ViewModel\HyvaForm\FormSectionInterfaceFactory;

use function array_column as pick;
use function array_combine as zip;
use function array_keys as keys;
use function array_map as map;
use function array_reduce as reduce;

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
            $map[$group->getSectionId()][] = $group;
            return $map;
        }, []);
    }

    private function buildSectionInstances(
        string $formName,
        array $sectionIdToGroupsMap
    ): array {
        return map(function (string $sectionId) use (
            $sectionIdToGroupsMap,
            $formName
        ): FormSectionInterface {
            return $this->formSectionFactory->create([
                'id'       => $sectionId,
                'groups'   => $sectionIdToGroupsMap[$sectionId],
                'label'    => $sortOrders['label'] ?? null,
                'formName' => $formName,
            ]);
        }, keys($sectionIdToGroupsMap));
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
            $config           = $sectionConfig[$sectionId] ?? [];
            $config['groups'] = $sectionIdToGroupsMap[$sectionId];
            return $config;
        }, $sectionIds));
    }

    private function sortConfigMap(array $sectionIdToConfigMap, array $sectionIdOrderFromConfig): array
    {
        $sortOrders   = pick($sectionIdToConfigMap, 'sortOrder');
        $maxSortOrder = max($sortOrders ?: [0]);

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

        $sllSectionsWithSortOrder = map(function (array $sectionConfig) use (&$maxSortOrder): array {
            $sectionConfig['sortOrder'] = (int) ($sectionConfig['sortOrder'] ?? ++$maxSortOrder);
            return $sectionConfig;
        }, $sectionsFromConfigWithSortOrder);

        uasort($sllSectionsWithSortOrder, function (array $s1, array $s2): int {
            return $s1['sortOrder'] <=> $s2['sortOrder'];
        });

        return $sllSectionsWithSortOrder;
    }
}
