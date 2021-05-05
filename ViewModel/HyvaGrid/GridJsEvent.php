<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel\HyvaGrid;

use Hyva\Admin\ViewModel\Shared\JsEventInterface;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;

class GridJsEvent implements JsEventInterface
{
    /**
     * @var string
     */
    private $on;

    /**
     * @var string
     */
    private $targetId;

    /**
     * @var string
     */
    private $gridName;

    /**
     * @var JsonSerializer
     */
    private $jsonSerializer;

    public function __construct(
        string $on,
        string $gridName,
        string $targetId,
        JsonSerializer $jsonSerializer
    ) {
        $this->on             = $on;
        $this->targetId       = $targetId;
        $this->gridName       = $gridName;
        $this->jsonSerializer = $jsonSerializer;
    }

    public function getEventName(): string
    {
        return $this->getDefaultEventName();
    }

    private function getDefaultEventName(): string
    {
        $gridNameInEvent = $this->eventify($this->gridName);
        return sprintf('hyva-grid-%s-action-%s-%s', $gridNameInEvent, $this->eventify($this->targetId), $this->on);
    }

    public function getOnTrigger(): string
    {
        return $this->on;
    }

    private function eventify(string $str): string
    {
        return strtolower(preg_replace('/[^[:alpha:]]+/', '-', $str));
    }

    public function getParamsJson(GridActionInterface $action, RowInterface $row): string
    {
        return $this->jsonSerializer->serialize([
            'action' => $action->getId(),
            'params' => $action->getParams($row),
        ]);
    }
}
