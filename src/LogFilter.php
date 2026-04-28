<?php

namespace PsrLogStacker;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use Stringable;

/**
 * Filter wrapper for logging.
 *
 * Constrains the service logger to only log when used on a certain level.
 * Also allows levels to be mapped @example map info -> error call.
 *
 * Useful when passing the service logger to third party
 * services where the log level cannot be controlled.
 */
class LogFilter extends AbstractLogger
{
    /**
     * The target service logger to filter
     */
    private LogManager $logger;

    /**
     * The constrained log levels
     *
     * @var array<LogLevel>
     */
    private array|null $constraints;

    /**
     * The log level mapper
     *
     * @var array<LogLevel, LogLevel>
     */
    private array $level_map;

    /**
     * Wrap the service logger with constraints
     *
     * @var array<LogLevel> $constraints Array of PSR-3 log levels
     * @var array<LogLevel, LogLevel> $level_map Array of PSR-3 log levels to map as [level => remap to]
     */
    public function __construct(LogManager $logger, array|null $constraints = null, array|null $level_map = null)
    {
        $this->logger      = $logger;
        $this->constraints = $constraints ?? null;

        if ($level_map) {
            $this->level_map = $level_map;
        }
    }

    /**
     * {@inheritdoc}
     *
     * Parent wrapper for log() calls, allowing filtering per instance
     */
    public function log($level, string|Stringable $message, array $context = []): void
    {
        if (isset($this->level_map)) {
            if (isset($this->level_map[$level])) {
                $level = $this->level_map[$level];
            }
        }

        if ($this->constraints === null) {
            $this->logger->log($level, $message, $context);
        } elseif (in_array($level, $this->constraints)) {
            $this->logger->log($level, $message, $context);
        }
    }
}
