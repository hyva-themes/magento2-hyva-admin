<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel;

use Hyva\Admin\ViewModel\HyvaForm\FormNavigationInterfaceFactory;

class HyvaFormViewModel implements HyvaFormInterface
{
    private string $formName;

    private FormNavigationInterfaceFactory $formNavigationFactory;

    public function __construct(string $formName, FormNavigationInterfaceFactory $formNavigationFactory)
    {
        $this->formName              = $formName;
        $this->formNavigationFactory = $formNavigationFactory;
    }

    public function getFormName(): string
    {
        return $this->formName;
    }

    public function getNavigation(): HyvaForm\FormNavigationInterface
    {
        return $this->formNavigationFactory->create([
            'formName' => $this->formName,
        ]);
    }

    public function getSections(): array
    {
        return [];
    }
}
