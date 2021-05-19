<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel\HyvaForm;

use Hyva\Admin\Model\FormEntity\FormFieldValueProcessorFactory;
use Hyva\Admin\Model\FormEntity\FormFieldValueProcessorInterface;
use Hyva\Admin\Model\SourceModelFactory;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\LayoutInterface;
use function array_filter as filter;
use function array_merge as merge;

class FormFieldDefinition implements FormFieldDefinitionInterface
{
    /**
     * @var FormFieldDefinitionInterfaceFactory
     */
    private $formFieldDefinitionFactory;

    /**
     * @var FormFieldValueProcessorFactory
     */
    private $fieldValueProcessorFactory;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string|null
     */
    private $label;

    /**
     * @var string|null
     */
    private $source;

    /**
     * @var string|null
     */
    private $inputType;

    /**
     * @var string
     */
    private $valueType;

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
    private $disabled;

    /**
     * @var bool|null
     */
    private $hidden;

    /**
     * @var string|null
     */
    private $valueProcessor;

    /**
     * @var bool
     */
    private $renderAsSingleColumn;

    /**
     * @var LayoutInterface
     */
    private $layout;

    /**
     * @var string
     */
    private $formName;

    private $value;

    /**
     * @var int|null
     */
    private $sortOrder;

    /**
     * @var SourceModelFactory
     */
    private $sourceModelFactory;

    private $valueTypeToInputTypeMap = [
        'id'      => 'id',
        'string'  => 'text',
        'decimal' => 'text',
        'int'     => 'number',
        'bool'    => 'boolean',
        'text'    => 'textarea',
        'array'   => 'select',
    ];

    private $inputTypeToTemplateMap = [
        'text'     => 'text',
        'search'   => 'text',
        'url'      => 'text',
        'tel'      => 'text',
        'email'    => 'text',
        'password' => 'text',
        'range'    => 'range',
        'number'   => 'number',
        'date'     => 'number',
        'datetime' => 'number',
        'time'     => 'number',
        'month'    => 'date-interval',
        'week'     => 'date-interval',
    ];

    /**
     * @var string|null
     */
    private $pattern;

    /**
     * @var bool|null
     */
    private $required;

    /**
     * @var int|null
     */
    private $minlength;

    /**
     * @var int|null
     */
    private $maxlength;

    /**
     * @var string|null
     */
    private $min;

    /**
     * @var string|null
     */
    private $max;

    /**
     * @var int|null
     */
    private $step;

    public function __construct(
        LayoutInterface $layout,
        FormFieldDefinitionInterfaceFactory $formFieldDefinitionFactory,
        FormFieldValueProcessorFactory $fieldValueProcessorFactory,
        SourceModelFactory $sourceModelFactory,
        string $formName,
        string $name,
        ?string $valueType = null,
        $value = null,
        ?string $label = null,
        ?string $source = null,
        ?string $inputType = null,
        ?string $groupId = null,
        ?string $pattern = null,
        ?bool $required = null,
        ?int $minlength = null,
        ?int $maxlength = null,
        ?string $min = null,
        ?string $max = null,
        ?int $step = null,
        ?string $template = null,
        ?bool $disabled = null,
        ?bool $hidden = null,
        bool $renderAsSingleColumn = null,
        ?string $valueProcessor = null,
        ?int $sortOrder = null
    ) {
        $this->layout                     = $layout;
        $this->formFieldDefinitionFactory = $formFieldDefinitionFactory;
        $this->fieldValueProcessorFactory = $fieldValueProcessorFactory;
        $this->sourceModelFactory         = $sourceModelFactory;
        $this->formName                   = $formName;
        $this->name                       = $name;
        $this->valueType                  = $valueType;
        $this->value                      = $value;
        $this->label                      = $label;
        $this->source                     = $source;
        $this->inputType                  = $inputType;
        $this->groupId                    = $groupId;
        $this->template                   = $template;
        $this->disabled                   = $disabled;
        $this->hidden                     = $hidden;
        $this->renderAsSingleColumn       = $renderAsSingleColumn;
        $this->valueProcessor             = $valueProcessor;
        $this->sortOrder                  = $sortOrder;
        $this->pattern                    = $pattern;
        $this->required                   = $required;
        $this->minlength                  = $minlength;
        $this->maxlength                  = $maxlength;
        $this->min                        = $min;
        $this->max                        = $max;
        $this->step                       = $step;
    }

    public function toArray(): array
    {
        return filter([
            'formName'             => $this->formName,
            'name'                 => $this->name,
            'valueType'            => $this->valueType,
            'value'                => $this->value,
            'label'                => $this->label,
            'source'               => $this->source,
            'inputType'            => $this->inputType,
            'groupId'              => $this->groupId,
            'template'             => $this->template,
            'disabled'             => $this->disabled,
            'hidden'               => $this->hidden,
            'renderAsSingleColumn' => $this->renderAsSingleColumn,
            'valueProcessor'       => $this->valueProcessor,
            'sortOrder'            => $this->sortOrder,
            'pattern'              => $this->pattern,
            'required'             => $this->required,
            'minlength'            => $this->minlength,
            'maxlength'            => $this->maxlength,
            'min'                  => $this->min,
            'max'                  => $this->max,
            'step'                 => $this->step,
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
        return $this->valueProcessor
            ? $this->getValueProcessor()->toFieldValue($this->value)
            : $this->value;
    }

    public function getHtml(): string
    {
        return $this->renderTemplate($this->determineFieldContainerTemplate());
    }

    public function getContentHtml(): string
    {
        return $this->renderTemplate($this->determineFieldContentTemplate());
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
        return $this->source ? $this->sourceModelFactory->get($this->source)->toOptionArray() : [];
    }

    public function getGroupId(): string
    {
        return $this->groupId ?? FormGroupInterface::DEFAULT_GROUP_ID;
    }

    public function getInputType(): string
    {
        return $this->inputType ?? $this->determineInputType();
    }

    public function isDisabled(): bool
    {
        return (bool) $this->disabled;
    }

    public function getFormName(): string
    {
        return $this->formName;
    }

    public function getSortOrder(): ?int
    {
        return $this->sortOrder;
    }

    private function determineFieldContainerTemplate(): string
    {
        return $this->renderAsSingleColumn
            ? 'Hyva_Admin::form/field/one-col.phtml'
            : 'Hyva_Admin::form/field/two-col.phtml';
    }

    private function determineFieldContentTemplate(): string
    {
        return $this->template ?? 'Hyva_Admin::form/field/input/' . $this->getInputType() . '.phtml';
    }

    private function getValueProcessor(): FormFieldValueProcessorInterface
    {
        return $this->fieldValueProcessorFactory->get($this->name, $this->valueProcessor);
    }

    private function determineInputType(): string
    {
        return $this->valueTypeToInputTypeMap[$this->valueType] ?? 'text';
    }

    public function getPattern(): ?string
    {
        return $this->pattern;
    }

    public function isRequired(): bool
    {
        return (bool) $this->required;
    }

    public function getMinlength(): ?int
    {
        return $this->minlength;
    }

    public function getMaxlength(): ?int
    {
        return $this->maxlength;
    }

    public function getMin(): ?string
    {
        return $this->min;
    }

    public function getMax(): ?string
    {
        return $this->max;
    }

    public function getStep(): ?int
    {
        return $this->step;
    }

    public function isHidden(): ?bool
    {
        return $this->hidden;
    }
}
