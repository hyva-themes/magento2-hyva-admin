<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel\HyvaForm;

class FormNavigation implements FormNavigationInterface
{
    private string $formName;

    public function __construct(string $formName)
    {
        $this->formName = $formName;
    }

    public function getButtons(): array
    {
        return [];
    }
}
