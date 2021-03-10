<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel\HyvaForm;

class FormFieldDefinition implements FormFieldDefinitionInterface
{
    private string $name;

    private ?array $options;

    private ?string $inputType;

    private ?string $groupId;

    private ?string $template;

    private ?bool $enabled;

    private ?string $valueProcessor;

    public function __construct(
        string $name,
        ?array $options = [],
        ?string $inputType = null,
        ?string $groupId = null,
        ?string $template = null,
        ?bool $isEnabled = null,
        ?string $valueProcessor = null
    ) {
        $this->name           = $name;
        $this->options        = $options;
        $this->inputType      = $inputType;
        $this->groupId        = $groupId;
        $this->template       = $template;
        $this->enabled        = $isEnabled;
        $this->valueProcessor = $valueProcessor;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getHtml(): string
    {

    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getGroupId(): string
    {
        return $this->groupId;
    }

    public function getInputType(): string
    {
        return $this->inputType;
    }

    public function isEnabled(): bool
    {
        return (bool) $this->enabled;
    }
}
