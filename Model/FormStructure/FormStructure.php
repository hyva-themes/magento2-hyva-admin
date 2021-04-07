<?php declare(strict_types=1);

namespace Hyva\Admin\Model\FormStructure;

use Hyva\Admin\ViewModel\HyvaForm\FormSectionInterface;

class FormStructure
{
    /**
     * @var string
     */
    private $formName;

    /**
     * @var FormSectionInterface[]
     */
    private $sections;

    /**
     * @param string $formName
     * @param FormSectionInterface[] $sections
     */
    public function __construct(string $formName, array $sections)
    {
        $this->formName = $formName;
        $this->sections = $sections;
    }

    public function getSections(): array
    {
        return $this->sections;
    }
}
