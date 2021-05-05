<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel\HyvaGrid;

use function array_values as values;

class GridAction implements GridActionInterface
{
    /**
     * @var string
     */
    private $label;

    /**
     * @var string
     */
    private $url;

    /**
     * @var string|null
     */
    private $id;

    /**
     * @var string|null
     */
    private $idParam;

    /**
     * @var string|null
     */
    private $idColumn;

    /**
     * @var array|null
     */
    private $events;

    public function __construct(
        string $label,
        string $url,
        ?array $events = null,
        ?string $id = null,
        ?string $idParam = null,
        ?string $idColumn = null
    ) {
        $this->label    = $label;
        $this->url      = $url;
        $this->events   = $events;
        $this->id       = $id;
        $this->idParam  = $idParam;
        $this->idColumn = $idColumn;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getEvents(): array
    {
        return $this->events ?? [];
    }

    public function getParams(RowInterface $row): array
    {
        $paramName = $this->idParam ?? $this->idColumn ?? $this->getFirstColumnKey($row);
        $paramCol  = $this->idColumn ?? ($row->getCell($paramName) ? $paramName : $this->getFirstColumnKey($row));
        return [$paramName => (string) $row->getCell($paramCol)->getRawValue()];
    }

    private function getFirstColumnKey(RowInterface $row): string
    {
        $first = values($row->getCells())[0] ?? null;
        if (!$first) {
            throw new \OutOfRangeException('Unable to determine the action id param because no columns are defined');
        }
        return $first->getColumnDefinition()->getKey();
    }

    public function getId(): string
    {
        return $this->id ?? $this->idParam ?? $this->idColumn ?? uniqid('undefined');
    }
}
