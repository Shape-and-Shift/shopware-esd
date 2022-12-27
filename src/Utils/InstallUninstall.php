<?php declare(strict_types=1);

namespace Sas\Esd\Utils;

use Sas\Esd\Event\EsdDownloadPaymentStatusPaidDisabledZipEvent;
use Sas\Esd\Event\EsdDownloadPaymentStatusPaidEvent;
use Sas\Esd\Event\EsdSerialPaymentStatusPaidEvent;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;

class InstallUninstall
{
    private EntityRepositoryInterface $mailTemplateTypeRepository;

    private EntityRepositoryInterface $mailTemplateRepository;

    private EntityRepositoryInterface $eventActionRepository;

    private EntityRepositoryInterface $flowRepository;

    public function __construct(
        EntityRepositoryInterface $mailTemplateTypeRepository,
        EntityRepositoryInterface $mailTemplateRepository,
        EntityRepositoryInterface $eventActionRepository,
        EntityRepositoryInterface $flowRepository
    ) {
        $this->mailTemplateTypeRepository = $mailTemplateTypeRepository;
        $this->mailTemplateRepository = $mailTemplateRepository;
        $this->eventActionRepository = $eventActionRepository;
        $this->flowRepository = $flowRepository;
    }

    public function uninstall(Context $context): void
    {
        EsdMailTemplate::removeMailTemplate($this->mailTemplateTypeRepository, $this->mailTemplateRepository, $context);

        $this->removeEventActions($context);
        $this->removeFlowBuilder($context);
    }

    private function removeEventActions(Context $context): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(
            new EqualsAnyFilter('eventName', [
                'esd.serial.payment.status.paid',
                EsdDownloadPaymentStatusPaidEvent::EVENT_NAME,
                EsdDownloadPaymentStatusPaidDisabledZipEvent::EVENT_NAME,
            ])
        );

        $eventActionIds = $this->eventActionRepository->searchIds($criteria, $context)->getIds();
        if (!empty($eventActionIds)) {
            $ids = array_map(static function ($id) {
                return ['id' => $id];
            }, $eventActionIds);
            $this->eventActionRepository->delete($ids, $context);
        }
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
