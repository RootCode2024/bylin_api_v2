<?php

declare(strict_types=1);

namespace Modules\Core\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use Modules\Core\Exceptions\BusinessException;

abstract class BaseService
{
    protected function transaction(callable $callback)
    {
        try {
            return DB::transaction($callback);
        } catch (QueryException $e) {
            $this->logError('Database transaction failed', [
                'sql' => $e->getSql(),
                'bindings' => $e->getBindings(),
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);

            $message = $this->getDatabaseErrorMessage($e);
            $statusCode = $this->getDatabaseErrorStatusCode($e);

            throw new BusinessException($message, $e->getCode(), $e, $statusCode);
        } catch (BusinessException $e) {
            throw $e;
        } catch (\Exception $e) {
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

    protected function getDatabaseErrorMessage(QueryException $e): string
    {
        $code = $e->getCode();
        $message = $e->getMessage();

        if (str_contains($message, 'column') && str_contains($message, 'does not exist')) {
            preg_match('/column "([^"]+)"/', $message, $matches);
            $column = $matches[1] ?? 'unknown';
            return "Database configuration error: The column '{$column}' does not exist. Please contact support.";
        }

        if (str_contains($message, 'relation') && str_contains($message, 'does not exist')) {
            preg_match('/relation "([^"]+)"/', $message, $matches);
            $table = $matches[1] ?? 'unknown';
            return "Database configuration error: The table '{$table}' does not exist. Please contact support.";
        }

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

    protected function getDatabaseErrorStatusCode(QueryException $e): int
    {
        $code = $e->getCode();

        $statusCodes = [
            '23505' => 409,
            '23503' => 409,
            '23502' => 422,
            '42703' => 500,
            '42P01' => 500,
            '42601' => 500,
            '42804' => 500,
        ];

        return $statusCodes[$code] ?? 500;
    }

    protected function logInfo(string $message, array $context = []): void
    {
        Log::info($message, array_merge(['service' => static::class], $context));
    }

    protected function logWarning(string $message, array $context = []): void
    {
        Log::warning($message, array_merge(['service' => static::class], $context));
    }

    protected function logError(string $message, array $context = []): void
    {
        Log::error($message, array_merge(['service' => static::class], $context));
    }

    protected function validateRequired(array $data, array $required): void
    {
        $missing = [];

        foreach ($required as $field) {
            if (!isset($data[$field]) || $data[$field] === '') $missing[] = $field;
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
