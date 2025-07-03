<?php

/**
 * This file contains the ProfilerBaseTest class.
 *
 * SPDX-FileCopyrightText: Copyright 2025 Framna Netherland B.V., Zwolle, The Netherlands
 * SPDX-License-Identifier: MIT
 */

namespace Lunr\Ticks\Monolog\Tests;

use Exception;
use Lunr\Ticks\EventLogging\EventInterface;
use Lunr\Ticks\EventLogging\EventLoggerInterface;
use Lunr\Ticks\Monolog\ProcessingHandler;
use Lunr\Ticks\TracingControllerInterface;
use Lunr\Ticks\TracingInfoInterface;
use Monolog\Formatter\LineFormatter;
use Monolog\Level;
use Monolog\Test\MonologTestCase;
use RuntimeException;
use stdClass;

/**
 * Tests for the Lunr.Ticks processing handler
 *
 * @covers \Lunr\Ticks\Monolog\ProcessingHandler
 */
class ProcessingHandlerTest extends MonologTestCase
{

    /**
     * Tests the processing handler implementation.
     *
     * @covers \Lunr\Ticks\Monolog\ProcessingHandler::handle
     */
    public function testHandleWithTraceIDUnavailable(): void
    {
        $record = $this->getRecord(Level::Warning, 'test', [ 'data' => new stdClass(), 'foo' => 34 ]);

        $event = $this->createMock(EventInterface::class);

        $event->expects($this->once())
              ->method('setTimestamp')
              ->with($record->datetime->format('Uu'));

        $event->expects($this->never())
              ->method('addTags');

        $event->expects($this->never())
              ->method('addFields');

        $event->expects($this->never())
              ->method('setTraceId');

        $event->expects($this->never())
              ->method('setSpanId');

        $event->expects($this->never())
              ->method('setParentSpanId');

        $eventLogger = $this->createMock(EventLoggerInterface::class);

        $eventLogger->expects($this->once())
                    ->method('newEvent')
                    ->willReturn($event);

        $controller = $this->createMockForIntersectionOfInterfaces([
            TracingControllerInterface::class,
            TracingInfoInterface::class,
        ]);

        $controller->expects($this->once())
                   ->method('getTraceId')
                   ->willReturn(NULL);

        $controller->expects($this->never())
                   ->method('getSpanId');

        $controller->expects($this->never())
                   ->method('getParentSpanId');

        $controller->expects($this->never())
                   ->method('getSpanSpecificTags');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Trace ID not available!');

        $handler = new ProcessingHandler($eventLogger, $controller);
        $handler->handle($record);
    }

    /**
     * Tests the processing handler implementation.
     *
     * @covers \Lunr\Ticks\Monolog\ProcessingHandler::handle
     */
    public function testHandleWithSpanIDUnavailable(): void
    {
        $traceID = '7b333e15-aa78-4957-a402-731aecbb358e';

        $record = $this->getRecord(Level::Warning, 'test', [ 'data' => new stdClass(), 'foo' => 34 ]);

        $event = $this->createMock(EventInterface::class);

        $event->expects($this->once())
              ->method('setTimestamp')
              ->with($record->datetime->format('Uu'));

        $event->expects($this->never())
              ->method('addTags');

        $event->expects($this->never())
              ->method('addFields');

        $event->expects($this->once())
              ->method('setTraceId')
              ->with($traceID);

        $event->expects($this->never())
              ->method('setSpanId');

        $event->expects($this->never())
              ->method('setParentSpanId');

        $eventLogger = $this->createMock(EventLoggerInterface::class);

        $eventLogger->expects($this->once())
                    ->method('newEvent')
                    ->willReturn($event);

        $controller = $this->createMockForIntersectionOfInterfaces([
            TracingControllerInterface::class,
            TracingInfoInterface::class,
        ]);

        $controller->expects($this->once())
                   ->method('getTraceId')
                   ->willReturn($traceID);

        $controller->expects($this->once())
                   ->method('getSpanId')
                   ->willReturn(NULL);

        $controller->expects($this->never())
                   ->method('getParentSpanId');

        $controller->expects($this->never())
                   ->method('getSpanSpecificTags');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Span ID not available!');

        $handler = new ProcessingHandler($eventLogger, $controller);
        $handler->handle($record);
    }

