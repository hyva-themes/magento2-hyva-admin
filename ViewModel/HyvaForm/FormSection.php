<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel\HyvaForm;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\LayoutInterface;

class FormSection implements FormSectionInterface
{
    /**
     * @var string
     */
    private $id;

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

    /**
     * @var LayoutInterface
     */
    private $layout;

    /**
     * @var bool
     */
    private $declaredInConfig;

    public function __construct(
        LayoutInterface $layout,
        string $id,
        string $formName,
        array $groups,
        ?string $label,
        bool $declaredInConfig = false
    ) {
        $this->layout           = $layout;
        $this->id               = $id;
        $this->formName         = $formName;
        $this->groups           = $groups;
        $this->label            = $label;
        $this->declaredInConfig = $declaredInConfig;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getGroups(): array
    {
        return $this->groups;
    }

    public function getHtml(): string
    {
        $block = $this->layout->createBlock(Template::class);
        $block->setTemplate('Hyva_Admin::form/section.phtml');
        $block->assign('section', $this);

        return $block->toHtml();
    }

    public function getLabel(): string
    {
        return $this->label ?? self::DEFAULT_SECTION_LABEL;
    }

    public function isDeclaredInConfig(): bool
    {
        return $this->declaredInConfig;
    }
}
