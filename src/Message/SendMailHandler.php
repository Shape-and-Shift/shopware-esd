<?php declare(strict_types=1);

namespace Sas\Esd\Message;

use Shopware\Core\Content\Mail\Service\MailService;

use Shopware\Core\Framework\MessageQueue\Handler\AbstractMessageHandler;

class SendMailHandler extends AbstractMessageHandler
{
    private MailService $mailService;

    public function __construct(MailService $mailService)
    {
        $this->mailService = $mailService;
    }

    /**
     * @param SendMailMessage $message
     */
    public function handle($message): void
    {
        $mail = $message->getMail();
        $data = $mail['data'];
        $context = $mail['context'];
        if (empty($mail['templateData'])) {
            $templateData = [];
        } else {
            $templateData = $mail['templateData'];
        }

        $this->mailService->send($data, $context, $templateData);
    }

    public static function getHandledMessages(): iterable
    {
        return [SendMailMessage::class];
    }
}
