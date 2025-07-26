<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class RateLimitExceededException extends Exception
{
    private int $retryAfter;
    private ?string $endpoint;
    private array $query;
    private int $nextAttempt;

    public function __construct(string $message, int $retryAfter, ?string $endpoint = null, array $query = [], int $nextAttempt = 0)
    {
        parent::__construct($message);
        $this->retryAfter = $retryAfter;
        $this->endpoint = $endpoint;
        $this->query = $query;
        $this->nextAttempt = $nextAttempt;
    }

    public function getRetryAfter(): int
    {
        return $this->retryAfter;
    }

    public function getEndpoint(): ?string
    {
        return $this->endpoint;
    }

    public function getQuery(): array
    {
        return $this->query;
    }

    public function getNextAttempt(): int
    {
        return $this->nextAttempt;
    }
}
