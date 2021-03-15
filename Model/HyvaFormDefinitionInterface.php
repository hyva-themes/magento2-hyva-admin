<?php declare(strict_types=1);

namespace Hyva\Admin\Model;

use Hyva\Admin\ViewModel\HyvaForm\FormFieldDefinitionInterface;

interface HyvaFormDefinitionInterface
{
    public function getFormName(): string;

    /**
     * @return mixed[]
     */
    public function getLoadConfig(): array;

    /**
     * @return mixed[]
     */
    public function getSaveConfig(): array;

    /**
     * @return FormFieldDefinitionInterface[]
     */
    public function getFieldDefinitions(): array;

    /**
     * @return mixed[]
     */
    public function getSectionsConfig(): array;

    /**
     * @return array[]
     */
    public function getGroupsFromSections(): array;

    /**
     * @return mixed[]
     */
    public function getNavigationConfig(): array;
}
