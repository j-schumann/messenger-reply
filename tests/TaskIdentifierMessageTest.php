<?php
declare(strict_types=1);

namespace Vrok\MessengerReply\Tests;

use PHPUnit\Framework\TestCase;
use Vrok\MessengerReply\ReplyToStamp;

class TaskIdentifierMessageTest extends TestCase
{
    public function testTraitProperties()
    {
        $msg = new TestMessage();
        $msg->setIdentifier('123');
        $msg->setTask('research');

        $this->assertEquals('123', $msg->getIdentifier());
        $this->assertEquals('research', $msg->getTask());
    }
}
