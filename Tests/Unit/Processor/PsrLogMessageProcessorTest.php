<?php

declare(strict_types=1);

/*
 * This file is part of the "t3_psr3_log_processor" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Ssch\Psr3LogProcessor\Tests\Unit\Processor;

use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use Ssch\Psr3LogProcessor\Processor\PsrLogMessageProcessor;
use TYPO3\CMS\Core\Log\Exception\InvalidLogProcessorConfigurationException;
use TYPO3\CMS\Core\Log\LogRecord;

final class PsrLogMessageProcessorTest extends TestCase
{
    public function testThatAnExceptionIsThrownWhenConfigurationOptionDoesNotExist(): void
    {
        // Assert
        $this->expectException(InvalidLogProcessorConfigurationException::class);

        // Arrange
        $this->createProcessor([
            'doesnotexist' => 1,
        ]);
    }

    public function testThatPlaceholdersAreSubstitutedSuccessfully(): void
    {
        // Arrange
        $subject = $this->createProcessor();
        $logRecord = new LogRecord('foo', LogLevel::INFO, 'Error thrown while handling message {class}. Removing from transport after {retryCount} retries at {date}. Error: "{error}". {array}', [
            'array' => [
                'some' => 'foo',
            ],
            'date' => new \DateTimeImmutable('12.12.2023'),
            'class' => new \stdClass(),
            'retryCount' => 3,
            'error' => 'An error occured',
        ]);

        // Act
        $logRecord = $subject->processLogRecord($logRecord);

        // Assert
        self::assertSame(
            'Error thrown while handling message [object stdClass]. Removing from transport after 3 retries at 2023-12-12T00:00:00.000000+00:00. Error: "An error occured". array{"some":"foo"}',
            $logRecord->getMessage()
        );
    }

    public function testThatAMessageWithNoPlaceholdersIsUntouched(): void
    {
        // Arrange
        $subject = $this->createProcessor();
        $logRecord = new LogRecord('foo', LogLevel::INFO, 'No placeholders');

        // Act
        $logRecord = $subject->processLogRecord($logRecord);

        // Assert
        self::assertSame('No placeholders', $logRecord->getMessage());
    }

    public function testThatReplacedContextKeysAreRemoved(): void
    {
        // Arrange
        $subject = $this->createProcessor([
            'removeUsedContextFields' => true,
        ]);
        $logRecord = new LogRecord('foo', LogLevel::INFO, 'A {placeholder} which will be removed from context array', [
            'placeholder' => 'foo',
        ]);

        // Act
        $logRecord = $subject->processLogRecord($logRecord);

        // Assert
        self::assertArrayNotHasKey('placeholder', $logRecord->getData());
    }

    public function testThatAMessageWithPlaceholdersWhichAreNotDefinedIsUntouched(): void
    {
        // Arrange
        $subject = $this->createProcessor();
        $logRecord = new LogRecord('foo', LogLevel::INFO, 'A {placeholder} which is not defined in context array');

        // Act
        $logRecord = $subject->processLogRecord($logRecord);

        // Assert
        self::assertSame('A {placeholder} which is not defined in context array', $logRecord->getMessage());
    }

    /**
     * @param array<mixed> $options
     */
    private function createProcessor(array $options = []): PsrLogMessageProcessor
    {
        return new PsrLogMessageProcessor($options);
    }
}
