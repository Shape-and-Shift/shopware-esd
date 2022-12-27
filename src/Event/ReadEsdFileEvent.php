<?php declare(strict_types=1);

namespace Sas\Esd\Event;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Event\NestedEvent;

class ReadEsdFileEvent extends NestedEvent
{
    protected Context $context;

    protected string $productId;

    public function __construct(string $productId)
    {
        $this->context = Context::createDefaultContext();
        $this->productId = $productId;
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
