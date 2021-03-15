<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel\HyvaForm;

interface FormNavigationInterface
{
    /**
     * @return FormButtonInterface[]
     */
    public function getButtons(): array;
}
