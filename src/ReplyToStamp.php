<?php

declare(strict_types=1);

namespace Vrok\MessengerReply;

use Symfony\Component\Messenger\Stamp\StampInterface;

class ReplyToStamp implements StampInterface
{
    /**
     * Where to send the reply?
     */
    private string $routingKey;

    /**
     * ReplyToStamp constructor.
     *
     * @param string $routingKey AMQP routing key, where to send the reply (queue name)
     */
    public function __construct(string $routingKey)
    {
        $this->routingKey = $routingKey;
    }

    public function getRoutingKey(): string
    {
        return $this->routingKey;
    }
}
