<?php
declare(strict_types=1);

namespace Vrok\MessengerReply\Tests;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

class TestBus implements MessageBusInterface
{
    public Envelope $envelope;

    public function dispatch($message, array $stamps = []): Envelope
    {
        $this->envelope = $message instanceof Envelope
            ? $message
            : new Envelope($message);
        return $this->envelope;
    }
}
