<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel;

use Hyva\Admin\Model\FormEntity\FormLoadEntity;
use Hyva\Admin\Model\FormEntity\FormLoadEntityRepository;
use Hyva\Admin\Model\FormSource;
use Hyva\Admin\Model\FormSourceFactory;
use Hyva\Admin\Model\FormStructure\FormStructure;
use Hyva\Admin\Model\FormStructure\FormStructureBuilder;
use Hyva\Admin\Model\HyvaFormDefinitionInterface;
use Hyva\Admin\Model\HyvaFormDefinitionInterfaceFactory;
use Hyva\Admin\ViewModel\HyvaForm\FormNavigationInterfaceFactory;
use Hyva\Admin\ViewModel\HyvaForm\FormSectionInterfaceFactory;

use function array_column as pick;
use function array_filter as filter;
use function array_map as map;
use function array_merge as merge;
use function array_values as values;

class HyvaFormViewModel implements HyvaFormInterface
{
    /**
     * @var string
     */
    private $formName;

    /**
     * @var FormNavigationInterfaceFactory
     */
    private $formNavigationFactory;

    /**
     * @var HyvaFormDefinitionInterfaceFactory
     */
    private $formDefinitionFactory;

    /**
     * @var FormSourceFactory
     */
    private $formSourceFactory;

    /**
     * @var FormLoadEntity|null
     */
    private $loadedEntity;

    /**
     * @var FormLoadEntityRepository
     */
    private $formEntityRepository;

    /**
     * @var FormStructureBuilder
     */
    private $formStructureBuilder;

    public function __construct(
        string $formName,
        HyvaFormDefinitionInterfaceFactory $formDefinitionFactory,
        FormNavigationInterfaceFactory $formNavigationFactory,
        FormSourceFactory $formSourceFactory,
        FormLoadEntityRepository $formEntityRepository,
        FormStructureBuilder $formStructureBuilder
    ) {
        $this->formName              = $formName;
        $this->formNavigationFactory = $formNavigationFactory;
        $this->formDefinitionFactory = $formDefinitionFactory;
        $this->formSourceFactory     = $formSourceFactory;
        $this->formEntityRepository  = $formEntityRepository;
        $this->formStructureBuilder  = $formStructureBuilder;
    }

    public function getFormName(): string
    {
        return $this->formName;
    }

    public function getNavigation(): HyvaForm\FormNavigationInterface
    {
        return $this->formNavigationFactory->create([
            'formName'         => $this->formName,
            'navigationConfig' => $this->getFormDefinition()->getNavigationConfig(),
        ]);
    }

    public function getSections(): array
    {
        $this->getFormStructure()->getSections();
    }

    private function getFormStructure(): FormStructure
    {
        return $this->formStructureBuilder->buildStructure($this->getFormDefinition(), $this->getLoadedEntity());
    }

    private function getLoadedEntity(): FormLoadEntity
    {
        if (!isset($this->loadedEntity)) {
            $this->loadedEntity = $this->formEntityRepository->fetchTypeAndMethod(
                $this->getFormSource()->getLoadMethodName(),
                $this->getFormSource()->getLoadBindArgumentConfig(),
                $this->getFormSource()->getLoadType()
            );
        }
        return $this->loadedEntity;
    }

    private function getFormDefinition(): HyvaFormDefinitionInterface
    {
        return $this->formDefinitionFactory->create(['formName' => $this->formName]);
    }

    private function getFormSource(): FormSource
    {
        return $this->formSourceFactory->create([
            'formName'   => $this->formName,
            'loadConfig' => $this->getFormDefinition()->getLoadConfig(),
            'saveConfig' => $this->getFormDefinition()->getSaveConfig(),
        ]);
    }
}
