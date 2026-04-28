# PSR-3 Log Stacker

> A configurable PSR-3 based wrapper for managing and routing log calls.

This package allows applications to retake control of PSR-3 operations for both the business logic and within third party SDKs. It allows multiple loggers to be configured and curated at run time, enabling logs to be remapped, filtered, amended, and handled dynamically.

## Log Manager

A brief overview of the log manager, detailing what it does and what it can be used for.

### Loggers

The Log manager mainly serves as a gateway that accepts and delegates PSR-3 log calls to all other registered loggers.

An example can be given with multiple registered loggers. A simple call to `->info('We love logging!');` will propagate to all loggers supplied:

- Log Manager (handles call `info()`):
    - -> Slack logger
    - -> File logger
    - -> CLI logger
    - -> DB logger

This allows a manager to be configured once and passed around the application, instead of manually logging to multiple loggers. The following block will propagate the single call to all registered loggers.

```php
use PsrLogStacker\LogManager;

// Create LogManager instance
$logManager = new LogManager();

// Register PSR-3 compliant loggers
$logManager->addLogger($monologLogger);
$logManager->addLogger($slackLogger);
$logManager->addLogger($cliLogger);
$logManager->addLogger($dbLogger);

// Log action
$logManager->info('Hello world');
```

### Context

To provide additional context, additional base context can be provided on the manager instance. This will be *merged* into the context array of each log call. 

This can be useful if you have a logger being called in multiple scenarios and environments in which you need to provide additional context.

A use case for this is to make identical log entries richer by providing additional context information, such as user, origin, cli, fpm etc. This is simply an array of information that always gets merged.

```php
$logManager->setContext(function () {
    return [
        'origin' => 'cli',
        'user' => 'system'
    ];
});

// Log action
$logManager->info('Scheduling job', ['job' => 'fetch_api']);
```

The following is then produced from a merged context on the log call
```php
[
    // Base context
    'origin' => 'cli'
    'user' => 'system'
    // Log context
    'job' => 'fetch_api'
]
```

## Log Filter

Some SDKs, libraries, or services have hardcoded log levels within their source that may conflict with your business logic. This can be a problem if you want to curate log levels across your application. 

To control this, the log filter class allows log levels to both be remapped and constrained to certain levels. 

The log manager is passed as a constructor, as you may want to make multiple filter instances for passing to different SDKs.

```php
$filteredLogger
    = new LogFilter(
        $logManager,
        null,
        [LogLevel::DEBUG => LogLevel::INFO]
    );

$filteredLogger->debug('Some debugging');
```
This remaps `debug()` calls to `info()` calls
```log
app.INFO: Some debugging {"debug":"local"} []
```

The log filter also allows only certain levels to be logged to by providing a constraint array. In this case, only the bottom four levels are allowed to be logged to.

```php
$filteredLogger
    = new LogFilter(
        $logManager,
        [
            LogLevel::WARNING,
            LogLevel::NOTICE,
            LogLevel::INFO,
            LogLevel::DEBUG,
        ]
    );

$filteredLogger->warning('Something bad is about to happen!');
$filteredLogger->error('OH NO!');
```

In this case, the errors are filtered out

```log
app.WARN: Something bad is about to happen! {} []
```

By design, logs are mapped before they are constrained, allowing remapping before filtering out. This can be handy for third party services that are noisy on certain levels.

## Supplied Loggers

This package also contains loggers that you can use directly within your application. These can be found within the `PsrLogStacker\Loggers\` namespace. These may also serve as inspiration in creating your own, bespoke loggers.

### Symfony & Laravel

There is a CLI logger, which parses log calls and writes them to the CLI using the underlying Symfony output. This requires to be called within the artisan command line. Being that Laravel is for the most part a huge Symfony wrapper, this should work within both Laravel and Symfony.

```php
use PsrLogStacker\LogManager;
use PsrLogStacker\Loggers\LaravelCLI;
use Symfony\Component\Console\Output\OutputInterface;

// Create LogManager instance
$logManager = new LogManager();

$output = $this->getOutput();

if ($output instanceof OutputInterface) {
    $logManager->addLogger(
        logger: new CLILogger($output),
        context: false
    );
}
```

## Future plans

- Unit tests
- Demo suite, featuring use cases