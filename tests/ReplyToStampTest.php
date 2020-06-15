<?php
declare(strict_types=1);

namespace Vrok\MessengerReply\Tests;

use PHPUnit\Framework\TestCase;
use Vrok\MessengerReply\ReplyToStamp;

class ReplyToStampTest extends TestCase
{
    public function testProperties()
    {
        $stamp = new ReplyToStamp('output');

        $this->assertEquals('output', $stamp->getRoutingKey());
    }
}
