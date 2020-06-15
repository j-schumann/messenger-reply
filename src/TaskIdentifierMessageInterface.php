<?php
declare(strict_types=1);

namespace Vrok\MessengerReply;

interface TaskIdentifierMessageInterface
{
    public function getIdentifier(): ?string;
    public function getTask(): ?string;
    public function setIdentifier(?string $identifier): self;
    public function setTask(?string $identifier): self;
}
