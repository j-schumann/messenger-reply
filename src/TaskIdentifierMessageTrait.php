<?php
declare(strict_types=1);

namespace Vrok\MessengerReply;

trait TaskIdentifierMessageTrait
{
    /**
     * Optional: a message/task identifier
     *
     * @var string|null
     */
    protected ?string $identifier = null;

    /**
     * Optional a task identifier
     *
     * @var string|null
     */
    protected ?string $task = null;

    /**
     * @return string|null
     */
    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    /**
     * @param string|null $identifier
     * @return self
     */
    public function setIdentifier(?string $identifier): self
    {
        $this->identifier = $identifier;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getTask(): ?string
    {
        return $this->task;
    }

    /**
     * @param string|null $task
     * @return self
     */
    public function setTask(?string $task): self
    {
        $this->task = $task;
        return $this;
    }
}
