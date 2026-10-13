<?php

namespace Vrok\MessengerReply\Tests;

use Vrok\MessengerReply\TaskIdentifierMessageInterface;
use Vrok\MessengerReply\TaskIdentifierMessageTrait;

class TestReply implements TaskIdentifierMessageInterface
{
    use TaskIdentifierMessageTrait;
}
