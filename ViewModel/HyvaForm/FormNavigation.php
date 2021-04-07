<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel\HyvaForm;

class FormNavigation implements FormNavigationInterface
{
    /**
     * @var string
     */
    private $formName;

    public function __construct(string $formName)
    {
        $this->formName = $formName;
    }

    public function getButtons(): array
    {
        return [];
    }
}
