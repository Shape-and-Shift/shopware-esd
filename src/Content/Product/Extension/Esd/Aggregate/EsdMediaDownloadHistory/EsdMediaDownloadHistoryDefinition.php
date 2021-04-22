<?php declare(strict_types=1);

namespace Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdMediaDownloadHistory;

use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdMedia\EsdMediaDefinition;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdOrder\EsdOrderDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class EsdMediaDownloadHistoryDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'sas_product_esd_media_download_history';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return EsdMediaDownloadHistoryEntity::class;
    }

    public function getCollectionClass(): string
    {
        return EsdMediaDownloadHistoryCollection::class;
    }

    public function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->setFlags(new Required(), new PrimaryKey()),
            (new FkField('esd_order_id', 'esdOrderId', EsdOrderDefinition::class))->addFlags(new Required()),
            (new FkField('esd_media_id', 'esdMediaId', EsdMediaDefinition::class))->addFlags(new Required())
        ]);
    }
}
