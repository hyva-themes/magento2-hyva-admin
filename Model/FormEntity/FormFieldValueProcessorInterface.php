<?php declare(strict_types=1);

namespace Hyva\Admin\Model\FormEntity;

interface FormFieldValueProcessorInterface
{
    public function toFieldValue($value);

    public function fromFieldValue($value);
}
