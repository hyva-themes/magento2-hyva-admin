<?php declare(strict_types=1);

namespace Hyva\Admin\Model\GridSourceType\Internal;

use Hyva\Admin\Model\RawGridSourceContainer;

class RawGridSourceDataAccessor extends RawGridSourceContainer
{
    public function unbox(RawGridSourceContainer $container)
    {
        return $container->getRawGridSourceData();
    }
}
