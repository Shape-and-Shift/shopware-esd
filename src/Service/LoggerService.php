<?php declare(strict_types=1);

namespace Sas\Esd\Service;

use Monolog\Logger;
use Psr\Log\AbstractLogger;

class LoggerService extends AbstractLogger
{
    private Logger $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    public function log($level, $message, array $context = []): void
    {
        $this->logger->log($level, $message, $context);
    }
}
