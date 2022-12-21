<?php declare(strict_types=1);

namespace Sas\Esd\Message;

use League\Flysystem\FileNotFoundException;
use Sas\Esd\Service\EsdService;
use Shopware\Core\Framework\MessageQueue\Handler\AbstractMessageHandler;

class CompressMediaHandler extends AbstractMessageHandler
{
    private EsdService $service;

    public function __construct(EsdService $service)
    {
        $this->service = $service;
    }

    /**
     * @param CompressMediaMessage $message
     *
     * @throws FileNotFoundException
     */
    public function handle($message): void
    {
        $this->service->compressFiles($message->getProductId());
    }

    public static function getHandledMessages(): iterable
    {
        return [CompressMediaMessage::class];
    }
}
