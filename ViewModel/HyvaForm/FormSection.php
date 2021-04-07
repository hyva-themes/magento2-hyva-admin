<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel\HyvaForm;

class FormSection implements FormSectionInterface
{
    /**
     * @var string
     */
    private $formName;

    /**
     * @var array
     */
    private $groups;

    /**
     * @var string|null
     */
    private $label;

    public function __construct(string $formName, array $groups, ?string $label)
    {
        $this->formName = $formName;
        $this->groups = $groups;
        $this->label = $label;
    }

    public function getId(): string
    {

    }

    public function getGroups(): array
    {
        return $this->groups;
    }

    public function getHtml(): string
    {

    }

    public function getLabel(): ?string
    {
        return $this->label;
    }
}
