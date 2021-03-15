<?php declare(strict_types=1);

namespace Hyva\Admin\Model\FormStructure;

use Hyva\Admin\Model\FormEntity\FormLoadEntity;
use Hyva\Admin\Model\HyvaFormDefinitionInterface;
use Hyva\Admin\ViewModel\HyvaForm\FormFieldDefinitionInterface as FieldDefinition;
use Hyva\Admin\ViewModel\HyvaForm\FormFieldDefinitionInterfaceFactory;

use function array_column as pick;
use function array_filter as filter;
use function array_keys as keys;
use function array_map as map;
use function array_merge as merge;
use function array_values as values;

class FormStructureBuilder
{
    /**
     * @var FormFieldDefinitionInterfaceFactory
     */
    private $formFieldDefinitionFactory;

    public function __construct(FormFieldDefinitionInterfaceFactory $formFieldDefinitionFactory)
    {
        $this->formFieldDefinitionFactory = $formFieldDefinitionFactory;
    }

    public function buildStructure(
        HyvaFormDefinitionInterface $formDefinition,
        FormLoadEntity $formEntity
    ): FormStructure {
        // This is the outline for algorithm to build the form structure of sections, groups and fields.
        // It is likely this might be moved to a different location once it's done.
        $fieldsFromEntity = $formEntity->getFieldDefinitions();
        $fieldsFromConfig = $formDefinition->getFieldDefinitions();

        $fields = $this->mergeFieldDefinitions($fieldsFromEntity, $fieldsFromConfig);

        $groupIdsFromFields   = map(function (FieldDefinition $f): ?string {
            return $f->getGroupId();
        }, $fields);
        $groupIdsFromSections = $this->getGroupIdsFromSections($formDefinition->getSectionsConfig());

        $fieldToGroupMap  = $this->buildFieldToGroupMap($fields);
        $groupToFieldsMap = $this->buildGroupToFieldsMap($fieldToGroupMap);

        $sectionsFromConfig = $this->getSectionsFromConfig($formDefinition->getSectionsConfig());

        $groupToSectionMap = $this->buildGroupToSectionMap($sectionsFromConfig, $groupToFieldsMap);

        $sectionToGroupsMap = $this->buildSectionToGroupsMap($groupToSectionMap);

    }

    /**
     * @param FieldDefinition[] $fieldsFromEntity
     * @param FieldDefinition[] $fieldsFromConfig
     * @return FieldDefinition[]
     */
    private function mergeFieldDefinitions(array $fieldsFromEntity, array $fieldsFromConfig): array
    {
        $fieldsOnlyInEntity = array_diff_key($fieldsFromEntity, $fieldsFromConfig);
        $fieldsOnlyInConfig = array_diff_key($fieldsFromConfig, $fieldsFromEntity);
        $fieldsInBoth       = array_intersect(keys($fieldsFromEntity), keys($fieldsFromConfig));

        return merge(
            $fieldsOnlyInConfig,
            map(
                [$this, 'mergeFieldDefinition'],
                filter($fieldsOnlyInEntity, function (FieldDefinition $f) use ($fieldsInBoth) {
                    return in_array($f->getName(), $fieldsInBoth);
                }),
                filter($fieldsOnlyInConfig, function (FieldDefinition $f) use ($fieldsInBoth) {
                    return in_array($f->getName(), $fieldsInBoth);
                })
            ),
            $fieldsOnlyInEntity
        );
    }

    private function mergeFieldDefinition(FieldDefinition $entityField, FieldDefinition $configField): FieldDefinition
    {
        return $entityField->merge($configField);
    }

    private function getGroupIdsFromSections(array $sectionsConfig): array
    {
        return merge([], ...values(map(function (array $section): array {
            return pick($section['groups'] ?? [], 'id');
        }, $sectionsConfig)));
    }

}
