<?php declare(strict_types=1);

namespace Hyva\Admin\Block;

use Hyva\Admin\ViewModel\HyvaFormInterface;
use Hyva\Admin\ViewModel\HyvaFormInterfaceFactory;
use Magento\Framework\View\Element\Template;

class BaseHyvaForm extends Template
{
    /**
     * @var HyvaFormInterfaceFactory
     */
    private $hyvaFormFactory;

    public function __construct(
        Template\Context $context,
        string $formTemplate,
        HyvaFormInterfaceFactory $hyvaFormFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->setTemplate($formTemplate);
        $this->hyvaFormFactory = $hyvaFormFactory;
    }

    public function getForm(): HyvaFormInterface
    {
        if (!$this->getData('form_name')) {
            $msg = 'The name of the hyvä form needs to be set on the block instance.';
            throw new \LogicException($msg);
        }

        return $this->hyvaFormFactory->create(['formName' => $this->_getData('form_name')]);
    }
}
