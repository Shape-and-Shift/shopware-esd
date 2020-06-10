<?php declare(strict_types=1);

namespace Sas\Esd\Content\Product\Extension;

use OdTrainings\Content\Product\Extension\Events\EventsDefinition;
use Onedrop\News\Content\OdsSiteNewsManufacturer\OdsSiteNewsManufacturerDefinition;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdMedia\EsdMediaDefinition;
use Sas\Esd\Content\Product\Extension\Esd\EsdDefinition;
use Shopware\Core\Content\Product\Aggregate\ProductManufacturer\ProductManufacturerDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityExtension;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Inherited;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class ProductExtension extends EntityExtension {

    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            (new OneToOneAssociationField(
                'esd',
                'id',
                'product_id',
                EsdDefinition::class
            ))->addFlags(new Inherited())
        );

        $collection->add(
            new ManyToManyAssociationField(
                'esdMedia',
                EsdDefinition::class,
                EsdMediaDefinition::class,
                'media_id',
                'sas_product_esd_id'
            )
        );
    }

    public function getDefinitionClass(): string
    {
        return ProductDefinition::class;
    }
}
