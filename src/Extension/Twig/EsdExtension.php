<?php declare(strict_types=1);

namespace Sas\Esd\Extension\Twig;

use Sas\Esd\Content\Product\Extension\Esd\EsdCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class EsdExtension extends AbstractExtension
{
    public function __construct(private readonly EntityRepository $esdRepository)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('searchEsdByProductIds', [$this, 'searchEsdByProductIds']),
        ];
    }

    public function searchEsdByProductIds(array $productIds, Context $context): EsdCollection
    {
        if (empty($productIds)) {
            return new EsdCollection();
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('productId', $productIds));

        /** @var EsdCollection $esd */
        $esd = $this->esdRepository
            ->search($criteria, $context)->getEntities();

        return $esd;
    }
}
