<?php declare(strict_types=1);
namespace Sas\Esd\Subscriber;

use Shopware\Core\Framework\Context;
use Shopware\Core\PlatformRequest;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

class MediaPermissionSubscriber
{
    public function __invoke(ControllerEvent $event): void
    {
        if ($event->getRequest()->attributes->get('_route') === 'api.action.media.upload') {
            $context = $event->getRequest()->attributes->get(PlatformRequest::ATTRIBUTE_CONTEXT_OBJECT);
            $f = \Closure::bind(function ($class) {
                $class->scope = Context::SYSTEM_SCOPE;
            }, null, $context);
            $f($context);
        }
    }
}
