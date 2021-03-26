<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel\HyvaForm;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\LayoutInterface;

class FormGroup implements FormGroupInterface
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var array
     */
    private $fields;

    /**
     * @var string|null
     */
    private $label;

    /**
     * @var string
     */
    private $sectionId;

    /**
     * @var int
     */
    private $sortOrder;

    /**
     * @var LayoutInterface
     */
    private $layout;

    public function __construct(
        LayoutInterface $layout,
        string $id,
        array $fields,
        string $sectionId,
        int $sortOrder,
        ?string $label = null
    ) {
        $this->layout    = $layout;
        $this->id        = $id;
        $this->fields    = $fields;
        $this->label     = $label;
        $this->sectionId = $sectionId;
        $this->sortOrder = $sortOrder;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getLabel(): string
    {
        return $this->label ?? self::DEFAULT_GROUP_NAME;
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    public function getHtml(): string
    {
        $block = $this->layout->createBlock(Template::class);
        $block->setTemplate('Hyva_Admin::form/group.phtml');
        $block->assign('group', $this);

        return $block->toHtml();
    }

    public function getSectionId(): string
    {
        return $this->sectionId;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }
}
