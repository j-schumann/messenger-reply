<?php
declare(strict_types=1);

namespace Vrok\MessengerReply\Tests;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\TransportMessageIdStamp;

class TestMiddleware implements MiddlewareInterface
{
    public $called = false;

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $this->called = true;
        return $envelope->with(new TransportMessageIdStamp('999'));
    }
}
