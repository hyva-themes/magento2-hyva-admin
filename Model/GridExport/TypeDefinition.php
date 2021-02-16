<?php declare(strict_types=1);

namespace Hyva\Admin\Model\GridExport;

class TypeDefinition
{
    private array $gridExportTypes;
    private TypeInterfaceFactory $exportInterfaceFactory;

    public function __construct(array $gridExportTypes,  TypeInterfaceFactory $exportInterfaceFactory)
    {
        $this->gridExportTypes = $gridExportTypes;
        $this->exportInterfaceFactory = $exportInterfaceFactory;
    }

    public function get(string $exportType, $data): TypeInterface
    {
        if (isset($this->gridExportTypes[$exportType])) {
            return $this->exportInterfaceFactory->create($this->gridExportTypes[$exportType], $data);
        }
        throw new \Exception (sprintf('Export type "%s" not defined', $exportType));
    }
}
