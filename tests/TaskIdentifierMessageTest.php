<?php

declare(strict_types=1);

namespace Vrok\MessengerReply\Tests;

use PHPUnit\Framework\TestCase;

final class TaskIdentifierMessageTest extends TestCase
{
    public function testTraitProperties(): void
    {
        $msg = new TestMessage();
        $msg->setIdentifier('123');
        $msg->setTask('research');

        self::assertEquals('123', $msg->getIdentifier());
        self::assertSame('research', $msg->getTask());
    }
}
