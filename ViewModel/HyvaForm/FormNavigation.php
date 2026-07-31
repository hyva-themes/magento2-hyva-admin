<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel\HyvaForm;

use Hyva\Admin\ViewModel\HyvaForm\FormButtonInterfaceFactory;

class FormNavigation implements FormNavigationInterface
{
    /**
     * @var string
     */
    private $formName;

    /**
     * @var array
     */
    private $navigationConfig;

    /**
     * @var FormButtonInterfaceFactory
     */
    private $buttonFactory;

    /**
     * @var FormButtonInterface[]|null
     */
    private $buttons;

    public function __construct(
        string $formName,
        FormButtonInterfaceFactory $buttonFactory,
        array $navigationConfig = []
    ) {
        $this->formName = $formName;
        $this->navigationConfig = $navigationConfig;
        $this->buttonFactory = $buttonFactory;
    }

    public function getButtons(): array
    {
        if ($this->buttons === null) {
            $this->buttons = $this->createButtonsFromConfig();
        }
        return $this->buttons;
    }

    private function createButtonsFromConfig(): array
    {
        $buttons = [];
        $buttonConfigs = $this->navigationConfig['buttons'] ?? [];
        
        foreach ($buttonConfigs as $buttonConfig) {
            $buttons[] = $this->buttonFactory->create(['buttonConfig' => $buttonConfig]);
        }
        
        return $buttons;
    }
}
