<?php
declare(strict_types=1);

namespace Sas\Esd\Service;

use Monolog\Logger;
use Psr\Log\AbstractLogger;

class LoggerService extends AbstractLogger
{
    public function __construct(private readonly Logger $logger)
    {
    }

    /**
     * Logs with an arbitrary level.
     *
     * @throws \Psr\Log\InvalidArgumentException
     */
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $this->logger->log($level, $message, $context);
    }
}
