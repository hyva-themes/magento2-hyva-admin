<?php declare(strict_types=1);

namespace Hyva\Admin\Model\Config;

use Magento\Framework\Config\Dom as XmlDom;
use Magento\Framework\Config\ValidationStateInterface;
use Magento\Framework\Module\Dir;
use Magento\Framework\Module\Dir\Reader as ModuleDirReader;

use function array_reduce as reduce;

class GridConfigReader implements HyvaGridConfigReaderInterface
{
    /**
     * @var GridDefinitionConfigFiles
     */
    private $definitionConfigFiles;

    /**
     * @var GridXmlToArrayConverter
     */
    private $gridXmlToArrayConverter;

    /**
     * @var ValidationStateInterface
     */
    private $appValidationState;

    /**
     * @var ModuleDirReader
     */
    private $moduleDirReader;

    private $idAttributes = [
        '/grid/source/defaultSearchCriteriaBindings/field' => 'name',
        '/grid/source/processors/processor' => 'class',
        '/grid/massActions/action' => 'id',
        '/grid/actions/action' => 'id',
        '/grid/columns/include/column' => 'name',
        '/grid/columns/include/column/option' => 'value',
        '/grid/columns/exclude/column' => 'name',
        '/grid/navigation/filters/filter' => 'column',
        '/grid/navigation/buttons/button' => 'id',
        '/grid/navigation/exports/export' => 'type',
    ];

    /**
     * @var string|null
     */
    private $perFileSchema;

    /**
     * @var string|null
     */
    private $mergedSchema;

    public function __construct(
        GridDefinitionConfigFiles $gridDefinitionConfigFiles,
        GridXmlToArrayConverter $gridXmlToArrayConverter,
        ValidationStateInterface $appValidationState,
        ModuleDirReader $moduleDirReader
    ) {
        $this->definitionConfigFiles   = $gridDefinitionConfigFiles;
        $this->gridXmlToArrayConverter = $gridXmlToArrayConverter;
        $this->appValidationState      = $appValidationState;
        $this->moduleDirReader         = $moduleDirReader;
        $this->perFileSchema           = 'hyva-grid.xsd';
        $this->mergedSchema            = null;
    }

    public function getGridConfiguration(string $gridName): array
    {
        return $this->readGridConfig($gridName);
    }

    private function readGridConfig(string $gridName): array
    {
        $files = $this->definitionConfigFiles->getConfigDefinitionFiles($gridName);
        return $files
            ? $this->mergeGridConfigs($files)
            : [];
    }

    private function mergeGridConfigs(array $files): array
    {
        $first        = array_shift($files);
        $mergedConfig = reduce($files, [$this, 'mergeFile'], $this->createDom(file_get_contents($first)));

        return $this->gridXmlToArrayConverter->convert($mergedConfig->getDom());
    }

    private function mergeFile(XmlDom $merged, string $file): XmlDom
    {
        $merged->merge(file_get_contents($file));
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
            $this->getPerFileSchema()
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

    public function getPerFileSchema(): string
    {
        return $this->moduleDirReader->getModuleDir(Dir::MODULE_ETC_DIR, 'Hyva_Admin') . '/' . $this->perFileSchema;
    }
}
