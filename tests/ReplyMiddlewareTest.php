<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace Vrok\MessengerReply\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpStamp;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Middleware\StackMiddleware;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Messenger\Stamp\TransportMessageIdStamp;
use Vrok\MessengerReply\ReplyMiddleware;
use Vrok\MessengerReply\ReplyToStamp;

final class ReplyMiddlewareTest extends TestCase
{
    public function testConstruction(): void
    {
        $bus = $this->createStub(MessageBusInterface::class);
        $logger = $this->createStub(LoggerInterface::class);

        $mw = new ReplyMiddleware($bus);
        $mw->setLogger($logger);
    }

    public function testIgnoresMessageWithoutReplyTo(): void
    {
        $bus = $this->createMock(MessageBusInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        // it will not dispatch a new message but log a debug message showing
        // the middleware was called
        $bus->expects($this->never())
            ->method('dispatch');
        $logger->expects($this->once())
            ->method('debug')
            ->with(ReplyMiddleware::MSG_RECEIVED_MESSAGE);

        $mw = new ReplyMiddleware($bus);
        $mw->setLogger($logger);

        // we cannot mock StackInterface because of:
        // Class "Symfony\Component\Messenger\Envelope" is declared "final" and cannot be mocked.
        $testMw = new TestMiddleware();
        $stack = new StackMiddleware($testMw);

        $envelope = new Envelope(new \stdClass());
        $res = $mw->handle($envelope, $stack);

        // it called the next middleware
        self::assertTrue($testMw->called);

        // it returned the result of the next middleware
        self::assertNotNull($res->last(TransportMessageIdStamp::class));

        // it did _not_ attach a reply stamp
        self::assertNull($res->last(AmqpStamp::class));
    }

    public function testRequiresHandledStamp(): void
    {
        $bus = $this->createMock(MessageBusInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        // it will not dispatch a new message but log a warning
        $bus->expects($this->never())
            ->method('dispatch');
        $logger->expects($this->once())
            ->method('warning')
            ->with(ReplyMiddleware::MSG_NO_HANDLED_STAMP);

        $mw = new ReplyMiddleware($bus);
        $mw->setLogger($logger);

        // we cannot mock StackInterface because of:
        // Class "Symfony\Component\Messenger\Envelope" is declared "final" and cannot be mocked.
        $testMw = new TestMiddleware();
        $stack = new StackMiddleware($testMw);

        $envelope = new Envelope(new \stdClass());
        $res = $mw->handle($envelope
            ->with(new ReplyToStamp('output')), $stack);

        // it called the next middleware
        self::assertTrue($testMw->called);

        // it returned the result of the next middleware
        self::assertNotNull($res->last(TransportMessageIdStamp::class));

        // it did _not_ attach a reply stamp
        self::assertNull($res->last(AmqpStamp::class));
    }

    public function testRequiresResultObject(): void
    {
        $bus = $this->createMock(MessageBusInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        // it will not dispatch a new message but throw an error
        $bus->expects($this->never())
            ->method('dispatch');
        $this->expectException(UnrecoverableMessageHandlingException::class);

        $mw = new ReplyMiddleware($bus);
        $mw->setLogger($logger);
        $stack = new StackMiddleware([$mw]);

        $envelope = new Envelope(new \stdClass());
        $mw->handle($envelope
            ->with(new ReplyToStamp('output'))
            ->with(new HandledStamp(123, 'TestHandler')), $stack);
    }

    public function testDispatchesReply(): void
    {
        // we cannot use a stub here because PHPUnit tries to mock the return type of dispatch(),
        // even when we use ->will($this->returnArgument(0)), and results in:
        // Class "Symfony\Component\Messenger\Envelope" is declared "final" and cannot be mocked.
        $bus = new TestBus();

        $logger = $this->createMock(LoggerInterface::class);

        $mw = new ReplyMiddleware($bus);
        $mw->setLogger($logger);

        // we cannot mock StackInterface because of:
        // Class "Symfony\Component\Messenger\Envelope" is declared "final" and cannot be mocked.
        $testMw = new TestMiddleware();
        $stack = new StackMiddleware($testMw);

        $envelope = new Envelope(new TestMessage());
        $replyTo = new ReplyToStamp('output');

        $res = $mw->handle($envelope
            ->with($replyTo)
            ->with(new HandledStamp(new TestReply(), 'TestHandler')), $stack);

        // the dispatch() method was called
        self::assertNotNull($bus->envelope);

        // the routing key was added on the given reply
        $amqpStamp = $bus->envelope->last(AmqpStamp::class);
        self::assertNotNull($amqpStamp);
        self::assertSame('output', $amqpStamp->getRoutingKey());

        // it dispatched our given handler result
        self::assertInstanceOf(TestReply::class, $bus->envelope->getMessage());

        // it called the next middleware
        self::assertTrue($testMw->called);

        // it returned the result of the next middleware
        self::assertNotNull($res->last(TransportMessageIdStamp::class));
    }

    public function testTransfersTaskIdentifier(): void
    {
        // we cannot use a stub here because PHPUnit tries to mock the return type of dispatch(),
        // even when we use ->will($this->returnArgument(0)), and results in:
        // Class "Symfony\Component\Messenger\Envelope" is declared "final" and cannot be mocked.
        $bus = new TestBus();

        $mw = new ReplyMiddleware($bus);

        // we cannot mock StackInterface because of:
        // Class "Symfony\Component\Messenger\Envelope" is declared "final" and cannot be mocked.
        $testMw = new TestMiddleware();
        $stack = new StackMiddleware($testMw);

        $input = new TestMessage();
        $input->setIdentifier('123');
        $input->setTask('research');

        $envelope = new Envelope($input);
        $replyTo = new ReplyToStamp('output');

        $mw->handle(
            $envelope->with($replyTo)
                ->with(new HandledStamp(new TestReply(), 'TestHandler')),
            $stack
        );

        // it returned the given reply
        self::assertInstanceOf(TestReply::class, $bus->envelope->getMessage());

        // it copied to identifier properties from the input message
        self::assertSame('123', $bus->envelope->getMessage()->getIdentifier());
        self::assertSame('research', $bus->envelope->getMessage()->getTask());
    }
}
