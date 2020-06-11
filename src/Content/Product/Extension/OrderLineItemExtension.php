<?php declare(strict_types=1);

namespace Sas\Esd\Content\Product\Extension;

use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdOrder\EsdOrderDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityExtension;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class OrderLineItemExtension extends EntityExtension
{
    public function getDefinitionClass(): string
    {
        return OrderLineItemDefinition::class;
    }

    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            new OneToOneAssociationField('esdOrder', 'id', 'order_line_item_id', EsdOrderDefinition::class, false)
        );
    }
}
