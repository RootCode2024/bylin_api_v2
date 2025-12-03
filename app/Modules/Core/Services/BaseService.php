<?php

declare(strict_types=1);

namespace Modules\Core\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Core\Exceptions\BusinessException;

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
        } catch (\Exception $e) {
            Log::error('Service transaction failed', [
                'service' => static::class,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new BusinessException(
                'An error occurred while processing your request',
                $e->getCode(),
                $e
            );
        }
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
                'Missing required fields: ' . implode(', ', $missing)
            );
        }
    }
}
