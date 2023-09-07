<?php declare(strict_types=1);

namespace Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdVideo;

use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdMedia\EsdMediaDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class EsdVideoDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'sas_product_esd_video';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return EsdVideoEntity::class;
    }

    public function getCollectionClass(): string
    {
        return EsdVideoCollection::class;
    }

    public function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->setFlags(new Required(), new PrimaryKey()),

            (new FkField('esd_media_id', 'esdMediaId', EsdMediaDefinition::class))->addFlags(new Required()),
            new OneToOneAssociationField('esdMedia', 'esd_media_id', 'id', EsdMediaDefinition::class, false),

            new IntField('option', 'option'),
        ]);
    }
}
