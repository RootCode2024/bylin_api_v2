<?php

declare(strict_types=1);

namespace Modules\Core\Exceptions;

use Exception;
use Throwable;

/**
 * Business logic exception
 *
 * Used for expected business rule violations and validation errors
 */
class BusinessException extends Exception
{
    protected int $statusCode = 422;

    /**
     * Create a new business exception
     *
     * @param string $message The exception message
     * @param int|string $code The exception code (will be converted to int)
     * @param Throwable|null $previous The previous exception
     * @param int $statusCode HTTP status code for API responses
     */
    public function __construct(
        string $message = "",
        $code = 0,
        ?Throwable $previous = null,
        int $statusCode = 422
    ) {
        // Convert string codes to integer to prevent TypeError
        // This handles PostgreSQL error codes like "42703" or "23505"
        $intCode = is_numeric($code) ? (int) $code : 0;

        $this->statusCode = $statusCode;

        parent::__construct($message, $intCode, $previous);
    }

    /**
     * Get HTTP status code for API responses
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Set HTTP status code
     */
    public function setStatusCode(int $statusCode): self
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    /**
     * Get the original code (before conversion)
     * Useful if you need the original string code from database
     */
    public function getOriginalCode()
    {
        return $this->getPrevious() ? $this->getPrevious()->getCode() : $this->code;
    }

    /**
     * Check if this is a database constraint violation
     */
    public function isDatabaseConstraintViolation(): bool
    {
        if ($previous = $this->getPrevious()) {
            $code = $previous->getCode();
            // PostgreSQL constraint violation codes
            return in_array($code, ['23000', '23001', '23502', '23503', '23505', '23514']);
        }
        return false;
    }

    /**
     * Check if this is a column not found error
     */
    public function isColumnNotFound(): bool
    {
        if ($previous = $this->getPrevious()) {
            // PostgreSQL: 42703 = undefined_column
            return $previous->getCode() === '42703';
        }
        return false;
    }

    /**
     * Create a validation error exception
     */
    public static function validation(string $message, $code = 0): self
    {
        return new self($message, $code, null, 422);
    }

    /**
     * Create a not found exception
     */
    public static function notFound(string $message = 'Resource not found', $code = 0): self
    {
        return new self($message, $code, null, 404);
    }

    /**
     * Create an unauthorized exception
     */
    public static function unauthorized(string $message = 'Unauthorized', $code = 0): self
    {
        return new self($message, $code, null, 401);
    }

    /**
     * Create a forbidden exception
     */
    public static function forbidden(string $message = 'Forbidden', $code = 0): self
    {
        return new self($message, $code, null, 403);
    }
}
