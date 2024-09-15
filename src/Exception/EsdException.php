<?php
declare(strict_types=1);

namespace Sas\Esd\Exception;

use Shopware\Core\Framework\HttpException;
use Symfony\Component\HttpFoundation\Response;

class EsdException extends HttpException
{
    public const PRODUCT_NOT_ENOUGH_SERIAL_KEY = 'CONTENT__PRODUCT_NOT_ENOUGH_SERIAL_KEY';

    public static function productNotEnoughSerialKey(string $productId): self
    {
        return new self(
            Response::HTTP_NOT_FOUND,
            self::PRODUCT_NOT_ENOUGH_SERIAL_KEY,
            'Product for id {{ productId }} has not enough the serial keys.',
            ['productId' => $productId]
        );
    }
}
