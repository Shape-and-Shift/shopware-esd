<?php declare(strict_types=1);

namespace Sas\Esd\Subscriber;

use Shopware\Core\Content\Media\Event\MediaFileExtensionWhitelistEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MediaFileExtensionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            MediaFileExtensionWhitelistEvent::class => 'onMediaFileExtensionWhiteList',
        ];
    }

    public function onMediaFileExtensionWhiteList(MediaFileExtensionWhitelistEvent $event): void
    {
        $newWhiteList = array_merge([
            'ppt',
            'pptm',
            'pot',
            'potx',
            'potm',
            'pps',
            'ppsx',
            'pptx',
            'xlsx',
            'xlsm',
            'xls',
            'csv',
            'doc',
            'docm',
            'docx',
            'zip',
            'rar',
            'tar.gz',
            'tar.gz2',
            'epub',
            'mobi',
        ], $event->getWhitelist());

        $event->setWhitelist($newWhiteList);
    }
}
