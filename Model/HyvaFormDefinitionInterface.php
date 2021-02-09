<?php declare(strict_types=1);

namespace Hyva\Admin\Model;

use Hyva\Admin\ViewModel\HyvaForm\FormFieldInterface;

interface HyvaFormDefinitionInterface
{
    public function getName(): string;

    /**
     * @return mixed[]
     */
    public function getLoadConfig(): array;

    /**
     * @return mixed[]
     */
    public function getSaveConfig(): array;

    /**
     * @return FormFieldInterface[]
     */
    public function getFieldDefinitions(): array;

    /**
     * @return mixed[]
     */
    public function getSectionsConfig(): array;

    /**
     * @return mixed[]
     */
    public function getNavigationConfig(): array;
}
