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
     * @param JsonSerializer|null $jsonSerializer Deprecated and unused, kept for backward compatibility of subclasses
     */
    public function __construct(
        string $on,
        string $gridName,
        string $targetId,
        ?JsonSerializer $jsonSerializer = null
    ) {
        $this->on       = $on;
        $this->targetId = $targetId;
        $this->gridName = $gridName;
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

    /**
     * The params contain row values. Quotes, ampersands and angle brackets are encoded as unicode escapes, so the
     * JSON stays inert inside HTML attributes and script tags, even in templates that don't escape it.
     */
    public function getParamsJson(GridActionInterface $action, RowInterface $row): string
    {
        $data = [
            'action' => $action->getId(),
            'params' => $action->getParams($row),
        ];

        return json_encode(
            $data,
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR
        );
    }
}
