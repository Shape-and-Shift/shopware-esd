<?php declare(strict_types=1);

namespace Sas\Esd\Service;

use Monolog\Logger;
use Psr\Log\AbstractLogger;

class LoggerService extends AbstractLogger
{
    public function __construct(private readonly Logger $logger)
    {
    }

    public function log($level, $message, array $context = []): void
    {
        $this->logger->log($level, $message, $context);
    }
}
