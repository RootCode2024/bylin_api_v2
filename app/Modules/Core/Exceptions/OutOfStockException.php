<?php

declare(strict_types=1);

namespace Modules\Core\Exceptions;

class OutOfStockException extends BusinessException
{
    protected int $statusCode = 409; // Conflict
}
