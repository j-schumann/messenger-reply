<?php

declare(strict_types=1);

namespace Vrok\MessengerReply\Tests;

use Vrok\MessengerReply\TaskIdentifierMessageInterface;
use Vrok\MessengerReply\TaskIdentifierMessageTrait;

class TestMessage implements TaskIdentifierMessageInterface
{
    use TaskIdentifierMessageTrait;
}
