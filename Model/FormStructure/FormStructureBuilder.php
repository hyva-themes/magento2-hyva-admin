<?php declare(strict_types=1);

namespace Hyva\Admin\Model\FormStructure;

use Hyva\Admin\ViewModel\HyvaForm\FormFieldDefinitionInterface;

use Hyva\Admin\ViewModel\HyvaForm\FormSectionInterface;
use function array_keys as keys;
use function array_map as map;
use function array_reduce as reduce;

use Hyva\Admin\ViewModel\HyvaForm\FormSectionInterfaceFactory;
use Hyva\Admin\ViewModel\HyvaForm\FormGroupInterface;
use Hyva\Admin\ViewModel\HyvaForm\FormGroupInterfaceFactory;
use Hyva\Admin\Model\FormEntity\FormLoadEntity;
use Hyva\Admin\Model\HyvaFormDefinitionInterface;
use Hyva\Admin\ViewModel\HyvaForm\FormFieldDefinitionInterfaceFactory;

class FormStructureBuilder
{
    /**
     * @var FormFieldDefinitionInterfaceFactory
     */
    private $formFieldDefinitionFactory;

    /**
     * @var MergeFormFieldDefinitionMaps
     */
    private $mergeFormFieldDefinitionMaps;

    /**
     * @var FormSectionInterfaceFactory
     */
    private $formSectionFactory;

    /**
     * @var FormStructureFactory
     */
    private $formStructureFactory;

    /**
     * @var FormGroupsBuilder
     */
    private $formGroupsBuilder;

    public function __construct(
        FormFieldDefinitionInterfaceFactory $formFieldDefinitionFactory,
        MergeFormFieldDefinitionMaps $mergeFormFieldDefinitionMaps,
        FormGroupsBuilder $formGroupsBuilder,
        FormSectionInterfaceFactory $formSectionFactory,
        FormStructureFactory $formStructureFactory
    ) {
        $this->formFieldDefinitionFactory   = $formFieldDefinitionFactory;
        $this->mergeFormFieldDefinitionMaps = $mergeFormFieldDefinitionMaps;
        $this->formGroupsBuilder            = $formGroupsBuilder;
        $this->formSectionFactory           = $formSectionFactory;
        $this->formStructureFactory         = $formStructureFactory;
    }

    /**
     * This is the algorithm to build the form structure of sections, groups and fields.
     *
     * Any groups without fields are dropped.
     * Any sections without groups are dropped.
     * If a field has no group, it is assigned to a group with an empty string id ''.
     * If a group has no section, it is assigned to a section with an empty string id ''.
     */
    public function buildStructure(
        string $formName,
        HyvaFormDefinitionInterface $formDefinition,
        FormLoadEntity $formEntity
    ): FormStructure {
        $fieldsFromEntity = $formEntity->getFieldDefinitions();
        $fieldsFromConfig = $formDefinition->getFieldDefinitions();
        $fields           = $this->mergeFormFieldDefinitionMaps->merge($fieldsFromEntity, $fieldsFromConfig);

        $groups = $this->formGroupsBuilder->buildGroups($fields, $formDefinition->getGroupsFromSections());

        $sectionIdToGroupsMap = $this->buildSectionIdToGroupsMap($groups);
        $sections             = $this->buildSectionInstances($formName, $sectionIdToGroupsMap, $formDefinition);

        return $this->formStructureFactory->create($formName, $sections);
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
        array $sectionIdToGroupsMap,
        HyvaFormDefinitionInterface $formDefinition
    ): array {
        $sectionConfig = $formDefinition->getSectionsConfig();
        return map(function (string $sectionId) use (
            $sectionIdToGroupsMap,
            $sectionConfig,
            $formName
        ): FormSectionInterface {
            $this->formSectionFactory->create([
                'id'       => $sectionId,
                'groups'   => $sectionIdToGroupsMap[$sectionId],
                'label'    => $sectionConfig['label'] ?? null,
                'formName' => $formName,
            ]);
        }, keys($sectionIdToGroupsMap));
    }
}
