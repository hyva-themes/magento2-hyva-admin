<?php declare(strict_types=1);

namespace Hyva\Admin\Model;

/**
 * This is a blackbox container for grid source data specific to each grid source type.
 */
class RawGridSourceContainer
{
    /**
     * @var mixed
     */
    private $rawGridSourceData;

    public static function forData($rawGridSourceData): self
    {
        $container = new self();
        $container->rawGridSourceData = $rawGridSourceData;
        return $container;
    }

    /**
     * @return mixed
     */
    final protected function getRawGridSourceData()
    {
        return $this->rawGridSourceData;
    }
}
