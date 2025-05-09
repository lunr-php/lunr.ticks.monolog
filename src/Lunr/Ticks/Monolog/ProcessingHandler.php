<?php

/**
 * This file contains a processing handler class for Monolog.
 *
 * SPDX-FileCopyrightText: Copyright 2025 Framna Netherland B.V., Zwolle, The Netherlands
 * SPDX-License-Identifier: MIT
 */

namespace Lunr\Ticks\Monolog;

use Lunr\Ticks\EventLogging\EventLoggerInterface;
use Lunr\Ticks\Precision;
use Lunr\Ticks\TracingControllerInterface;
use Lunr\Ticks\TracingInfoInterface;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;
use Psr\Log\LogLevel;
use Throwable;

/**
 * Logs via Lunr.Ticks.
 *
 * @phpstan-type LevelValue value-of<Level::VALUES>|value-of<Level::NAMES>|Level|LogLevel::*
 * @phpstan-type TracingInterface TracingControllerInterface&TracingInfoInterface
 */
class ProcessingHandler extends AbstractProcessingHandler
{

    /**
     * Instance of an EventLogger
     * @var EventLoggerInterface
     */
    private readonly EventLoggerInterface $eventLogger;

    /**
     * Shared instance of a tracing controller
     * @var TracingInterface
     */
    private readonly TracingControllerInterface&TracingInfoInterface $tracingController;

    /**
     * Constructor.
     *
     * @param EventLoggerInterface $eventLogger       Instance of an event logger
     * @param TracingInterface     $tracingController Instance of a tracing controller
     * @param LevelValue           $level             The minimum logging level at which this handler will be triggered
     * @param bool                 $bubble            Whether the messages that are handled can bubble up the stack or not
     */
    public function __construct(
        EventLoggerInterface $eventLogger,
        TracingControllerInterface&TracingInfoInterface $tracingController,
        int|string|Level $level = Level::Debug,
        bool $bubble = TRUE,
    )
    {
        parent::__construct($level, $bubble);

        $this->eventLogger       = $eventLogger;
        $this->tracingController = $tracingController;
    }

    /**
     * Writes the (already formatted) record down to the log of the implementing handler
     *
     * @param LogRecord $record The log record to handle
     *
     * @return void
     */
    protected function write(LogRecord $record): void
    {
        $fields = [
            'message'      => is_string($record->formatted) ? $record->formatted : NULL,
            'level'        => $record->level->value,
            'line'         => is_string($record->extra['line'] ?? NULL) ? $record->extra['line'] : NULL,
            'traceID'      => $this->tracingController->getTraceId(),
            'spanID'       => $this->tracingController->getSpanId(),
            'parentSpanID' => $this->tracingController->getParentSpanId(),
        ];

        $tags = [
            'levelName' => $record->level->getName(),
            'channel'   => $record->channel,
            'file'      => is_string($record->extra['file'] ?? NULL) ? $record->extra['file'] : NULL,
            'class'     => is_string($record->extra['class'] ?? NULL) ? $record->extra['class'] : NULL,
            'function'  => is_string($record->extra['function'] ?? NULL) ? $record->extra['function'] : NULL,
        ];

        foreach ($record->extra as $key => $value)
        {
            if (isset($tags[$key]) || isset($fields[$key]))
            {
                continue;
            }

            if (!is_scalar($value))
            {
                continue;
            }

            $fields[(string) $key] = $value;
        }

        if (isset($record->context['exception']) && $record->context['exception'] instanceof Throwable)
        {
            $tags['exception']    = get_class($record->context['exception']);
            $fields['stacktrace'] = $record->context['exception']->getTraceAsString();
        }

        $event = $this->eventLogger->newEvent('php_log');

        $event->setTimestamp($record->datetime->format('Uu'));
        $event->addTags(array_merge($this->tracingController->getSpanSpecificTags(), $tags));
        $event->addFields($fields);
        $event->record(Precision::MicroSeconds);
    }

}

?>
