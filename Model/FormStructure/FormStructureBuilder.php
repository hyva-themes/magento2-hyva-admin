<?php declare(strict_types=1);

namespace Hyva\Admin\Model\FormStructure;

use Hyva\Admin\Model\FormEntity\FormLoadEntity;
use Hyva\Admin\Model\HyvaFormDefinitionInterface;
use Hyva\Admin\ViewModel\HyvaForm\FormFieldDefinitionInterface;
use Hyva\Admin\ViewModel\HyvaForm\FormFieldDefinitionInterfaceFactory;

use function array_map as map;

class FormStructureBuilder
{
    private FormFieldDefinitionInterfaceFactory $formFieldDefinitionFactory;

    public function __construct(FormFieldDefinitionInterfaceFactory $formFieldDefinitionFactory)
    {
        $this->formFieldDefinitionFactory = $formFieldDefinitionFactory;
    }

    public function buildStructure(HyvaFormDefinitionInterface $formDefinition, FormLoadEntity $formEntity): FormStructure
    {
        // This is the outline for algorithm to build the form structure of sections, groups and fields.
        // It is likely this might be moved to a different location once it's done.
        $fieldsFromEntity = $formEntity->getFieldDefinitions();
        $fieldsFromConfig = $formDefinition->getFieldDefinitions();

        $groupsFromConfiguredFields   = $this->getGroupsFromConfiguredFields($fieldsFromConfig);
        $groupsFromEntity             = $this->getGroupsFromEntity($formEntity);
        $groupsFromConfiguredSections = $this->getGroupsFromConfiguredSections($formDefinition->getSectionsConfig());

        $fieldToGroupMap  = $this->buildFieldToGroupMap($fieldsFromConfig, $fieldsFromEntity);
        $groupToFieldsMap = $this->buildGroupToFieldsMap($fieldToGroupMap);

        $sectionsFromConfig = $this->getSectionsFromConfig($formDefinition->getSectionsConfig());

        $groupToSectionMap = $this->buildGroupToSectionMap($sectionsFromConfig, $groupToFieldsMap);

        $sectionToGroupsMap = $this->buildSectionToGroupsMap($groupToSectionMap);

    }

    private function createFormField(array $data): FormFieldDefinitionInterface
    {
        return $this->formFieldDefinitionFactory->create([
            'name'           => $data['name'],
            'source'         => $data['source'],
            'inputType'      => $data['type'],
            'groupId'        => $data['group'],
            'template'       => $data['template'],
            'isEnabled'      => $data['enabled'],
            'valueProcessor' => $data['valueProcessor'],
        ]);
    }
}
