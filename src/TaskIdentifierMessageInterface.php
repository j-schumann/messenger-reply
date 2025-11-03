<?php

declare(strict_types=1);

namespace Vrok\MessengerReply;

/**
 * When replying to messages with this interface with a message also implementing
 * this interface the identifiers are automatically transferred.
 */
interface TaskIdentifierMessageInterface
{
    public function getIdentifier(): ?string;

    public function getTask(): ?string;

    public function setIdentifier(?string $identifier): self;

    public function setTask(?string $identifier): self;
}
