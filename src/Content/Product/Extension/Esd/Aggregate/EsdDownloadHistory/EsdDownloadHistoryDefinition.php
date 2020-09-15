<?php declare(strict_types=1);

namespace Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdDownloadHistory;

use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdOrder\EsdOrderDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class EsdDownloadHistoryDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'sas_product_esd_download_history';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return EsdDownloadHistoryEntity::class;
    }

    public function getCollectionClass(): string
    {
        return EsdDownloadHistoryCollection::class;
    }

    public function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->setFlags(new Required(), new PrimaryKey()),

            (new FkField('esd_order_id', 'esdOrderId', EsdOrderDefinition::class))->addFlags(new Required()),
            (new ManyToOneAssociationField('esdOrder', 'esd_order_id', EsdOrderDefinition::class)),
        ]);
    }
}
