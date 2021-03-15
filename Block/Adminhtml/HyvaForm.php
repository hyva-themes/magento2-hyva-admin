<?php declare(strict_types=1);

namespace Hyva\Admin\Block\Adminhtml;

use Hyva\Admin\Block\BaseHyvaForm;

/**
 * This class is intended to be used for adminhtml forms.
 *
 * It currently contains no additional logic to the parent class. The reason for this class exists is so it can
 * be used as an extension point if the admin form block ever requires changes from the generic class that
 * is also used as an ancestor for frontend forms.
 */
class HyvaForm extends BaseHyvaForm
{

}
