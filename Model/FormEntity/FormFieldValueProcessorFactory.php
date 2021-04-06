<?php declare(strict_types=1);

namespace Hyva\Admin\Model\FormEntity;

use Magento\Framework\ObjectManagerInterface;

class FormFieldValueProcessorFactory
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function get(string $fieldName, string $class): FormFieldValueProcessorInterface
    {
        $processor = $this->objectManager->get($class);
        $this->validateInstance($processor, $fieldName);
        return $processor;
    }

    private function validateInstance(string $processor, string $fieldName): void
    {
        if (!$processor instanceof FormFieldValueProcessorInterface) {
            $message = sprintf(
                'Field Value Processor for field "%s" does not implement the required interface %s',
                $fieldName,
                FormFieldValueProcessorInterface::class
            );
            throw new \RuntimeException($message);
        }
    }
}
