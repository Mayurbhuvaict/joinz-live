<?php

namespace Kplngi\ProductOrder\Position;

class CategoryIdHelper
{
    /** @var string */
    private $categoryId;

    /**
     * @return string
     */
    public function getCategoryId(): ?string
    {
        return $this->categoryId;
    }

    /**
     * @param string $categoryId
     */
    public function setCategoryId(string $categoryId): void
    {
        $this->categoryId = $categoryId;
    }


}
