<?php

declare(strict_types=1);

namespace Modules\Core\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Core\Exceptions\BusinessException;
use Illuminate\Database\QueryException;

/**
 * Base service class for business logic
 *
 * Provides transaction management and error handling
 * for all service operations
 */
abstract class BaseService
{
    /**
     * Execute a database transaction
     *
     * @param callable $callback
     * @return mixed
     * @throws BusinessException
     */
    protected function transaction(callable $callback)
    {
        try {
            return DB::transaction($callback);
        } catch (QueryException $e) {
            // Handle database-specific exceptions with more context
            $this->logError('Database transaction failed', [
                'sql' => $e->getSql(),
                'bindings' => $e->getBindings(),
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);

            // Provide user-friendly messages for common database errors
            $message = $this->getDatabaseErrorMessage($e);
            $statusCode = $this->getDatabaseErrorStatusCode($e);

            throw new BusinessException($message, $e->getCode(), $e, $statusCode);
        } catch (BusinessException $e) {
            // Re-throw business exceptions as-is
            throw $e;
        } catch (\Exception $e) {
            // Handle general exceptions
            $this->logError('Service transaction failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new BusinessException(
                'An error occurred while processing your request',
                $e->getCode(),
                $e,
                500
            );
        }
    }

    /**
     * Get a user-friendly error message based on database exception
     */
    protected function getDatabaseErrorMessage(QueryException $e): string
    {
        $code = $e->getCode();
        $message = $e->getMessage();

        // Check for specific column errors in the message
        if (str_contains($message, 'column') && str_contains($message, 'does not exist')) {
            preg_match('/column "([^"]+)"/', $message, $matches);
            $column = $matches[1] ?? 'unknown';
            return "Database configuration error: The column '{$column}' does not exist. Please contact support.";
        }

        // Check for table errors
        if (str_contains($message, 'relation') && str_contains($message, 'does not exist')) {
            preg_match('/relation "([^"]+)"/', $message, $matches);
            $table = $matches[1] ?? 'unknown';
            return "Database configuration error: The table '{$table}' does not exist. Please contact support.";
        }

        // PostgreSQL error codes
        $errorMessages = [
            '23000' => 'A database constraint was violated',
            '23505' => 'This record already exists (duplicate entry)',
            '23503' => 'Cannot delete this record because it is referenced by other records',
            '23502' => 'A required field is missing',
            '42703' => 'Database configuration error. Please contact support.',
            '42P01' => 'Database configuration error. Please contact support.',
            '42601' => 'Database query syntax error. Please contact support.',
            '42804' => 'Database data type mismatch. Please contact support.',
        ];

        return $errorMessages[$code] ?? 'A database error occurred while processing your request';
    }

    /**
     * Get appropriate HTTP status code based on database exception
     */
    protected function getDatabaseErrorStatusCode(QueryException $e): int
    {
        $code = $e->getCode();

        // Map database errors to HTTP status codes
        $statusCodes = [
            '23505' => 409, // Conflict (duplicate)
            '23503' => 409, // Conflict (foreign key)
            '23502' => 422, // Unprocessable Entity (missing required)
            '42703' => 500, // Internal Server Error (column missing)
            '42P01' => 500, // Internal Server Error (table missing)
            '42601' => 500, // Internal Server Error (syntax)
            '42804' => 500, // Internal Server Error (type mismatch)
        ];

        return $statusCodes[$code] ?? 500;
    }

    /**
     * Log an info message
     */
    protected function logInfo(string $message, array $context = []): void
    {
        Log::info($message, array_merge(['service' => static::class], $context));
    }

    /**
     * Log a warning
     */
    protected function logWarning(string $message, array $context = []): void
    {
        Log::warning($message, array_merge(['service' => static::class], $context));
    }

    /**
     * Log an error
     */
    protected function logError(string $message, array $context = []): void
    {
        Log::error($message, array_merge(['service' => static::class], $context));
    }

    /**
     * Validate required fields in data array
     *
     * @throws BusinessException
     */
    protected function validateRequired(array $data, array $required): void
    {
        $missing = [];

        foreach ($required as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                $missing[] = $field;
            }
        }

        if (!empty($missing)) {
            throw new BusinessException(
                'Missing required fields: ' . implode(', ', $missing),
                0,
                null,
                422
            );
        }
    }
}
