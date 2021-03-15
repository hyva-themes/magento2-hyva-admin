<?php declare(strict_types=1);

namespace Hyva\Admin\Model\Config;

use Magento\Framework\Config\Dom as XmlDom;
use Magento\Framework\Config\ValidationStateInterface;
use Magento\Framework\Module\Dir;
use Magento\Framework\Module\Dir\Reader as ModuleDirReader;

use function array_reduce as reduce;

class FormConfigReader implements HyvaFormConfigReaderInterface
{
    /**
     * @var FormDefinitionConfigFiles
     */
    private $formDefinitionConfigFiles;

    /**
     * @var FormXmlToArrayConverter
     */
    private $formXmlToArrayConverter;

    /**
     * @var ValidationStateInterface
     */
    private $appValidationState;

    /**
     * @var ModuleDirReader
     */
    private $moduleDirReader;

    private $idAttributes = [
        '/form/load/bindArguments/argument' => 'name',
        '/form/save/bindArguments/argument' => 'name',
        '/form/tabs/tab'                    => 'id',
        '/form/tabs/tab/group'              => 'id',
        '/form/fields/include/field'        => 'name',
        '/form/fields/exclude/field'        => 'name',
        '/form/navigation/buttons/button'   => 'id',
    ];

    /**
     * @var string|null
     */
    private $perFileSchema;

    private $mergedSchema;

    public function __construct(
        FormDefinitionConfigFiles $formDefinitionConfigFiles,
        FormXmlToArrayConverter $formXmlToArrayConverter,
        ValidationStateInterface $appValidationState,
        ModuleDirReader $moduleDirReader
    ) {
        $this->formDefinitionConfigFiles = $formDefinitionConfigFiles;
        $this->formXmlToArrayConverter   = $formXmlToArrayConverter;
        $this->appValidationState        = $appValidationState;
        $this->moduleDirReader           = $moduleDirReader;
        $this->perFileSchema             = null;//'hyva-form.xsd';
        $this->mergedSchema              = null;
    }

    public function getFormConfiguration(string $formName): array
    {
        return $this->readFormConfiguration($formName);
    }

    private function readFormConfiguration(string $formName): array
    {
        $files = $this->formDefinitionConfigFiles->getConfigDefinitionFiles($formName);
        return $files
            ? $this->mergeFormConfigs($files)
            : [];
    }

    private function mergeFormConfigs(array $files): array
    {
        $first        = array_shift($files);
        $mergedConfig = reduce($files, [$this, 'mergeFile'], $this->createDom(file_get_contents($first)));

        return $this->formXmlToArrayConverter->convert($mergedConfig->getDom());
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

    public function getPerFileSchema(): ?string
    {
        return $this->perFileSchema
            ? $this->moduleDirReader->getModuleDir(Dir::MODULE_ETC_DIR, 'Hyva_Admin') . '/' . $this->perFileSchema
            : null;
    }
}
