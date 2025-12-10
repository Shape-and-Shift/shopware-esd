<?php
declare(strict_types=1);

namespace Sas\Esd\Event;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Event\NestedEvent;

class ReadEsdFileEvent extends NestedEvent
{
    public function __construct(protected readonly string $productId, protected readonly Context $context)
    {
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    public function getProductId(): string
    {
        return $this->productId;
    }
}
