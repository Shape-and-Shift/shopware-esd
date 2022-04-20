<?php declare(strict_types=1);

namespace Sas\Esd\Exception;;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class ProductNotEnoughSerialException extends ShopwareHttpException
{
    public function __construct(string $productId)
    {
        parent::__construct(
            'Product for id {{ productId }} has not enough the serial keys.',
            ['productId' => $productId]
        );
    }

    public function getErrorCode(): string
    {
        return 'CONTENT__PRODUCT_NOT_ENOUGH_SERIAL_KEY';
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }
}
