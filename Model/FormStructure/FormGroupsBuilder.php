<?php declare(strict_types=1);

namespace Hyva\Admin\Model\FormStructure;

use Hyva\Admin\ViewModel\HyvaForm\FormFieldDefinitionInterface;
use Hyva\Admin\ViewModel\HyvaForm\FormGroupInterface;
use Hyva\Admin\ViewModel\HyvaForm\FormGroupInterfaceFactory;

use function array_column as pick;
use function array_combine as zip;
use function array_filter as filter;
use function array_keys as keys;
use function array_map as map;
use function array_merge as merge;
use function array_reduce as reduce;

class FormGroupsBuilder
{
    private const NO_CONFIG = '_noConfig';

    /**
     * @var FormGroupInterfaceFactory
     */
    private $formGroupFactory;

    public function __construct(FormGroupInterfaceFactory $formGroupFactory)
    {
        $this->formGroupFactory = $formGroupFactory;
    }

    /**
     * @param FormFieldDefinitionInterface[] $fields
     * @param array[] $groupIdToConfigMap
     * @return FormGroupInterface[]
     */
    public function buildGroups(array $fields, array $groupIdToConfigMap): array
    {
        $fieldToGroupIdMap      = $this->buildFieldToGroupIdMap($fields);
        $groupIdToFieldIdsMap   = $this->buildGroupToFieldIdsMap($fieldToGroupIdMap);
        $configMapWithFields    = $this->addFieldIdsToGroupConfig($groupIdToConfigMap, $groupIdToFieldIdsMap);
        $groupIdToFullConfigMap = $this->addSortOrderWhereMissing($configMapWithFields);

        return $this->buildGroupInstances($groupIdToFullConfigMap, $fields);
    }

    /**
     * @param FormFieldDefinitionInterface[] $fields
     */
    private function buildFieldToGroupIdMap(array $fields): array
    {
        return reduce($fields, function (array $map, FormFieldDefinitionInterface $f): array {
            $map[$f->getName()] = $f->getGroupId();
            return $map;
        }, []);
    }

    private function buildGroupToFieldIdsMap(array $fieldToGroupIdMap): array
    {
        return reduce(keys($fieldToGroupIdMap), function (array $map, string $fieldId) use ($fieldToGroupIdMap): array {
            $groupId         = $fieldToGroupIdMap[$fieldId];
            $map[$groupId][] = $fieldId;
            return $map;
        }, []);
    }

    private function addFieldIdsToGroupConfig(array $groupIdToConfigMap, array $groupIdToFieldIdsMap): array
    {
        $groupIds = keys($groupIdToFieldIdsMap);
        $configs  = map(function (string $groupId) use ($groupIdToFieldIdsMap, $groupIdToConfigMap): array {
            $groupConfig             = $groupIdToConfigMap[$groupId] ?? ['id' => $groupId, self::NO_CONFIG => true];
            $groupConfig['fieldIds'] = $groupIdToFieldIdsMap[$groupId];
            return $groupConfig;
        }, $groupIds);

        return zip($groupIds, $configs);
    }

    private function buildGroupInstances(array $groupIdToGroupConfigMap, array $fields): array
    {
        uasort($groupIdToGroupConfigMap, [$this, 'compareGroupSortOrder']);
        return map(function (array $groupConfig) use ($fields): FormGroupInterface {
            return $this->buildGroup($groupConfig, $fields);
        }, $groupIdToGroupConfigMap);
    }

    private function buildGroup(array $groupConfig, array $fields): FormGroupInterface
    {
        $fieldsInGroup = filter($fields, function (FormFieldDefinitionInterface $field) use ($groupConfig): bool {
            return in_array($field->getName(), $groupConfig['fieldIds'], true);
        });
        return $this->formGroupFactory->create(merge($groupConfig, [
            'fields'    => $fieldsInGroup,
            'label'     => $groupConfig['label'] ?? null,
            'sectionId' => $groupConfig['sectionId'] ?? '',
        ]));
    }

    private function compareGroupSortOrder(array $g1, array $g2): int
    {
        return $g1['sortOrder'] <=> $g2['sortOrder'];
    }

    private function addSortOrderWhereMissing(array $groupIdToConfigMap): array
    {
        $groupsWithConfig = $this->addSortOrderToGroupsWithConfig($groupIdToConfigMap);

        return $this->addSortOrderToGroupsWithoutConfig($groupsWithConfig);
    }

    private function addSortOrderToGroupsWithConfig(array $allGroupsConfig): array
    {
        return $this->addSortOrderToGroups($allGroupsConfig, [$this, 'isGroupWithConfiguration']);
    }

    private function addSortOrderToGroupsWithoutConfig(array $allGroupsConfig): array
    {
        $isGroupWithoutConfiguration = function (array $groupConfig): bool {
            return ! $this->isGroupWithConfiguration($groupConfig);
        };
        return $this->addSortOrderToGroups($allGroupsConfig, $isGroupWithoutConfiguration);
    }

    private function addSortOrderToGroups(array $allGroupsConfig, callable $filter): array
    {
        $sortOrders = pick($allGroupsConfig, 'sortOrder');
        $maxSortOrder = max($sortOrders ?: [0]);

        return map(function (array $groupConfig) use ($filter, &$maxSortOrder): array {
            if ($filter($groupConfig)) {
                $groupConfig['sortOrder'] = (int) ($groupConfig['sortOrder'] ?? ++$maxSortOrder);
            }
            return $groupConfig;
        }, $allGroupsConfig);
    }

    private function isGroupWithConfiguration(array $groupConfig): bool
    {
        return ! isset($groupConfig[self::NO_CONFIG]);
    }
}
