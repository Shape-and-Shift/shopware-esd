<?php declare(strict_types=1);

namespace Sas\Esd\Utils;

use Sas\Esd\Event\EsdDownloadPaymentStatusPaidDisabledZipEvent;
use Sas\Esd\Event\EsdDownloadPaymentStatusPaidEvent;
use Sas\Esd\Event\EsdSerialPaymentStatusPaidEvent;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;

class InstallUninstall
{
    public function __construct(
        private readonly EntityRepository $mailTemplateTypeRepository,
        private readonly EntityRepository $mailTemplateRepository,
        private readonly EntityRepository $flowRepository
    ) {
    }

    public function uninstall(Context $context): void
    {
        EsdMailTemplate::removeMailTemplate($this->mailTemplateTypeRepository, $this->mailTemplateRepository, $context);

        $this->removeFlowBuilder($context);
    }

    private function removeFlowBuilder(Context $context): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(
            new EqualsAnyFilter('eventName', [
                EsdDownloadPaymentStatusPaidDisabledZipEvent::EVENT_NAME,
                EsdDownloadPaymentStatusPaidEvent::EVENT_NAME,
                EsdSerialPaymentStatusPaidEvent::EVENT_NAME,
            ])
        );

        $flowIds = $this->flowRepository->searchIds($criteria, $context)->getIds();
        if (!empty($flowIds)) {
            $ids = array_map(static function ($id) {
                return ['id' => $id];
            }, $flowIds);
            $this->flowRepository->delete($ids, $context);
        }
    }
}
