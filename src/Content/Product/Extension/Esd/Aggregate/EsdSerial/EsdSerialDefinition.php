<?php declare(strict_types=1);

namespace Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdSerial;

use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdOrder\EsdOrderDefinition;
use Sas\Esd\Content\Product\Extension\Esd\EsdDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class EsdSerialDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'sas_product_esd_serial';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return EsdSerialEntity::class;
    }

    public function getCollectionClass(): string
    {
        return EsdSerialCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->setFlags(new Required(), new PrimaryKey()),

            (new FkField('esd_id', 'esdId', EsdDefinition::class))->addFlags(new Required()),
            (new ManyToOneAssociationField('esd', 'esd_id', EsdDefinition::class)),

            (new StringField('serial', 'serial'))->addFlags(new Required()),

            (new OneToOneAssociationField('esdOrder', 'id', 'serial_id', EsdOrderDefinition::class, false)),
        ]);
    }
}
