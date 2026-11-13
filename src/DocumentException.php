<?php

namespace Noop\Documents;

use RuntimeException;
use Throwable;

final class DocumentException extends RuntimeException
{
    public function __construct(string $message, public readonly ?int $status = null, ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
