<?php declare(strict_types=1);

namespace Sas\Esd\Message;

class SendMailMessage
{
    /**
     * @var array
     */
    private $mail = [];

    public function setMail(array $mail): void
    {
        $this->mail = $mail;
    }

    public function getMail(): array
    {
        return $this->mail;
    }
}
