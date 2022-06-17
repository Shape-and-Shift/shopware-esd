<?php declare(strict_types=1);

namespace Sas\Esd\Message;

class CompressMediaMessage
{
    private ?string $productId;

    public function setProductId(string $productId): void
    {
        $this->productId = $productId;
    }

    public function getProductId(): string
    {
        return $this->productId;
    }
}
