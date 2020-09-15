<?php declare(strict_types=1);

namespace Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdOrder;

use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdSerial\EsdSerialDefinition;
use Sas\Esd\Content\Product\Extension\Esd\EsdDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class EsdOrderDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'sas_product_esd_order';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return EsdOrderEntity::class;
    }

    public function getCollectionClass(): string
    {
        return EsdOrderCollection::class;
    }

    public function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->setFlags(new Required(), new PrimaryKey()),

            (new FkField('esd_id', 'esdId', EsdDefinition::class))->addFlags(new Required()),
            (new ManyToOneAssociationField('esd', 'esd_id', EsdDefinition::class)),

            (new FkField('order_line_item_id', 'orderLineItemId', OrderLineItemDefinition::class)),
            (new OneToOneAssociationField('orderLineItem', 'order_line_item_id', 'id', OrderLineItemDefinition::class, false)),

            (new FkField('serial_id', 'serialId', EsdSerialDefinition::class)),
            (new OneToOneAssociationField('serial', 'serial_id', 'id', EsdSerialDefinition::class, false)),

            new IntField('count_download', 'countDownload'),
        ]);
    }
}
