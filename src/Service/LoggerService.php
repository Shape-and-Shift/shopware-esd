<?php
declare(strict_types=1);

namespace Sas\Esd\Service;

use Monolog\Logger;
use Psr\Log\AbstractLogger;
use Psr\Log\InvalidArgumentException;

class LoggerService extends AbstractLogger
{
    public function __construct(private readonly Logger $logger)
    {
    }

    /**
     * Logs with an arbitrary level.
     *
     * @throws InvalidArgumentException
     */
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $this->logger->log($level, $message, $context);
    }
}
