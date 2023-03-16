<?php

declare(strict_types=1);

/*
 * This file is part of the "t3_psr3_log_processor" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Ssch\Psr3LogProcessor\Processor;

use TYPO3\CMS\Core\Log\Exception\InvalidLogProcessorConfigurationException;
use TYPO3\CMS\Core\Log\LogRecord;
use TYPO3\CMS\Core\Log\Processor\ProcessorInterface;

final class PsrLogMessageProcessor implements ProcessorInterface
{
    private const DEFAULT_JSON_FLAGS = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR;

    private string $dateFormat = "Y-m-d\TH:i:s.uP";

    private bool $removeUsedContextFields = false;

    private ?int $jsonFlags = null;

    /**
     * @param array{"dateFormat"?: string, "removeUsedContextFields"?: bool, "jsonFlags"?: int} $options
     */
    public function __construct(array $options = [])
    {
        foreach ($options as $optionKey => $optionValue) {
            if (! property_exists($this, $optionKey)) {
                throw new InvalidLogProcessorConfigurationException(
                    'Invalid LogProcessor configuration option "' . $optionKey . '" for log processor of type "' . self::class . '"',
                    1321696151
                );
            }

            $this->{$optionKey} = $optionValue;
        }
    }

    public function processLogRecord(LogRecord $logRecord): LogRecord
    {
        if (strpos($logRecord->getMessage(), '{') === false) {
            return $logRecord;
        }

        $replacements = [];
        $context = $logRecord->getData();

        foreach ($context as $key => $val) {
            $placeholder = '{' . $key . '}';
            if (strpos($logRecord->getMessage(), $placeholder) === false) {
                continue;
            }

            if ($val === null || is_scalar($val) || (is_object($val) && method_exists($val, '__toString'))) {
                $replacements[$placeholder] = $val;
            } elseif ($val instanceof \DateTimeInterface) {
                $replacements[$placeholder] = $val->format($this->dateFormat);
            } elseif ($val instanceof \UnitEnum) {
                $replacements[$placeholder] = $val instanceof \BackedEnum ? $val->value : $val->name;
            } elseif (is_object($val)) {
                $replacements[$placeholder] = '[object ' . $this->getClass($val) . ']';
            } elseif (is_array($val)) {
                $replacements[$placeholder] = 'array' . $this->jsonEncode($val);
            } else {
                $replacements[$placeholder] = '[' . gettype($val) . ']';
            }

            if ($this->removeUsedContextFields) {
                unset($context[$key]);
            }
        }

        return $logRecord
            ->setMessage(strtr($logRecord->getMessage(), $replacements))
            ->setData($context);
    }

    private function getClass(object $object): string
    {
        $class = \get_class($object);

        $pos = \strpos($class, "@anonymous\0");
        if ($pos === false) {
            return $class;
        }

        $parent = \get_parent_class($class);
        if ($parent === false) {
            return \substr($class, 0, $pos + 10);
        }

        return $parent . '@anonymous';
    }

    /**
     * @param array<mixed> $data
     */
    private function jsonEncode(array $data): string
    {
        $flags = $this->jsonFlags ?? self::DEFAULT_JSON_FLAGS;
        $json = @json_encode($data, $flags);
        if ($json === false) {
            return 'null';
        }

        return $json;
    }
}
