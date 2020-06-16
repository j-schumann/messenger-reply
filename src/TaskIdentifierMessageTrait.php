<?php

declare(strict_types=1);

namespace Vrok\MessengerReply;

/**
 * Implements TaskIdentifierMessageInterface
 */
trait TaskIdentifierMessageTrait
{
    /**
     * Optional: a message/task identifier.
     */
    protected ?string $identifier = null;

    /**
     * Optional: a task identifier.
     */
    protected ?string $task = null;

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function setIdentifier(?string $identifier): self
    {
        $this->identifier = $identifier;

        return $this;
    }

    public function getTask(): ?string
    {
        return $this->task;
    }

    public function setTask(?string $task): self
    {
        $this->task = $task;

        return $this;
    }
}
