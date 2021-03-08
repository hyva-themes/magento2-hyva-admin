<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel\HyvaForm;

class FormFieldDefinition implements FormFieldDefinitionInterface
{
    private string $name;

    private ?string $source;

    private ?string $inputType;

    private ?string $groupId;

    private ?string $template;

    private ?bool $enabled;

    private ?string $valueProcessor;

    public function __construct(
        string $name,
        ?string $source,
        ?string $inputType,
        ?string $groupId,
        ?string $template,
        ?bool $isEnabled,
        ?string $valueProcessor
    ) {
        $this->name = $name;
        $this->source = $source;
        $this->inputType = $inputType;
        $this->groupId = $groupId;
        $this->template = $template;
        $this->enabled = $isEnabled;
        $this->valueProcessor = $valueProcessor;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getFormName(): string
    {

    }

    public function getHtml(): string
    {

    }

    public function getOptions(): array
    {

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
