<?php declare(strict_types=1);

namespace Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdMedia;

use Sas\Esd\Content\Product\Extension\Esd\EsdDefinition;
use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class EsdMediaDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'sas_product_esd_media';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return EsdMediaEntity::class;
    }

    public function getCollectionClass(): string
    {
        return EsdMediaCollection::class;
    }

    public function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->setFlags(new Required(), new PrimaryKey()),

            (new FkField('esd_id', 'esdId', EsdDefinition::class))->addFlags(new Required()),
            (new ManyToOneAssociationField('esd', 'esd_id', EsdDefinition::class)),

            (new FkField('media_id', 'mediaId', MediaDefinition::class)),
            (new OneToOneAssociationField('media', 'media_id', 'id', MediaDefinition::class, true)),
        ]);
    }
}
