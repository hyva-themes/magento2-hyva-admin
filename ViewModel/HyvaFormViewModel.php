<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel;

use Hyva\Admin\Model\HyvaFormDefinitionInterface;
use Hyva\Admin\Model\HyvaFormDefinitionInterfaceFactory;
use Hyva\Admin\ViewModel\HyvaForm\FormNavigationInterfaceFactory;

class HyvaFormViewModel implements HyvaFormInterface
{
    private string $formName;

    private FormNavigationInterfaceFactory $formNavigationFactory;

    private HyvaFormDefinitionInterfaceFactory $formDefinitionFactory;

    public function __construct(
        string $formName,
        HyvaFormDefinitionInterfaceFactory $formDefinitionFactory,
        FormNavigationInterfaceFactory $formNavigationFactory
    ) {
        $this->formName              = $formName;
        $this->formNavigationFactory = $formNavigationFactory;
        $this->formDefinitionFactory = $formDefinitionFactory;
    }

    public function getFormName(): string
    {
        return $this->formName;
    }

    public function getNavigation(): HyvaForm\FormNavigationInterface
    {
        return $this->formNavigationFactory->create([
            'formName'         => $this->formName,
            'navigationConfig' => $this->getFormDefinition()->getNavigationConfig()
        ]);
    }

    public function getSections(): array
    {
        return [];
    }

    private function getFormDefinition(): HyvaFormDefinitionInterface
    {
        return $this->formDefinitionFactory->create(['formName' => $this->formName]);
    }
}
