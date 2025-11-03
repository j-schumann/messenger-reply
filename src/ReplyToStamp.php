<?php

declare(strict_types=1);

namespace Vrok\MessengerReply;

use Symfony\Component\Messenger\Stamp\StampInterface;

class ReplyToStamp implements StampInterface
{
    /**
     * ReplyToStamp constructor.
     *
     * @param string $routingKey AMQP routing key, where to send the reply (queue name)
     */
    public function __construct(private readonly string $routingKey)
    {
    }

    public function getRoutingKey(): string
    {
        return $this->routingKey;
    }
}
