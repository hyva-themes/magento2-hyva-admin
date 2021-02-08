<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel;

interface HyvaFormInterface
{
    public function getFormName(): string;

    public function getNavigation(): HyvaForm\FormNavigationInterface;

    /**
     * @return HyvaForm\FormSectionInterface[]
     */
    public function getSections(): array;
}
