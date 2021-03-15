<?php declare(strict_types=1);

namespace Hyva\Admin\Block\Adminhtml;

use Hyva\Admin\Block\BaseHyvaGrid;

/**
 * This class is intended to be used for adminhtml grids.
 *
 * It currently contains no additional logic to the parent class. The reason for this class is:
 * Originally the class was only intended for use in the admin area, and removing this class from the Adminhtml
 * namespace would be a backward incompatible change.
 * This class can also serve as an extension point if the adminhtml block ever needs to differ from the frontend block.
 */
class HyvaGrid extends BaseHyvaGrid
{
}
