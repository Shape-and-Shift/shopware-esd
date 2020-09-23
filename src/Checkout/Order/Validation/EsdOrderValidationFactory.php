<?php declare(strict_types=1);

namespace Sas\Esd\Checkout\Order\Validation;

use Shopware\Core\Framework\Validation\DataValidationDefinition;
use Shopware\Core\Framework\Validation\DataValidationFactoryInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\Validator\Constraints\NotBlank;

class EsdOrderValidationFactory implements DataValidationFactoryInterface
{
    public function create(SalesChannelContext $context): DataValidationDefinition
    {
        return $this->createOrderValidation('order.create');
    }

    public function update(SalesChannelContext $context): DataValidationDefinition
    {
        return $this->createOrderValidation('order.update');
    }

    private function createOrderValidation(string $validationName): DataValidationDefinition
    {
        $definition = new DataValidationDefinition($validationName);

        $definition->add('waiveRightOfRescission', new NotBlank());

        return $definition;
    }
}
