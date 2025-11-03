<?php

declare(strict_types=1);

namespace Vrok\MessengerReply;

use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpStamp;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

class ReplyMiddleware implements MiddlewareInterface
{
    use LoggerAwareTrait;

    public const MSG_RECEIVED_MESSAGE = '{middleware} received message {class}';
    public const MSG_NO_HANDLED_STAMP = 'Message {class} has a replyTo stamp but was not handled before being passed to the ReplyMiddleware, wrong middleware order?';

    public function __construct(private MessageBusInterface $bus)
    {
        $this->logger = new NullLogger();
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $context = [
            'message' => $envelope->getMessage(),
            'class'   => $envelope->getMessage()::class,
        ];

        $this->logger->debug(
            self::MSG_RECEIVED_MESSAGE,
            $context + ['middleware' => self::class]
        );

        /** @var ReplyToStamp $replyToStamp */
        $replyToStamp = $envelope->last(ReplyToStamp::class);
        if (!$replyToStamp) {
            // no reply requested, do nothing
            return $stack->next()->handle($envelope, $stack);
        }

        /** @var HandledStamp $handledStamp */
        $handledStamp = $envelope->last(HandledStamp::class);
        if (!$handledStamp) {
            // not yet handled, nothing to do
            $this->logger->warning(
                self::MSG_NO_HANDLED_STAMP,
                $context
            );

            return $stack->next()->handle($envelope, $stack);
        }

        $result = $handledStamp->getResult();
        if (!\is_object($result)) {
            throw new UnrecoverableMessageHandlingException("Result returned by handler {$handledStamp->getHandlerName()} must be a serializable Message object to be a valid reply!");
        }

        // copy the identifying properties to the reply so the original sender
        // knows for which request this reply is
        if ($result instanceof TaskIdentifierMessageInterface
            && $context['message'] instanceof TaskIdentifierMessageInterface
        ) {
            $result->setIdentifier($context['message']->getIdentifier());
            $result->setTask($context['message']->getTask());
        }

        // Reply with the handler's result, route to the "replyTo address".
        // It's the handler's responsibility to return a serializable message.
        $reply = (new Envelope($result))
            ->with(new AmqpStamp($replyToStamp->getRoutingKey()));

        $this->logger->info(
            'Replying to handled message {class}: routing {reply} to key {routingKey}',
            $context + [
                'reply'      => $result::class,
                'routingKey' => $replyToStamp->getRoutingKey(),
            ]
        );
        $this->bus->dispatch($reply);

        // resume with middleware, if any further...
        return $stack->next()->handle($envelope, $stack);
    }
}
