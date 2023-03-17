# TYPO3 PSR-3 Log Processor
[https://www.php-fig.org/psr/psr-3/#12-message](https://www.php-fig.org/psr/psr-3/#12-message)

## Integration guide
The extension ships with a custom Processor to substitute log messages with context variables in the message.
Let´s say you have a log message with the following format:

```php

use Psr\Log\LogLevel;use TYPO3\CMS\Core\Log\LogRecord;

$logRecord = new LogRecord('foo', LogLevel::INFO, 'A message with a {placeholder}', ['placeholder' => 'bar']);

```
In that case the {placeholder} of the message will be substituted by "bar" when processed by the PsrLogProcessor shipped with the extension.


In order to activate the Processor you can configure it i.e. via ext_localconf.php

```php

$GLOBALS['TYPO3_CONF_VARS']['LOG']['Documentation']['Examples']['Controller']['processorConfiguration'] = [
    // configuration for Debug level log entries and above
    \TYPO3\CMS\Core\Log\LogLevel::DEBUG => [
        \Ssch\Psr3LogProcessor\Processor\PsrLogMessageProcessor::class => [
            // The format of the timestamp: one supported by DateTime::format
            'dateFormat' => DateTime::ISO8601,
            // If set to true the fields interpolated into message gets unset
            'removeUsedContextFields' => true,
            // Arrays will be json encoded. With this option you can define how this will happen
            'jsonFlags' => JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        ]
    ]
];
```
[https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ApiOverview/Logging/Configuration/Index.html#processor-configuration](https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ApiOverview/Logging/Configuration/Index.html#processor-configuration)

## Credits
This LogProcessor is heavily inspired by the Processor of [monolog/monolog](https://github.com/Seldaek/monolog/)