    /**
     * Tests the processing handler implementation.
     *
     * @covers \Lunr\Ticks\Monolog\ProcessingHandler::handle
     */
    public function testHandleWithParentSpanIDUnavailable(): void
    {
        $traceID = '7b333e15-aa78-4957-a402-731aecbb358e';
        $spanID  = '24ec5f90-7458-4dd5-bb51-7a1e8f4baafe';

        $record = $this->getRecord(Level::Warning, 'test', [ 'data' => new stdClass(), 'foo' => 34 ]);

        $event = $this->createMock(EventInterface::class);

        $event->expects($this->once())
              ->method('setTimestamp')
              ->with($record->datetime->format('Uu'));

        $event->expects($this->once())
              ->method('addTags')
              ->with([
                  'call'      => 'controller/method',
                  'levelName' => $record->level->getName(),
                  'channel'   => $record->channel,
                  'file'      => NULL,
                  'class'     => NULL,
                  'function'  => NULL,
              ]);

        $event->expects($this->once())
              ->method('addFields')
              ->with([
                  'message' => (new LineFormatter())->format($record),
                  'level'   => $record->level->value,
                  'line'    => NULL,
              ]);
        $event->expects($this->once())
              ->method('setTraceId')
              ->with($traceID);

        $event->expects($this->once())
              ->method('setSpanId')
              ->with($spanID);

        $event->expects($this->never())
              ->method('setParentSpanId');

        $eventLogger = $this->createMock(EventLoggerInterface::class);

        $eventLogger->expects($this->once())
                    ->method('newEvent')
                    ->willReturn($event);

        $controller = $this->createMockForIntersectionOfInterfaces([
            TracingControllerInterface::class,
            TracingInfoInterface::class,
        ]);

        $controller->expects($this->once())
                   ->method('getTraceId')
                   ->willReturn($traceID);

        $controller->expects($this->once())
                   ->method('getSpanId')
                   ->willReturn($spanID);

        $controller->expects($this->once())
                   ->method('getParentSpanId')
                   ->willReturn(NULL);

        $controller->expects($this->once())
                   ->method('getSpanSpecificTags')
                   ->willReturn([ 'call' => 'controller/method' ]);

        $handler = new ProcessingHandler($eventLogger, $controller);
        $handler->handle($record);
    }

    /**
     * Tests the processing handler implementation.
     *
     * @covers \Lunr\Ticks\Monolog\ProcessingHandler::handle
     */
    public function testHandle(): void
    {
        $traceID      = '7b333e15-aa78-4957-a402-731aecbb358e';
        $spanID       = '24ec5f90-7458-4dd5-bb51-7a1e8f4baafe';
        $parentSpanID = '8b1f87b5-8383-4413-a341-7619cd4b9948';

        $record = $this->getRecord(Level::Warning, 'test', [ 'data' => new stdClass(), 'foo' => 34 ]);

        $event = $this->createMock(EventInterface::class);

        $event->expects($this->once())
              ->method('setTimestamp')
              ->with($record->datetime->format('Uu'));

        $event->expects($this->once())
              ->method('addTags')
              ->with([
                  'call'      => 'controller/method',
                  'levelName' => $record->level->getName(),
                  'channel'   => $record->channel,
                  'file'      => NULL,
                  'class'     => NULL,
                  'function'  => NULL,
              ]);

        $event->expects($this->once())
              ->method('addFields')
              ->with([
                  'message' => (new LineFormatter())->format($record),
                  'level'   => $record->level->value,
                  'line'    => NULL,
              ]);

        $event->expects($this->once())
              ->method('setTraceId')
              ->with($traceID);

        $event->expects($this->once())
              ->method('setSpanId')
              ->with($spanID);

        $event->expects($this->once())
              ->method('setParentSpanId')
              ->with($parentSpanID);

        $eventLogger = $this->createMock(EventLoggerInterface::class);

        $eventLogger->expects($this->once())
                    ->method('newEvent')
                    ->willReturn($event);

        $controller = $this->createMockForIntersectionOfInterfaces([
            TracingControllerInterface::class,
            TracingInfoInterface::class,
        ]);

        $controller->expects($this->once())
                   ->method('getTraceId')
                   ->willReturn($traceID);

        $controller->expects($this->once())
                   ->method('getSpanId')
                   ->willReturn($spanID);

        $controller->expects($this->once())
                   ->method('getParentSpanId')
                   ->willReturn($parentSpanID);

        $controller->expects($this->once())
                   ->method('getSpanSpecificTags')
                   ->willReturn([ 'call' => 'controller/method' ]);

        $handler = new ProcessingHandler($eventLogger, $controller);
        $handler->handle($record);
    }

