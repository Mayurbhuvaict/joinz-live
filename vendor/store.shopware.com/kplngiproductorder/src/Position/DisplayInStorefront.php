<?php

namespace Kplngi\ProductOrder\Position;

use Shopware\Core\Framework\Struct\Struct;

class DisplayInStorefront extends Struct
{
    /**
     * @var bool
     */
    protected $displaySorting;

    public function getDisplaySorting(): bool
    {
        return $this->displaySorting;
    }
}
