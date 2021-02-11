<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Integration\Model\FormSource;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Cms\Api\Data\BlockInterface;

class StubFormSourceTarget
{
    public function noType($arg)
    {

    }

    public function saveWithTypeParam(ProductInterface $arg1, BlockInterface $arg2)
    {

    }

    public function saveOnlyReturnType($arg): ProductInterface
    {

    }
}
