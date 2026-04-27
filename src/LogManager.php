<?php

namespace PsrLogStacker;

use Psr\Log\LoggerInterface;
use Psr\Log\AbstractLogger;
use Illuminate\Support\Facades\Log;
use Closure;
use Stringable;

/**
 * Handler for logging messages to multiple registered logfiles
 * 
 * Each specified PSR-3 monolog can be constrained to only 
 * log on certain levels. 
 * 
 * emergency()
 * alert()
 * critical()
 * error()
 * warning()
 * notice()
 * info()
 * debug()
 */
class LogManager extends AbstractLogger
{
    /**
     * Closure to provide context for the current instance
     */
	private ?Closure $context_closure = null;

	/**
	 * Enable or disable error logging altogether
	 */
	protected bool $error_logging_enabled = true;

	/**
	 * Enable or disable error logging
	 */
	protected bool $error_logging_slack = true;

	/**
	 * The target configured loggers in Laravel e.g. 'foo_bar_logger', 
	 * or PSR-3 compliant class with LoggerInterface.
	 * 
	 * As an array, so possible to log to multiple logs
	 */
	protected ?array $registered_loggers = null;

	/**
     * {@inheritdoc}
	 * 
	 * Merges runtime context with closure context for better debugging.
     */
    public function log($level, string|Stringable $message, array $context = [], string $colour = null): void
	{
		if ($this->error_logging_enabled) {
			$context = array_merge($this->getErrorContext(), $context);

			$context_message = '';

			// if (!empty($context)) {
			// 	$context_message .= PHP_EOL . PHP_EOL . 'Context:' . PHP_EOL;
			// 	$context_message .= \App\Helpers\ArrayMerger::toStringList($context);
			// 	$context_message .= PHP_EOL;
			// }

			if (is_array($this->registered_loggers)) {
				foreach ($this->registered_loggers as $key => $loggerConfig) {
					$logger_message = $message;

					if ($loggerConfig->context) {
						$logger_message .= $context_message;
					}

					// Constrain if global constraints set
					if (is_array($loggerConfig->levels)) {
						// Check the level is allowed
						if (in_array($level, $loggerConfig->levels)) {
							$loggerConfig->logger->{$level}($logger_message, $context, $colour);
						}
					}
					// Always log
					else {
						$loggerConfig->logger->{$level}($logger_message, $context, $colour);
					}
				}
			}
		}
	}

	/**
	 * Provide current context for the error
	 * 
	 * Intended as a configured closure on the class 
	 * to provide context to the exceptions thrown
	 * 
	 * @example ['Foo' => 'bar']
	 */
	public function getErrorContext(): array
	{
		if ($this->context_closure instanceof Closure) {
			$lambda = $this->context_closure;
			return $lambda();
		}

		return [];
	}

	/**
	 * Optionally set the context callback
	 */
	public function setContext(Closure $closure): void
	{
		$this->context_closure = $closure;
	}

	/**
	 * Add additional loggers to log to.
	 * 
	 * Second parameter constrains what levels that logger is to use.
	 * For example, if you only want that logger to log errors, then 
	 * use @example [Psr\Log\LogLevel::ERROR]. By default leaving this
	 * empty will allow all levels of logging
	 */
	public function addLogger(string|LoggerInterface $logger, array $levels = null, bool $context = true): void
	{
		if ($this->registered_loggers === null) {
			$this->registered_loggers = [];
		}

		if (is_string($logger)) {
			$this->addLogger(Log::channel($logger), $levels);
		} elseif ($logger instanceof LoggerInterface)
		{
			$this->registered_loggers[] = (object) [
				'levels' => $levels, 
				'logger' => $logger,
				'context' => $context,
			];
		}
	}
}
