<?php declare(strict_types=1);

namespace Hyva\Admin\Model\FormStructure;

use Hyva\Admin\ViewModel\HyvaForm\FormFieldDefinitionInterface;
use Hyva\Admin\ViewModel\HyvaForm\FormGroupInterface;
use Hyva\Admin\ViewModel\HyvaForm\FormGroupInterfaceFactory;

use function array_combine as zip;
use function array_filter as filter;
use function array_keys as keys;
use function array_map as map;
use function array_reduce as reduce;

class FormGroupsBuilder
{
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
        $fieldToGroupIdMap           = $this->buildFieldToGroupIdMap($fields);
        $groupIdToFieldIdsMap        = $this->buildGroupToFieldIdsMap($fieldToGroupIdMap);
        $groupIdToFullGroupConfigMap = $this->buildGroupConfigWithFields($groupIdToFieldIdsMap, $groupIdToConfigMap);

        return $this->buildGroupInstances($groupIdToFullGroupConfigMap, $fields);
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

    private function buildGroupConfigWithFields(array $groupIdToFieldIdsMap, array $groupIdToConfigMap): array
    {
        $groupIds = keys($groupIdToFieldIdsMap);
        $configs  = map(function (string $groupId) use ($groupIdToFieldIdsMap, $groupIdToConfigMap): array {
            $groupConfig             = $groupIdToConfigMap[$groupId] ?? ['id' => $groupId];
            $groupConfig['fieldIds'] = $groupIdToFieldIdsMap[$groupId];
            return $groupConfig;
        }, $groupIds);

        return zip($groupIds, $configs);
    }

    private function buildGroupInstances(array $groupIdToGroupConfigMap, array $fields): array
    {
        return map(function (array $groupConfig) use ($fields): FormGroupInterface {
            return $this->buildGroup($groupConfig, $fields);
        }, $groupIdToGroupConfigMap);
    }

    private function buildGroup(array $groupConfig, array $fields): FormGroupInterface
    {
        $fieldsInGroup = filter($fields, function (FormFieldDefinitionInterface $field) use ($groupConfig): bool {
            return in_array($field->getName(), $groupConfig['fieldIds'], true);
        });
        return $this->formGroupFactory->create([
            'id'        => $groupConfig['id'],
            'fields'    => $fieldsInGroup,
            'label'     => $groupConfig['label'] ?? null,
            'sectionId' => $groupConfig['sectionId'] ?? '',
        ]);
    }
}
