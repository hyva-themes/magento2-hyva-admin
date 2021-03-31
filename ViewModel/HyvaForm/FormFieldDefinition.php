<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel\HyvaForm;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\LayoutInterface;
use function array_filter as filter;
use function array_merge as merge;

class FormFieldDefinition implements FormFieldDefinitionInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string|null
     */
    private $label;

    /**
     * @var array|null
     */
    private $options;

    /**
     * @var string|null
     */
    private $inputType;

    /**
     * @var string|null
     */
    private $groupId;

    /**
     * @var string|null
     */
    private $template;

    /**
     * @var bool|null
     */
    private $enabled;

    /**
     * @var bool|null
     */
    private $excluded;

    /**
     * @var string|null
     */
    private $valueProcessor;

    /**
     * @var FormFieldDefinitionInterfaceFactory
     */
    private $formFieldDefinitionFactory;

    /**
     * @var LayoutInterface
     */
    private $layout;

    /**
     * @var string
     */
    private $formName;

    private $value;

    public function __construct(
        LayoutInterface $layout,
        FormFieldDefinitionInterfaceFactory $formFieldDefinitionFactory,
        string $formName,
        string $name,
        $value = null,
        ?string $label = null,
        ?array $options = [],
        ?string $inputType = null,
        ?string $groupId = null,
        ?string $template = null,
        ?bool $isEnabled = null,
        ?bool $isExcluded = null,
        ?string $valueProcessor = null
    ) {
        $this->layout                     = $layout;
        $this->formName                   = $formName;
        $this->formFieldDefinitionFactory = $formFieldDefinitionFactory;
        $this->name                       = $name;
        $this->value                      = $value;
        $this->label                      = $label;
        $this->options                    = $options;
        $this->inputType                  = $inputType;
        $this->groupId                    = $groupId;
        $this->template                   = $template;
        $this->enabled                    = $isEnabled;
        $this->excluded                   = $isExcluded;
        $this->valueProcessor             = $valueProcessor;
    }

    public function toArray(): array
    {
        return filter([
            'formName'       => $this->formName,
            'name'           => $this->name,
            'value'          => $this->value,
            'options'        => $this->options,
            'inputType'      => $this->inputType,
            'groupId'        => $this->groupId,
            'template'       => $this->template,
            'isEnabled'      => $this->enabled,
            'isExcluded'     => $this->excluded,
            'valueProcessor' => $this->valueProcessor,
        ]);
    }

    public function merge(FormFieldDefinitionInterface $field): FormFieldDefinitionInterface
    {
        return $this->formFieldDefinitionFactory->create(merge($this->toArray(), $field->toArray()));
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): string
    {
        return $this->label ?? ucwords(str_replace('_', ' ', $this->getName()));
    }

    public function getValue()
    {
        return $this->value;
    }

    public function getHtml(): string
    {
        return $this->renderTemplate($this->template ?? $this->determineFieldTemplate());
    }

    public function getInputHtml(): string
    {
        $inputType = $this->getInputType() ?: 'text';
        $template  = 'Hyva_Admin::form/field/input/' . $inputType . '.phtml';
        return $this->renderTemplate($template);
    }

    private function renderTemplate(string $template): string
    {
        $block = $this->layout->createBlock(Template::class);
        $block->setTemplate($template);
        $block->assign('field', $this);

        return $block->toHtml();
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getGroupId(): string
    {
        return $this->groupId ?? FormGroupInterface::DEFAULT_GROUP_ID;
    }

    public function getInputType(): string
    {
        return (string) $this->inputType;
    }

    public function isEnabled(): bool
    {
        return (bool) $this->enabled;
    }

    public function getFormName(): string
    {
        return $this->formName;
    }

    private function determineFieldTemplate(): string
    {
        return 'Hyva_Admin::form/field/two-col.phtml';
    }
}
