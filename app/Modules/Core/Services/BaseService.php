<?php

declare(strict_types=1);

namespace Modules\Core\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use Modules\Core\Exceptions\BusinessException;

/**
 * Service de base
 * 
 * Fournit des méthodes communes pour tous les services métier :
 * - Gestion des transactions
 * - Logging unifié
 * - Traduction des erreurs de base de données
 */
abstract class BaseService
{
    /**
     * Exécute une callback dans une transaction base de données
     * 
     * @throws BusinessException En cas d'erreur
     */
    protected function transaction(callable $callback)
    {
        try {
            return DB::transaction($callback);
        } catch (QueryException $e) {
            $this->logError('Échec de la transaction base de données', [
                'sql' => $e->getSql(),
                'bindings' => $e->getBindings(),
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);

            $message = $this->getDatabaseErrorMessage($e);
            $statusCode = $this->getDatabaseErrorStatusCode($e);

            throw new BusinessException($message, $e->getCode(), $e, $statusCode);
        } catch (BusinessException $e) {
            // Propager les exceptions métier sans les modifier
            throw $e;
        } catch (\Exception $e) {
            $this->logError('Échec du service', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new BusinessException(
                'Une erreur est survenue lors du traitement de votre demande',
                $e->getCode(),
                $e,
                500
            );
        }
    }

    /**
     * Traduit les erreurs PostgreSQL en messages utilisateur compréhensibles
     */
    protected function getDatabaseErrorMessage(QueryException $e): string
    {
        $code = $e->getCode();
        $message = $e->getMessage();

        // Détection des erreurs de colonnes manquantes
        if (str_contains($message, 'column') && str_contains($message, 'does not exist')) {
            preg_match('/column "([^"]+)"/', $message, $matches);
            $column = $matches[1] ?? 'inconnue';
            return "Erreur de configuration : La colonne '{$column}' n'existe pas. Veuillez contacter le support.";
        }

        // Détection des erreurs de tables manquantes
        if (str_contains($message, 'relation') && str_contains($message, 'does not exist')) {
            preg_match('/relation "([^"]+)"/', $message, $matches);
            $table = $matches[1] ?? 'inconnue';
            return "Erreur de configuration : La table '{$table}' n'existe pas. Veuillez contacter le support.";
        }

        // Mapping des codes d'erreur PostgreSQL vers messages claire
        $errorMessages = [
            '23000' => 'Une contrainte de base de données a été violée',
            '23505' => 'Cet enregistrement existe déjà (entrée dupliquée)',
            '23503' => 'Impossible de supprimer cet enregistrement car il est référencé par d\'autres données',
            '23502' => 'Un champ obligatoire est manquant',
            '42703' => 'Erreur de configuration de la base de données. Veuillez contacter le support.',
            '42P01' => 'Erreur de configuration de la base de données. Veuillez contacter le support.',
            '42601' => 'Erreur de syntaxe SQL. Veuillez contacter le support.',
            '42804' => 'Incompatibilité de type de données. Veuillez contacter le support.',
        ];

        return $errorMessages[$code] ?? 'Une erreur de base de données est survenue';
    }

    /**
     * Détermine le code HTTP approprié selon le type d'erreur base de données
     */
    protected function getDatabaseErrorStatusCode(QueryException $e): int
    {
        $code = $e->getCode();

        $statusCodes = [
            '23505' => 409, // Conflict - Duplicate entry
            '23503' => 409, // Conflict - Foreign key violation
            '23502' => 422, // Unprocessable Entity - Missing required field
            '42703' => 500, // Internal Server Error - Column doesn't exist
            '42P01' => 500, // Internal Server Error - Table doesn't exist
            '42601' => 500, // Internal Server Error - Syntax error
            '42804' => 500, // Internal Server Error - Type mismatch
        ];

        return $statusCodes[$code] ?? 500;
    }

    /**
     * Log un message d'information avec le contexte du service
     */
    protected function logInfo(string $message, array $context = []): void
    {
        Log::info($message, array_merge(['service' => static::class], $context));
    }

    /**
     * Log un avertissement avec le contexte du service
     */
    protected function logWarning(string $message, array $context = []): void
    {
        Log::warning($message, array_merge(['service' => static::class], $context));
    }

    /**
     * Log une erreur avec le contexte du service
     */
    protected function logError(string $message, array $context = []): void
    {
        Log::error($message, array_merge(['service' => static::class], $context));
    }

    /**
     * Valide la présence des champs obligatoires
     * 
     * @throws BusinessException Si des champs sont manquants
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
                'Champs obligatoires manquants : ' . implode(', ', $missing),
                0,
                null,
                422
            );
        }
    }
}
