<?php

namespace PsrLogStacker\Loggers;

use Psr\Log\InvalidArgumentException;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Psr\Log\AbstractLogger;

/**
 * Handler for logging PSR to artisan console
 *
 * PSR-3 compliant console logger.
 *
 * @see Symfony\Component\Console\Logger\ConsoleLogger
 * @see https://www.php-fig.org/psr/psr-3/
 */
class LaravelCLI extends AbstractLogger
{
    private OutputInterface $output;

    /**
     * @see Symfony\Component\Console\Color for more colours
     */
    private array $formatLevelMap = [
        LogLevel::EMERGENCY => LogLevel::ERROR,
        LogLevel::ALERT => LogLevel::ERROR,
        LogLevel::CRITICAL => LogLevel::ERROR,
        LogLevel::ERROR => LogLevel::ERROR,
        LogLevel::WARNING => 'fg=yellow',
        LogLevel::NOTICE => LogLevel::INFO,
        LogLevel::INFO => 'fg=bright-white',
        LogLevel::DEBUG => 'fg=blue',
    ];

    public function __construct(OutputInterface $output, array $formatLevelMap = [])
    {
        $this->output         = $output;
        $this->formatLevelMap = $formatLevelMap + $this->formatLevelMap;
    }

    /**
     * Log messages to artisan output, so we have runtime context
     */
    public function log($level, $message, array $context = []): void
    {
        $this->output->writeln(
            sprintf(
                '<%1$s>%3$s [%2$s]</%1$s>',
                $this->formatLevelMap[$level],
                $level,
                $message
            ),
            OutputInterface::VERBOSITY_NORMAL
        );
    }
}
