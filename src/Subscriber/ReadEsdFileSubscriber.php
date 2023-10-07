<?php declare(strict_types=1);

namespace Sas\Esd\Subscriber;

use Sas\Esd\Event\ReadEsdFileEvent;
use Sas\Esd\Service\EsdService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ReadEsdFileSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly EsdService $esdService)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ReadEsdFileEvent::class => 'onCheckEsdFileIsExisting',
        ];
    }

    public function onCheckEsdFileIsExisting(ReadEsdFileEvent $event): void
    {
        if (is_file($this->esdService->getCompressFile($event->getProductId()))) {
            return;
        }

        $this->esdService->compressFiles($event->getProductId());
    }
}
