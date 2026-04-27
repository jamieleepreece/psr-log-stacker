<?php

namespace PsrLogStacker\Loggers;

use Psr\Log\AbstractLogger;

/**
 * Handler for logging to Slack
 *
 * Creates Jobs for each message
 *
 * @see https://www.php-fig.org/psr/psr-3/
 */
class SlackLogger extends AbstractLogger
{
    public function __construct(
        private ?string $prefix = null
    ) {
    }

    /**
     * Log messages to artisan output, so we have runtime context
     */
    public function log($level, $message, array $context = []): void
    {
        $context_string = null;

        if (!empty($context)) {
            $context_string = PHP_EOL . 'Context:' . '```' . json_encode($context, JSON_PRETTY_PRINT) . '```';
        }

        $slackJob = new \App\Jobs\NotifySlackChannel(
            sprintf(
                '*[%1$s]* %3$s: `%2$s` %4$s',
                $level,
                $message,
                $this->prefix,
                $context_string
            )
        );

        dispatch($slackJob);
    }
}
