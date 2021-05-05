<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel\HyvaGrid;

use Hyva\Admin\ViewModel\Shared\JsEventInterface;

interface GridActionInterface
{
    public function getId(): string;

    public function getUrl(): string;

    public function getLabel(): string;

    /**
     * @return JsEventInterface[]
     */
    public function getEvents(): array;

    /**
     * @param RowInterface $row
     * @return string[]
     */
    public function getParams(RowInterface $row): array;
}
