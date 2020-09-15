<?php declare(strict_types=1);

namespace Sas\Esd\Content\Product\Extension\Esd;

use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdMedia\EsdMediaDefinition;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdOrder\EsdOrderDefinition;
use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class EsdDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'sas_product_esd';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return EsdEntity::class;
    }

    public function getCollectionClass(): string
    {
        return EsdCollection::class;
    }

    public function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->setFlags(new Required(), new PrimaryKey()),

            (new FkField('product_id', 'productId', ProductDefinition::class))->addFlags(new Required()),
            (new ReferenceVersionField(ProductDefinition::class))->addFlags(new Required()),
            (new ManyToOneAssociationField('product', 'product_id', ProductDefinition::class, 'id', false))->addFlags(new CascadeDelete()),

            (new FkField('media_id', 'mediaId', MediaDefinition::class)),
            (new OneToOneAssociationField('media', 'media_id', 'id', MediaDefinition::class, true)),

            new OneToManyAssociationField('esdMedia', EsdMediaDefinition::class, 'esd_id'),
            new OneToManyAssociationField('orders', EsdOrderDefinition::class, 'esd_id'),

            new BoolField('has_serial', 'hasSerial'),
            new BoolField('has_custom_download_limit', 'hasCustomDownloadLimit'),
            new BoolField('has_unlimited_download', 'hasUnlimitedDownload'),
            new IntField('download_limit_number', 'downloadLimitNumber'),

            new CreatedAtField(),
        ]);
    }
}