    /**
     * Tests the processing handler implementation with introspection data.
     *
     * @covers \Lunr\Ticks\Monolog\ProcessingHandler::handle
     */
    public function testHandleWithIntrospectionData(): void
    {
        $traceID      = '7b333e15-aa78-4957-a402-731aecbb358e';
        $spanID       = '24ec5f90-7458-4dd5-bb51-7a1e8f4baafe';
        $parentSpanID = '8b1f87b5-8383-4413-a341-7619cd4b9948';

        $record = $this->getRecord(Level::Warning, 'test', [ 'data' => new stdClass(), 'foo' => 34 ]);

        $record->extra = [
            'file'     => '/path/to/Foo.php',
            'line'     => 101,
            'class'    => 'Foo',
            'function' => 'bar',
        ];

        $event = $this->createMock(EventInterface::class);

        $event->expects($this->once())
              ->method('setTimestamp')
              ->with($record->datetime->format('Uu'));

        $event->expects($this->once())
              ->method('addTags')
              ->with([
                  'call'      => 'controller/method',
                  'levelName' => $record->level->getName(),
                  'channel'   => $record->channel,
                  'file'      => '/path/to/Foo.php',
                  'class'     => 'Foo',
                  'function'  => 'bar',
              ]);

        $event->expects($this->once())
              ->method('addFields')
              ->with([
                  'message' => (new LineFormatter())->format($record),
                  'level'   => $record->level->value,
                  'line'    => 101,
              ]);

        $event->expects($this->once())
              ->method('setTraceId')
              ->with($traceID);

        $event->expects($this->once())
              ->method('setSpanId')
              ->with($spanID);

        $event->expects($this->once())
              ->method('setParentSpanId')
              ->with($parentSpanID);

        $eventLogger = $this->createMock(EventLoggerInterface::class);

        $eventLogger->expects($this->once())
                    ->method('newEvent')
                    ->willReturn($event);

        $controller = $this->createMockForIntersectionOfInterfaces([
            TracingControllerInterface::class,
            TracingInfoInterface::class,
        ]);

        $controller->expects($this->once())
                   ->method('getTraceId')
                   ->willReturn($traceID);

        $controller->expects($this->once())
                   ->method('getSpanId')
                   ->willReturn($spanID);

        $controller->expects($this->once())
                   ->method('getParentSpanId')
                   ->willReturn($parentSpanID);

        $controller->expects($this->once())
                   ->method('getSpanSpecificTags')
                   ->willReturn([ 'call' => 'controller/method' ]);

        $handler = new ProcessingHandler($eventLogger, $controller);
        $handler->handle($record);
    }

