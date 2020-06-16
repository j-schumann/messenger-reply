# vrok/messenger-reply

This is a library to allow symfony/messenger to reply to messages with a result.  
This is meant to be used in a setup with AMQP transport and two symfony instances
talking to each other over the same broker: e.g. a web frontend and a
microservice, where the frontend sends tasks to the service and requests a
reply, for example a generated PDF file.

[![Build Status](https://travis-ci.org/j-schumann/messenger-reply.svg?branch=master)](https://travis-ci.org/j-schumann/messenger-reply)
[![Coverage Status](https://coveralls.io/repos/github/j-schumann/messenger-reply/badge.svg?branch=master)](https://coveralls.io/github/j-schumann/messenger-reply?branch=master)

## Setup

Installation on both sides (You need the ReplyToStamp on the request side and the
middleware + stamp on the receiving side):
`composer require vrok/messenger-reply`

You need request and reply message classes that are equal on both sides, e.g.
use a shared composer package:

```
namespace MyNamespace\Message;

class GeneratePdfMessage
{
    /**
     * @var string
     */
    private string $latex;

    public function __construct(string $latex)
    {
        $this->latex = $latex;
    }

    /**
     * @return string
     */
    public function getLatex(): string
    {
        return $this->latex;
    }
}

...

namespace MyNamespace\Message;

class PdfResultMessage
{
    private string $pdfContent;

    public function __construct(string $pdfContent)
    {
        $this->pdfContent = $pdfContent;
    }

    /**
     * @return string
     */
    public function getPdfContent(): string
    {
        return $this->pdfContent;
    }
}
```

### Requesting side

Add a transport for the shared AMQP broker, routing to the _input_ of the
receiver (having the same exchange and queue name as the receivers _input_ queue).

```
framework:
    messenger:
        transports:
            pdf-requests:
                dsn: '%env(MESSENGER_TRANSPORT_DSN)%'
                options:
                    exchange:
                        name: pdf-service
                        type: direct
                        default_publish_routing_key: input
                    queues:
                        input:
                            binding_keys: [input]
                retry_strategy:
                    max_retries: 3
                    # milliseconds delay
                    delay: 1000
                    # causes the delay to be higher before each retry
                    # e.g. 1 second delay, 2 seconds, 4 seconds
                    multiplier: 2
                    max_delay: 0
```


Add a transport for the shared AMQP broker, for receiving the replies (matching
the exchange and queue name of the receivers _output_ queue):
We need separate transports as `messenger:consume [transportname]` consumes
all messages in all queues for that transport.

```
framework:
    messenger:
        transports:
            pdf-results:
                dsn: '%env(MESSENGER_TRANSPORT_DSN)%'
                options:
                    exchange:
                        name: pdf-service
                        type: direct
                        default_publish_routing_key: output
                    queues:
                        input:
                            binding_keys: [output]
                retry_strategy:
                    max_retries: 3
                    # milliseconds delay
                    delay: 1000
                    # causes the delay to be higher before each retry
                    # e.g. 1 second delay, 2 seconds, 4 seconds
                    multiplier: 2
                    max_delay: 0
```

Route your requests to the shared transport/queue:

```
framework:
    messenger:
        routing:
            # e.g. 
            'MyNamespace\GeneratePdfMessage': pdf-requests
```

### Replying side

Configure the middleware on your message bus:  
(We have to disable the default middleware and explicitly define the order as
there is no priority option, just adding our service to the middleware-option
would add it before send_middleware. See https://github.com/symfony/symfony/issues/28568)

```
framework:
    messenger:
        buses:
            messenger.bus.default:
                default_middleware: false
                middleware:
                    - {id: 'add_bus_name_stamp_middleware', arguments: ['messenger.bus.default']}
                    - reject_redelivered_message_middleware
                    - dispatch_after_current_bus
                    - failed_message_processing_middleware
                    - send_message
                    - handle_message
                    - App\Messenger\ReplyMiddleware
```

Configure an _input_ transport where you send messages from the external
applications, which are consumed by your worker(s).
And an _output_ transport where the replies are sent, optimally with one queue
for each application sending requests, so the only consume the
replies meant for them.
We need separate transports as `messenger:consume [transportname]` consumes
all messages in all queues for that transport.

```
framework:
    messenger:
        transports:
            input:
                dsn: '%env(MESSENGER_TRANSPORT_DSN)%'
                options:
                    exchange:
                        name: pdf-service
                        type: direct
                        default_publish_routing_key: input
                    queues:
                        input:
                            binding_keys: [input]
                retry_strategy:
                    max_retries: 3
                    # milliseconds delay
                    delay: 1000
                    # causes the delay to be higher before each retry
                    # e.g. 1 second delay, 2 seconds, 4 seconds
                    multiplier: 2
                    max_delay: 0

            output:
                dsn: '%env(MESSENGER_TRANSPORT_DSN)%'
                options:
                    exchange:
                        name: pdf-service
                        type: direct
                        default_publish_routing_key: output
                    queues:
                        output:
                            binding_keys: [output]
                        output_1:
                            binding_keys: [output_1]
                retry_strategy:
                    max_retries: 3
                    # milliseconds delay
                    delay: 1000
                    # causes the delay to be higher before each retry
                    # e.g. 1 second delay, 2 seconds, 4 seconds
                    multiplier: 2
                    max_delay: 0
```

All replies should be routed to the output transport:

```
framework:
    messenger:
        routing:
            # Route your messages to the transports
            '*': output
```

## Usage

Dispatch the request message with the attached ReplyStamp so the
receiver knows where to send the replies:

```
    use MyNamespace\GeneratePdfMessage;
    use Vrok\MessengerReply\ReplyToStamp;

    $e = new Envelope(new GeneratePdfMessage('LaTeX content'));
    $this->bus->dispatch($e
        ->with(new ReplyToStamp('output'))
    );
```

Implement a MessageHandler that handles the requests and returns the reply
Message object:

```
use MyNamespace\GeneratePdfMessage
use MyNamespace\PdfResultMessage

class GeneratePdfMessageHandler implements
    MessageHandlerInterface
{
    public function __invoke(GeneratePdfMessage $message): PdfResultMessage
    {
        $LaTeX = $message->getLatex();
        $pdfContent = "<fakepdf>$LaTeX</fakePdf>";
        $reply = new PdfResultMessage($pdfContent);
        return $reply;
    }
}
```

Consume requests (only on the _input_ queue!) on the receiver side:

`./bin/console messenger:consume input`

Consume replies (only on the _output_ queue!) on the requesting side:

`./bin/console messenger:consume pdf-results`

If you need to know on the requesting side which task to resume etc. with the
received reply, you can implement the `Vrok\MessengerReply\TaskIdentifierMessageInterface` 
(and use the `Vrok\MessengerReply\TaskIdentifierMessageTrait`) on your request 
and reply message classes to automatically transfer the _task_ and/or _identifier_
properties given on the request to the reply. We cannot use stamps for this as
stamps are not accessible by MessageHandlers.
