<?php declare(strict_types=1);

namespace Sas\Esd\Message;

use League\Flysystem\FilesystemException;
use Sas\Esd\Service\EsdService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class CompressMediaHandler
{
    public function __construct(private readonly EsdService $service)
    {
    }

    /**
     * @throws FilesystemException
     */
    public function __invoke(CompressMediaMessage $message): void
    {
        $this->service->compressFiles($message->getProductId());
    }
}