    /**
     * Tests the processing handler implementation with exception data.
     *
     * @covers \Lunr\Ticks\Monolog\ProcessingHandler::handle
     */
    public function testHandleWithExceptionData(): void
    {
        $traceID      = '7b333e15-aa78-4957-a402-731aecbb358e';
        $spanID       = '24ec5f90-7458-4dd5-bb51-7a1e8f4baafe';
        $parentSpanID = '8b1f87b5-8383-4413-a341-7619cd4b9948';

        $exception = new Exception('Something went wrong!');

        $context = [
            'data'      => new stdClass(),
            'foo'       => 34,
            'exception' => $exception,
        ];

        $record = $this->getRecord(Level::Warning, 'test', $context);

        $event = $this->createMock(EventInterface::class);

        $event->expects($this->once())
              ->method('setTimestamp')
              ->with($record->datetime->format('Uu'));

        $event->expects($this->once())
              ->method('addTags')
              ->with([
                  'call'      => 'controller/method',
                  'levelName' => $record->level->getName(),
                  'channel'   => $record->channel,
                  'file'      => NULL,
                  'class'     => NULL,
                  'function'  => NULL,
                  'exception' => 'Exception',
              ]);

        $event->expects($this->once())
              ->method('addFields')
              ->with([
                  'message'    => (new LineFormatter())->format($record),
                  'level'      => $record->level->value,
                  'line'       => NULL,
                  'stacktrace' => $exception->getTraceAsString(),
              ]);

        $event->expects($this->once())
              ->method('setTraceId')
              ->with($traceID);

        $event->expects($this->once())
              ->method('setSpanId')
              ->with($spanID);

        $event->expects($this->once())
              ->method('setParentSpanId')
              ->with($parentSpanID);

        $eventLogger = $this->createMock(EventLoggerInterface::class);

        $eventLogger->expects($this->once())
                    ->method('newEvent')
                    ->willReturn($event);

        $controller = $this->createMockForIntersectionOfInterfaces([
            TracingControllerInterface::class,
            TracingInfoInterface::class,
        ]);

        $controller->expects($this->once())
                   ->method('getTraceId')
                   ->willReturn($traceID);

        $controller->expects($this->once())
                   ->method('getSpanId')
                   ->willReturn($spanID);

        $controller->expects($this->once())
                   ->method('getParentSpanId')
                   ->willReturn($parentSpanID);

        $controller->expects($this->once())
                   ->method('getSpanSpecificTags')
                   ->willReturn([ 'call' => 'controller/method' ]);

        $handler = new ProcessingHandler($eventLogger, $controller);
        $handler->handle($record);
    }

    /**
     * Tests the processing handler implementation with generic extra data.
     *
     * @covers \Lunr\Ticks\Monolog\ProcessingHandler::handle
     */
    public function testHandleWithGenericExtraData(): void
    {
        $traceID      = '7b333e15-aa78-4957-a402-731aecbb358e';
        $spanID       = '24ec5f90-7458-4dd5-bb51-7a1e8f4baafe';
        $parentSpanID = '8b1f87b5-8383-4413-a341-7619cd4b9948';

        $record = $this->getRecord(Level::Warning, 'test', [ 'data' => new stdClass(), 'foo' => 34 ]);

        $record->extra = [
            'meta'   => 'bar',
            'object' => new stdClass(),
        ];

        $event = $this->createMock(EventInterface::class);

        $event->expects($this->once())
              ->method('setTimestamp')
              ->with($record->datetime->format('Uu'));

        $event->expects($this->once())
              ->method('addTags')
              ->with([
                  'call'      => 'controller/method',
                  'levelName' => $record->level->getName(),
                  'channel'   => $record->channel,
                  'file'      => NULL,
                  'class'     => NULL,
                  'function'  => NULL,
              ]);

        $event->expects($this->once())
              ->method('addFields')
              ->with([
                  'message' => (new LineFormatter())->format($record),
                  'level'   => $record->level->value,
                  'line'    => NULL,
                  'meta'    => 'bar',
              ]);

        $event->expects($this->once())
              ->method('setTraceId')
              ->with($traceID);

        $event->expects($this->once())
              ->method('setSpanId')
              ->with($spanID);

        $event->expects($this->once())
              ->method('setParentSpanId')
              ->with($parentSpanID);

        $eventLogger = $this->createMock(EventLoggerInterface::class);

        $eventLogger->expects($this->once())
                    ->method('newEvent')
                    ->willReturn($event);

        $controller = $this->createMockForIntersectionOfInterfaces([
            TracingControllerInterface::class,
            TracingInfoInterface::class,
        ]);

        $controller->expects($this->once())
                   ->method('getTraceId')
                   ->willReturn($traceID);

        $controller->expects($this->once())
                   ->method('getSpanId')
                   ->willReturn($spanID);

        $controller->expects($this->once())
                   ->method('getParentSpanId')
                   ->willReturn($parentSpanID);

        $controller->expects($this->once())
                   ->method('getSpanSpecificTags')
                   ->willReturn([ 'call' => 'controller/method' ]);

        $handler = new ProcessingHandler($eventLogger, $controller);
        $handler->handle($record);
    }

}

?>
