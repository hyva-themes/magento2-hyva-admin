<?php declare(strict_types=1);

namespace Hyva\Admin\Model\Config;

use Magento\Framework\Config\Dom as XmlDom;
use Magento\Framework\Config\ValidationStateInterface;
use function array_reduce as reduce;

class GridConfigReader
{
    private GridDefinitionConfigFiles $definitionConfigFiles;

    private GridXmlToArrayConverter $gridXmlToArrayConverter;

    private ValidationStateInterface $appValidationState;

    private array $idAttributes = [];

    private ?string $perFileSchema;

    private ?string $mergedSchema;

    public function __construct(
        GridDefinitionConfigFiles $definitionConfigFiles,
        GridXmlToArrayConverter $gridXmlToArrayConverter,
        ValidationStateInterface $appValidationState
    ) {
        $this->definitionConfigFiles   = $definitionConfigFiles;
        $this->gridXmlToArrayConverter = $gridXmlToArrayConverter;
        $this->appValidationState      = $appValidationState;
        $this->perFileSchema           = null;
        $this->mergedSchema            = null;
    }

    public function getGridConfiguration(string $gridName): array
    {
        // todo: add caching
        $gridConfig = $this->readGridConfig($gridName);

        return $gridConfig;
    }

    private function readGridConfig(string $gridName): array
    {
        $files        = $this->definitionConfigFiles->getGridDefinitionFiles($gridName);
        $first        = array_pop($files);
        $mergedConfig = reduce($files, [$this, 'mergeFile'], $this->createDom($first));

        return $this->gridXmlToArrayConverter->convert($mergedConfig->getDom());
    }

    private function mergeFile(XmlDom $merged, string $file): XmlDom
    {
        $content = file_get_contents($file);
        $merged->merge($this->createDom($content));
        $this->validateMerged($merged);

        return $merged;
    }

    private function createDom(string $content): XmlDom
    {
        return new XmlDom(
            $content,
            $this->appValidationState,
            $this->idAttributes,
            null, // the schema for the merged files is never used for individual files
            $this->perFileSchema
        );
    }

    private function validateMerged(XmlDom $merged): void
    {
        if ($this->mergedSchema && $this->appValidationState->isValidationRequired()) {
            $errors = [];
            if (!$merged->validate($this->mergedSchema, $errors)) {
                throw new \RuntimeException("Invalid Document \n" . implode("\n", $errors));
            }
        }
    }
}
