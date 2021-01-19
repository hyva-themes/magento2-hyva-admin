<?php declare(strict_types=1);

namespace Hyva\Admin\Model\Config;

use Magento\Framework\Config\Dom as XmlDom;
use Magento\Framework\Config\ValidationStateInterface;

use Magento\Framework\Module\Dir;
use Magento\Framework\Module\Dir\Reader as ModuleDirReader;
use function array_reduce as reduce;

class GridConfigReader implements HyvaGridConfigReaderInterface
{
    private GridDefinitionConfigFiles $definitionConfigFiles;

    private GridXmlToArrayConverter $gridXmlToArrayConverter;

    private ValidationStateInterface $appValidationState;

    private array $idAttributes = [
        '/grid/source/defaultSearchCriteriaBindings/field' => 'name',
        '/grid/massActions/action' => 'id',
        '/grid/actions/action' => 'id',
        '/grid/columns/include/column' => 'name',
        '/grid/columns/include/column/option' => 'value',
        '/grid/columns/exclude/column' => 'name',
        '/grid/navigation/filters/filter' => 'column',
        '/grid/navigation/buttons/button' => 'id',
    ];

    private ?string $perFileSchema;

    private ?string $mergedSchema;

    /**
     * @var ModuleDirReader
     */
    private ModuleDirReader $moduleDirReader;

    public function __construct(
        GridDefinitionConfigFiles $definitionConfigFiles,
        GridXmlToArrayConverter $gridXmlToArrayConverter,
        ValidationStateInterface $appValidationState,
        ModuleDirReader $moduleDirReader
    ) {
        $this->definitionConfigFiles   = $definitionConfigFiles;
        $this->gridXmlToArrayConverter = $gridXmlToArrayConverter;
        $this->appValidationState      = $appValidationState;
        $this->moduleDirReader         = $moduleDirReader;
        $this->perFileSchema           = 'hyva-grid.xsd';
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
        $files = $this->definitionConfigFiles->getGridDefinitionFiles($gridName);
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
