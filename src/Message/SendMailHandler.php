<?php declare(strict_types=1);

namespace Sas\Esd\Message;

use Shopware\Core\Content\Mail\Service\MailService as MailService;
use Shopware\Core\Content\MailTemplate\Service\MailService as MailServiceV63;

use Shopware\Core\Framework\MessageQueue\Handler\AbstractMessageHandler;

class SendMailHandler extends AbstractMessageHandler
{
    /**
     * @var MailService|MailServiceV63|null
     */
    private $mailService;

    public function __construct(?MailService $mailService, ?MailServiceV63 $mailServiceV63)
    {
        if ($mailServiceV63) {
            $this->mailService = $mailServiceV63;
        } else {
            $this->mailService = $mailService;
        }

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
