<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel;

use Hyva\Admin\Model\FormSource;
use Hyva\Admin\Model\FormSourceFactory;
use Hyva\Admin\Model\HyvaFormDefinitionInterface;
use Hyva\Admin\Model\HyvaFormDefinitionInterfaceFactory;
use Hyva\Admin\ViewModel\HyvaForm\FormNavigationInterfaceFactory;
use Hyva\Admin\ViewModel\HyvaForm\FormSectionInterfaceFactory;

use function array_filter as filter;
use function array_map as map;
use function array_merge as merge;
use function array_values as values;

class HyvaFormViewModel implements HyvaFormInterface
{
    private string $formName;

    private FormNavigationInterfaceFactory $formNavigationFactory;

    private HyvaFormDefinitionInterfaceFactory $formDefinitionFactory;

    private FormSectionInterfaceFactory $formSectionFactory;

    /**
     * @var FormSourceFactory
     */
    private FormSourceFactory $formSourceFactory;

    public function __construct(
        string $formName,
        HyvaFormDefinitionInterfaceFactory $formDefinitionFactory,
        FormNavigationInterfaceFactory $formNavigationFactory,
        FormSectionInterfaceFactory $formSectionFactory,
        FormSourceFactory $formSourceFactory
    ) {
        $this->formName              = $formName;
        $this->formNavigationFactory = $formNavigationFactory;
        $this->formDefinitionFactory = $formDefinitionFactory;
        $this->formSectionFactory    = $formSectionFactory;
        $this->formSourceFactory     = $formSourceFactory;
    }

    public function getFormName(): string
    {
        return $this->formName;
    }

    public function getNavigation(): HyvaForm\FormNavigationInterface
    {
        return $this->formNavigationFactory->create([
            'formName'         => $this->formName,
            'navigationConfig' => $this->getFormDefinition()->getNavigationConfig(),
        ]);
    }

    public function getSections(): array
    {
        $groupDeclarations = $this->getFormDefinition()->getAllGroups();
        $entityType        = $this->getFormSource()->getLoadType();
        $entity            = $this->getLoadedEntity();
        $entityGroups      = $entity ? $this->getLoadedEntityGroups($entity) : $this->getFormEntityGroups();
        return [];
    }

    private function getLoadedEntity()
    {
        if (!isset($this->loadedEntity)) {
            $this->loadedEntity = $this->getFormSource()->getLoadMethodValue();
        }
        return $this->loadedEntity;
    }

    private function getFormDefinition(): HyvaFormDefinitionInterface
    {
        return $this->formDefinitionFactory->create(['formName' => $this->formName]);
    }

    private function declaredGroupConfigsAsFlatArray(array $sectionsConfig): array
    {
        return values(filter(merge([], ...map(fn(array $s): array => $s['groups'] ?? [], $sectionsConfig))));
    }

    private function getFormSource(): FormSource
    {
        return $this->formSourceFactory->create([
            'formName'   => $this->formName,
            'loadConfig' => $this->getFormDefinition()->getLoadConfig(),
            'saveConfig' => $this->getFormDefinition()->getSaveConfig(),
        ]);
    }
}
