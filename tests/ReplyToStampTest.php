<?php

declare(strict_types=1);

namespace Vrok\MessengerReply\Tests;

use PHPUnit\Framework\TestCase;
use Vrok\MessengerReply\ReplyToStamp;

final class ReplyToStampTest extends TestCase
{
    public function testProperties(): void
    {
        $stamp = new ReplyToStamp('output');

        self::assertSame('output', $stamp->getRoutingKey());
    }
}
