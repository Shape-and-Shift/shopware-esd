<?php declare(strict_types=1);

namespace Sas\Esd\Utils;

use Sas\Esd\Event\EsdDownloadPaymentStatusPaidDisabledZipEvent;
use Sas\Esd\Event\EsdDownloadPaymentStatusPaidEvent;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;

class InstallUninstall
{
    private EntityRepositoryInterface $mailTemplateTypeRepository;

    private EntityRepositoryInterface $mailTemplateRepository;

    private EntityRepositoryInterface $eventActionRepository;

    public function __construct(
        EntityRepositoryInterface $mailTemplateTypeRepository,
        EntityRepositoryInterface $mailTemplateRepository,
        EntityRepositoryInterface $eventActionRepository
    ) {
        $this->mailTemplateTypeRepository = $mailTemplateTypeRepository;
        $this->mailTemplateRepository = $mailTemplateRepository;
        $this->eventActionRepository = $eventActionRepository;
    }

    public function uninstall(Context $context): void
    {
        EsdMailTemplate::removeMailTemplate($this->mailTemplateTypeRepository, $this->mailTemplateRepository, $context);

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
}
