<?php declare(strict_types=1);

namespace Hyva\Admin\Model\FormStructure;

use Hyva\Admin\ViewModel\HyvaForm\FormSectionInterfaceFactory;
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

    /**
     * @var FormSectionsBuilder
     */
    private $formSectionsBuilder;

    public function __construct(
        FormFieldDefinitionInterfaceFactory $formFieldDefinitionFactory,
        MergeFormFieldDefinitionMaps $mergeFormFieldDefinitionMaps,
        FormGroupsBuilder $formGroupsBuilder,
        FormSectionsBuilder $formSectionsBuilder,
        FormSectionInterfaceFactory $formSectionFactory,
        FormStructureFactory $formStructureFactory
    ) {
        $this->formFieldDefinitionFactory   = $formFieldDefinitionFactory;
        $this->mergeFormFieldDefinitionMaps = $mergeFormFieldDefinitionMaps;
        $this->formGroupsBuilder            = $formGroupsBuilder;
        $this->formSectionsBuilder          = $formSectionsBuilder;
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

        $groups   = $this->formGroupsBuilder->buildGroups($fields, $formDefinition->getGroupsFromSections());
        $sections = $this->formSectionsBuilder->buildSections($formName, $groups, $formDefinition->getSectionsConfig());

        return $this->formStructureFactory->create($formName, $sections);
    }
}
