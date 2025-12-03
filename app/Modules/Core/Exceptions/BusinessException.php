<?php

declare(strict_types=1);

namespace Modules\Core\Exceptions;

use Exception;

class BusinessException extends Exception
{
    protected int $statusCode = 422;

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
